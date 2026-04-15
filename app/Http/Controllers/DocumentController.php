<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Services\DocumentService;
use App\Services\FolderService;
use App\Services\DocumentApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use App\Services\DocumentSearchService;
use Illuminate\View\View;
use App\Models\Tag;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentReadLog;
use Illuminate\Http\JsonResponse;
use App\Models\AuditLog;
use App\Models\Department;
use App\Http\Requests\CheckinDocumentRequest;
use App\Models\DocumentType;
use App\Services\DocumentNumberService;
use App\Services\DocumentStamperService;

class DocumentController extends Controller
{
    /**
     * @var DocumentSearchService
     */
    protected DocumentSearchService $searchService;

    /**
     * @var DocumentService
     */
    protected DocumentService $documentService;

    /**
     * @var FolderService
     */
    protected FolderService $folderService;
    /**
     * @var DocumentNumberService
     */
    protected DocumentNumberService $numberService;
    /**
     * @var DocumentStamperService
     */
    protected DocumentStamperService $stamperService;


    /**
     * Dependency Injection (Bağımlılık Enjeksiyonu)
     * Tüm servislerimizi constructor üzerinden içeri alıyoruz.
     */
    public function __construct(
        DocumentService $documentService,
        DocumentSearchService $searchService,
        FolderService $folderService,
        DocumentApprovalService $approvalService,
        DocumentNumberService $numberService,
        DocumentStamperService $stamperService

    ) {
        $this->documentService = $documentService;
        $this->searchService = $searchService;
        $this->folderService = $folderService;
        $this->approvalService = $approvalService;
        $this->numberService = $numberService;
        $this->stamperService = $stamperService;
    }
    /**
     * Tüm belgeleri listeler, Full-Text Search araması yapar ve İstatistik Kartlarını dondürür.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $keyword = $request->query('q');
        $status = $request->query('status');
        $privacy = $request->query('privacy');
        $startDate = $request->query('start_date'); // YENİ: Başlangıç Tarihi
        $endDate = $request->query('end_date');     // YENİ: Bitiş Tarihi

        // --- YENİ: HIZLI DURUM KARTLARI (WIDGETS) İÇİN İSTATİSTİKLER ---
        // Kullanıcının sadece görebilmeye yetkisi olduğu belgeleri (authorizedForUser) filtrelerden bağımsız alıyoruz.
        $baseQuery = Document::where(function ($q) use ($user) {
            $q->authorizedForUser($user);
        });

        // Query'i 'clone' ile çoğaltıyoruz ki bir sonraki hesaplamayı etkilemesin (Memory Leak koruması)
        $stats = (object) [
            'approved' => (clone $baseQuery)->whereIn('status', ['published', 'approved'])->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'public'   => (clone $baseQuery)->where('privacy_level', 'public')->count(),
            'secret'   => (clone $baseQuery)->whereIn('privacy_level', ['confidential', 'strictly_confidential'])->count(),
        ];

        // --- SERVİS ARAMASI ---
        $documents = $this->searchService->searchDocuments(
            $keyword,
            $user,
            15,
            $status,
            $privacy,
            $startDate,
            $endDate
        );
        $privacyLevels = SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Hizmete Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);

        // KESİN KONTROL: Eğer istek AJAX ise SADECE tabloyu (partial) gönder
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('documents.partials.list', compact('documents', 'keyword'))->render();
        }


        // Normal ziyaret ise tüm sayfayı ve istatistikleri gönder
        return view('documents.index', compact('documents', 'keyword', 'status', 'privacy', 'startDate', 'endDate', 'stats', 'privacyLevels'));
    }
    public function show(Document $document): View
    {
        // 1. Güvenlik Duvarı: Bu belgeyi görmeye yetkisi var mı?
        Gate::authorize('view', $document);

        // 2. N+1 Yıkıcı Eager Loading (Ana Veriler)
        $document->load([
            'folder',
            'currentVersion.createdBy',
            'versions.createdBy', // Versiyonları yükleyenleri de al
            'approvals.user',
            'tags'
        ]);
        $latestUploadedVersion = $document->versions->sortByDesc('id')->first();
        $document->setRelation('currentVersion', $latestUploadedVersion);

        $breadcrumb = $this->folderService->getFlatFolderList()[$document->folder_id] ?? ($document->folder->name ?? 'Ana Dizin');

        // 3. Yetki Kontrolü ve Logların Çekilmesi (Sadece Yetkililere)
        /** @var User $user */
        $user = Auth::user();
        $isOwner = $document->currentVersion && $document->currentVersion->created_by === $user->id;

        $auditLogs = collect();
        $readLogs = collect();

        // Patronun Kuralı: Tarihçeyi sadece Admin, Direktör, Müdür ve Belge Sahibi görebilir.
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Direktör', 'Müdür']) || $isOwner) {
            // Sistem İşlem Logları
            $auditLogs = AuditLog::with('user')
                ->where('auditable_type', Document::class)
                ->where('auditable_id', $document->id)
                ->latest()
                ->get();

            // Sayfada Kalma / Okuma Logları
            $readLogs = DocumentReadLog::with('user')
                ->where('document_id', $document->id)
                ->latest()
                ->get();
        }

        return view('documents.show', compact('document', 'breadcrumb', 'auditLogs', 'readLogs', 'isOwner'));
    }
    /**
     * Dinamik belge yükleme formunu gösterir.
     */
    public function create(): View
    {
        $flatFolders = $this->folderService->getFlatFolderList();

        $privacyLevels = SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Hizmete Özel',
            'strictly_confidential' => 'Gizli'
        ]);

        $tags = Tag::orderBy('name')->get();

        $users = User::where('is_active', true)
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();

        $departments = Department::orderBy('name')->get();
        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('documents.create', compact('flatFolders', 'privacyLevels', 'tags', 'users', 'departments', 'documentTypes'));
    }
    /**
     * AJAX İsteği: Seçilen Doküman Tipinin Dinamik Form Alanlarını (JSON) döner
     */
    public function getCustomFields($id): JsonResponse
    {
        $documentType = DocumentType::findOrFail($id);

        // Veritabanındaki 'custom_fields' JSON sütununu direkt döndürüyoruz.
        // Eğer boşsa hata vermemesi için boş dizi [] döner.
        return response()->json($documentType->custom_fields ?? []);
    }

    /**
     * @var \App\Services\DocumentApprovalService
     */
    protected $approvalService;

    /**
     * Formdan gelen devasa veriyi işler, belgeyi kaydeder ve akışı başlatır.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        try {
            $generatedDocNo = ''; // Başarı mesajında göstermek için dışarıda tanımladık

            DB::transaction(function () use ($request, &$generatedDocNo) {
                $data = $request->validated();

                // 1. OTOMATİK KODLAMA: Klasör önekine göre numarayı üret
                $data['document_number'] = $this->numberService->generateNextNumber($data['folder_id']);
                $generatedDocNo = $data['document_number'];

                // 2. Dosyayı Sunucuya Kaydet ve Metadata'yı Oluştur (DocumentService)
                $document = $this->documentService->storeDocument(
                    $data,
                    $request->file('file')
                );

                // --- 3. ZORUNLU DEPARTMAN ONAYI ENJEKSİYONU ---
                $user = Auth::user();
                $approvers = $request->input('approvers') ?? [];

                if ($user->department && $user->department->requires_approval_on_upload) {
                    $deptAdmin = User::role('Admin')
                        ->where('department_id', $user->department_id)
                        ->first()
                        ?? User::role('Super Admin')->first();

                    if ($deptAdmin) {
                        foreach ($approvers as &$app) {
                            $app['step_order'] += 1;
                        }

                        array_unshift($approvers, [
                            'user_id' => $deptAdmin->id,
                            'step_order' => 1
                        ]);
                    }
                }

                // 4. Workflow'u Başlat veya Direkt Yayınla
                if (count($approvers) > 0) {
                    $this->approvalService->startWorkflow(
                        $document,
                        $approvers,
                        Auth::id(),
                        $request->ip() ?? '0.0.0.0',
                        $request->userAgent() ?? 'Unknown'
                    );
                } else {
                    $document->updateQuietly(['status' => 'published']);
                }
            });

            return redirect()->route('documents.index')->with('success', "Belge başarıyla yüklendi! Sistem tarafından atanan kod: <strong>{$generatedDocNo}</strong>");
        } catch (Exception $e) {
            Log::error('DMS Genel Upload Hatası: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'file_name' => $request->file('file')?->getClientOriginalName()
            ]);

            return back()->withInput()->with('error', 'İşlem sırasında kritik bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Belgeyi tarayıcıda önizletir (Inline) veya indirir.
     * PDF'leri Damgalayarak (Stamping) servis eder.
     */
    public function download(Document $document)
    {
        Gate::authorize('view', $document);

        // 1. ZEKİ VERSİYON SEÇİCİ: URL'de 'v' parametresi varsa o versiyonu, yoksa currentVersion'u al!
        $requestedVersionId = request()->query('v');

        if ($requestedVersionId) {
            $version = $document->versions()->find($requestedVersionId);
        } else {
            $version = $document->currentVersion;
        }

        if (!$version || !Storage::disk('local')->exists($version->file_path)) {
            abort(404, 'Dosya sistemde bulunamadı. Veritabanı ve klasör uyuşmuyor.');
        }

        $path = Storage::disk('local')->path($version->file_path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $cleanFilename = $document->document_number . '_v' . $version->version_number . '_' . Str::slug($document->title) . '.' . $extension;
        $mimeType = Storage::disk('local')->mimeType($version->file_path);

        $isDownload = request()->has('download');

        // SADECE PDF İSE VE SADECE İNDİR BUTONUNA BASILDIYSA DAMGALA!
        if ($mimeType === 'application/pdf' && $isDownload) {
            try {
                // Damgalama servisi artık spesifik versiyonu almalı
                // Modeldeki currentVersion ilişkisini anlık olarak ezerek servise gönderiyoruz
                $document->setRelation('currentVersion', $version);

                $stampedPdf = $this->stamperService->stampPdf($document);

                return response($stampedPdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $cleanFilename . '"');
            } catch (Exception $e) {
                Log::error('PDF Damgalama Hatası: ' . $e->getMessage());
            }
        }

        // --- ÖNİZLEME VEYA PDF DIŞI DOSYALAR ---
        if ($isDownload) {
            return response()->download($path, $cleanFilename);
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $cleanFilename . '"'
        ]);
    }
    /**
     * Belge üst verilerini (Metadata) düzenleme formunu gösterir.
     */
    public function edit(Document $document): View
    {
        // 1. ZIRH: Bu kullanıcının bu belgeyi güncelleme yetkisi var mı?
        Gate::authorize('update', $document);

        // 2. Form için gerekli tüm listeleri (Create metodundaki gibi) çekiyoruz
        $flatFolders = $this->folderService->getFlatFolderList();
        $privacyLevels = SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Hizmete Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);
        $tags = Tag::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('name')->get();

        return view('documents.edit', compact('document', 'flatFolders', 'privacyLevels', 'tags', 'departments', 'documentTypes'));
    }
    /**
     * Belge üst verilerini (Metadata) günceller ve klasör değiştiyse numarasını yeniden üretir.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        // 1. ZIRH: İşlemi yapmadan önce yetkiyi tekrar doğrula
        Gate::authorize('update', $document);

        // 2. Gelen verileri doğrula (Validation)
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'folder_id' => 'required|exists:folders,id',
            'document_type_id' => 'required|exists:document_types,id',
            'privacy_level' => 'required|string',
            'related_department_id' => 'nullable|exists:departments,id',
            'department_retention_years' => 'required|integer|min:0',
            'archive_retention_years' => 'required|integer|min:0',
            'expire_at' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);

        try {
            DB::transaction(function () use ($validated, $document, $request) {
                // 3. ZEKİ KATEGORİ GÜNCELLEYİCİ
                $documentType = DocumentType::find($validated['document_type_id']);
                $categoryName = $documentType ? $documentType->category : 'Genel';

                // Eski durumları loglamak için saklayalım
                $oldFolderId = $document->folder_id;
                $oldDocumentNumber = $document->document_number;

                // Varsayılan olarak numara aynı kalır
                $newDocumentNumber = $oldDocumentNumber;

                // 4. ZEKİ NUMARA MOTORU: KLASÖR DEĞİŞTİ Mİ?
                if ($oldFolderId != $validated['folder_id']) {
                    // Evet! O zaman yeni klasörün sıradaki numarasını üret (Örn: IK-005 -> HUKUK-012)
                    $newDocumentNumber = $this->numberService->generateNextNumber($validated['folder_id']);
                }

                // 5. Belgeyi Güncelle
                $document->update([
                    'title' => $validated['title'],
                    'folder_id' => $validated['folder_id'],
                    'document_number' => $newDocumentNumber, // Yeni Veya Eski Numara
                    'document_type_id' => $validated['document_type_id'],
                    'category' => $categoryName, // Yeni kategori!
                    'privacy_level' => $validated['privacy_level'],
                    'related_department_id' => $validated['related_department_id'],
                    'department_retention_years' => $validated['department_retention_years'],
                    'archive_retention_years' => $validated['archive_retention_years'],
                    'expire_at' => $validated['expire_at'],
                ]);

                // 6. Etiketleri Güncelle (Sync: Eskileri siler, yenileri ekler)
                if (isset($validated['tags'])) {
                    $document->tags()->sync($validated['tags']);
                } else {
                    $document->tags()->detach(); // Hiç etiket seçilmediyse hepsini temizle
                }

                // 7. İzlenebilirlik (Audit Log): Klasör (ve Numara) değiştiyse KRİTİK LOG at!
                if ($oldFolderId != $validated['folder_id']) {
                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'event' => 'document_moved_and_renumbered', // Özel Event Adı
                        'auditable_type' => Document::class,
                        'auditable_id' => $document->id,
                        'old_values' => [
                            'folder_id' => $oldFolderId,
                            'document_number' => $oldDocumentNumber
                        ],
                        'new_values' => [
                            'folder_id' => $validated['folder_id'],
                            'document_number' => $newDocumentNumber
                        ],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                }
            });

            return redirect()->route('documents.show', $document->id)
                ->with('success', 'Belge bilgileri başarıyla güncellendi.');
        } catch (Exception $e) {
            Log::error('Belge Güncelleme Hatası: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }
    /**
     * Belgeyi revizyon için kilitler (Check-out)
     */
    public function checkout(Document $document): RedirectResponse
    {
        // Yetki: Bu kullanıcı belgeyi düzenleme (create/edit) yetkisine sahip mi?
        Gate::authorize('update', $document);

        try {
            $this->documentService->checkoutDocument(
                $document,
                Auth::id(),
                request()->ip() ?? '0.0.0.0',
                request()->userAgent() ?? 'Unknown'
            );

            return back()->with('success', 'Belge sizin adınıza kilitlendi. Artık yeni versiyon yükleyebilirsiniz.');
        } catch (Exception $e) {
            Log::error('DMS Checkout Hatası: ' . $e->getMessage(), ['doc_id' => $document->id]);
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Yeni versiyonu AJAX (Fetch) ile yükler ve kilidi açar (Check-in)
     */
    public function checkin(CheckinDocumentRequest $request, Document $document): JsonResponse
    {
        // 1. Güvenlik (Sadece kilitleyen veya Super Admin değiştirebilir)
        if ($document->locked_by !== Auth::id() && !Auth::user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Sadece belgeyi kilitleyen kişi yeni versiyon yükleyebilir.'], 403);
        }

        // 2. Validasyon artık CheckinDocumentRequest tarafından otomatik ve kusursuz yapılıyor!
        // Manuel validate() bloğunu sildik, kodumuz çok daha temiz (Skinny Controller).

        try {
            // 3. İşi Service'e Devret!
            $this->documentService->checkinDocument(
                $document,
                $request->file('file'),
                $request->input('revision_reason'),
                Auth::id()
            );

            // 4. Başarılı Yanıt
            return response()->json([
                'success' => true,
                'message' => 'Yeni versiyon başarıyla yüklendi ve zırhlı kontrolden geçti.'
            ]);
        } catch (Exception $e) {
            Log::error('DMS Check-in Hatası: ' . $e->getMessage());
            return response()->json(['message' => 'Sunucu hatası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Yönetici müdahalesi (Force Unlock)
     */
    public function forceUnlock(Document $document): RedirectResponse
    {
        // Sadece 'document.force_unlock' yetkisine sahip olanlar (Super Admin vb.) bu işlemi yapabilir.
        Gate::authorize('forceUnlock', $document);

        try {
            $this->documentService->forceUnlock(
                $document,
                Auth::id(),
                request()->ip() ?? '0.0.0.0',
                request()->userAgent() ?? 'Unknown'
            );

            return back()->with('success', 'Yönetici yetkisiyle belge kilidi zorla açıldı.');
        } catch (Exception $e) {
            Log::error('DMS Force Unlock Hatası: ' . $e->getMessage(), ['doc_id' => $document->id]);
            return back()->with('error', $e->getMessage());
        }
    }
    /**
     * JS Beacon tarafından tetiklenen okuma süresi loglayıcısı.
     */
    public function logTime(Request $request, Document $document): JsonResponse
    {
        $duration = $request->input('duration', 0);

        if ($duration > 0) {
            DocumentReadLog::create([
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'duration_seconds' => $duration,
                'ip_address' => $request->ip()
            ]);
        }

        return response()->json(['status' => 'logged']);
    }
    /**
     * Hukuk Yöneticisi: Fiziksel belgeyi bir personele zimmetler (Teslim sürecini başlatır)
     */
    public function assignPhysicalCopy(Request $request, Document $document): RedirectResponse
    {
        // Yetki Kontrolü: Belge sahibi veya tam yetkili yöneticiler yapabilir
        if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasPermissionTo('document.manage_all') && ($document->currentVersion && $document->currentVersion->created_by !== Auth::id())) {
            abort(403, 'Fiziksel zimmet işlemi için yetkiniz yok.');
        }

        $request->validate([
            'delivered_to_user_id' => 'required|exists:users,id'
        ]);

        $document->updateQuietly([
            'delivered_to_user_id' => $request->delivered_to_user_id,
            'physical_receipt_status' => 'pending', // Bekliyor
            'physical_location' => null // Yeni kişiye geçtiği için eski konumu sıfırla
        ]);

        $assignedUser = User::find($request->delivered_to_user_id);
        if ($assignedUser) {
            $assignedUser->notify(new \App\Notifications\PhysicalDocumentAssigned($document));
        }

        return back()->with('success', 'Fiziksel kopya zimmetleme işlemi başlatıldı. Karşı tarafın "Teslim Aldım" onayı bekleniyor.');
    }

    /**
     * Zimmetlenen Personel: Belgeyi teslim aldığını onaylar ve arşive koyar
     */
    public function confirmPhysicalReceipt(Request $request, Document $document): RedirectResponse
    {
        // Yetki: Sadece belgenin zimmetlendiği kişi bu onayı verebilir
        if (Auth::id() !== $document->delivered_to_user_id) {
            abort(403, 'Bu evrak size zimmetlenmemiş.');
        }

        $request->validate([
            'physical_location' => 'required|string|max:255'
        ]);

        $document->updateQuietly([
            'physical_location' => $request->physical_location,
            'physical_receipt_status' => 'received' // Teslim Alındı!
        ]);

        return back()->with('success', 'Fiziksel evrakı teslim aldınız ve arşiv konumu başarıyla kaydedildi.');
    }
    /**
     * Belgeyi güvenli bir şekilde siler (Soft Delete) ve loglar.
     */
    public function destroy(Document $document)
    {
        // 1. Yetki Kontrolü (Policy)
        Gate::authorize('delete', $document);

        try {
            $folderId = $document->folder_id;

            // 2. Silinme Logunu AuditLog'a Yaz (İzlenebilirlik)
            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'document_deleted',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'old_values' => ['status' => $document->status_text],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // 3. Soft Delete İşlemi
            $document->delete();

            return redirect()->route('folders.show', $folderId)->with('success', '🗑️ Belge başarıyla sistem arşivine kaldırıldı (Soft Delete).');
        } catch (Exception $e) {
            Log::error('Belge Silme Hatası: ' . $e->getMessage());
            return back()->with('error', 'Belge silinirken kritik bir hata oluştu.');
        }
    }
}
