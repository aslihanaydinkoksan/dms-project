<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTaskRequest;
use App\Services\TaskService;
use App\Models\ProcessTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class TaskController extends Controller
{
    protected TaskService $taskService;

    // Dependency Injection (SOLID: Bağımlılık Enjeksiyonu)
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }
    /**
     * BPM OPERASYON MERKEZİ: Dinamik Kanban Tahtası (Index)
     * ENTEGRE: Local Scopes ile Gelişmiş Filtreleme Mimarisi
     */
    public function index(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();

        // Şablon yoksa boş sayfayı bas
        if ($templates->isEmpty()) {
            return view('tasks.index', [
                'templates' => collect(),
                'selectedTemplate' => null,
                'stages' => collect(),
                'calendarEvents' => [],
                'currentView' => 'kanban',
                'filters' => [] // YENİ: Boş filtre dizisi gönderiyoruz
            ]);
        }

        $templateId = $request->query('template_id', $templates->first()->id);
        $selectedTemplate = ProcessTemplate::findOrFail($templateId);

        // Kullanıcının tercih ettiği görünüm (kanban veya calendar)
        $currentView = $request->query('view', 'kanban');

        // ====================================================================
        // YENİ: Gelişmiş Filtre Verilerini Yakala
        // ====================================================================
        $filters = $request->only(['search', 'date_start', 'date_end']);

        // Kanban İçin Aşamalar ve Veriler (Filtre Entegreli)
        $stages = \App\Models\ProcessStage::where('process_template_id', $selectedTemplate->id)
            ->with(['tasks' => function ($query) use ($filters) { // YENİ: use ($filters) eklendi
                $query->with(['creator', 'users'])
                    ->filter($filters) // YENİ: Local Scope Buraya Enjekte Edildi!
                    ->where(function ($q) {
                        // Aktif ve Onay Bekleyenleri her zaman getir
                        $q->whereIn('status', ['active', 'pending_closure_approval'])
                            // Tamamlananları ise sadece SON 14 GÜN içinde güncellenmişse getir (Performans Kalkanı)
                            ->orWhere(function ($sq) {
                                $sq->where('status', 'completed')
                                    ->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(14));
                            });
                    })
                    ->orderBy('updated_at', 'desc');
            }])->orderBy('sort_order')->get();

        // AJANDA (CALENDAR) İÇİN DİNAMİK EVENT ÜRETİCİ (Dokunulmadı, orijinal)
        $calendarEvents = [];
        if ($currentView === 'calendar') {
            foreach ($stages as $stage) {
                foreach ($stage->tasks as $task) {

                    // Dinamik "Bitiş" tarihini şablondaki date tipindeki ilk alandan bul
                    $endDate = null;
                    if (is_array($selectedTemplate->fields)) {
                        foreach ($selectedTemplate->fields as $field) {
                            if (($field['type'] ?? '') === 'date' && !empty($task->custom_data[$field['name']])) {
                                $endDate = $task->custom_data[$field['name']];
                                break;
                            }
                        }
                    }

                    $calendarEvents[] = [
                        'id' => $task->id,
                        'title' => 'TASK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT) . ' | ' . $task->title,
                        'start' => $task->created_at->format('Y-m-d'), // Başlangıç = İşi açtığı gün
                        'end' => $endDate ? \Carbon\Carbon::parse($endDate)->addDay()->format('Y-m-d') : null, // Bitiş = JSON'daki dinamik tarih
                        'backgroundColor' => $stage->color ?? '#3b82f6', // Kanban sütununun rengiyle %100 uyum!
                        'borderColor' => $stage->color ?? '#3b82f6',
                        'textColor' => '#ffffff',
                        'url' => route('tasks.show', $task->id), // Tıklanınca direk detaya gider
                    ];
                }
            }
        }

        // YENİ: compact içine 'filters' eklendi
        return view('tasks.index', compact('templates', 'selectedTemplate', 'stages', 'currentView', 'calendarEvents', 'filters'));
    }
    /**
     * GÖREV DETAY EKRANI (Arşivden veya Kanban'dan tıklanınca açılır)
     */
    public function show(\App\Models\Task $task)
    {
        $task->load(['template.department', 'creator', 'users', 'stage']);

        // En yeni log en üstte olacak şekilde çekiyoruz
        $logs = $task->logs()->with('user')->latest()->get();

        return view('tasks.show', compact('task', 'logs'));
    }

    /**
     * AJAX ENDPOINT: Kartı sürükleyip bıraktığımızda veritabanını günceller
     * ENTEGRE: Anti-Skip, Stage-Gate Notları ve GÜVENLİ Dosya Yükleme
     */
    public function updateStage(Request $request, \App\Models\Task $task): \Illuminate\Http\JsonResponse
    {
        // 1. Zenginleştirilmiş Validasyon
        $validated = $request->validate([
            'current_stage_id' => 'required|exists:process_stages,id',
            'transition_note'  => 'nullable|string|max:500',
            'attachment'       => 'nullable|file|extensions:pdf,xls,xlsx,jpg,jpeg,udf|max:20480' // Max 20MB
        ]);

        try {
            $targetStage = \App\Models\ProcessStage::findOrFail($validated['current_stage_id']);

            // Zırh 1: Başka sürecin aşamasına fırlatmayı engelle
            if ($targetStage->process_template_id !== $task->process_template_id) {
                return response()->json(['success' => false, 'message' => 'Geçersiz süreç aşaması eşleşmesi!'], 400);
            }

            // ZIRH 2: DOĞRUSAL AKIŞ KALKANI (ANTI-SKIP SHIELD)
            $stages = \App\Models\ProcessStage::where('process_template_id', $task->process_template_id)
                ->orderBy('sort_order', 'asc')->pluck('id')->toArray();

            $originalIndex = array_search($task->current_stage_id, $stages);
            $targetIndex = array_search($targetStage->id, $stages);

            if ($originalIndex !== false && $targetIndex !== false && abs($targetIndex - $originalIndex) > 1) {
                return response()->json(['error' => 'Kurumsal iş akışı kuralları gereği aşama atlanamaz. Lütfen süreci adım adım ilerletin.'], 422);
            }

            // Eski aşamayı log için hafızaya al
            $oldStageName = $task->stage->name ?? 'Bilinmeyen Aşama';

            // ====================================================================
            // GÜVENLİ DOSYA İŞLEME (SECURE FILE UPLOAD)
            // ====================================================================
            $attachmentLogText = ''; // Dışarıda sadece boş bir metin tanımlıyoruz

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeName = time() . '_' . \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

                // DİKKAT: 'local' (Gizli) diske kaydediyoruz!
                $path = $file->storeAs("tasks/attachments/{$task->id}", $safeName, 'local');

                // DİKKAT: $attachment değişkeni SADECE bu IF bloğunun içinde yaşar!
                $attachment = \App\Models\TaskAttachment::create([
                    'task_id'          => $task->id,
                    'process_stage_id' => $targetStage->id,
                    'user_id'          => Auth::id(),
                    'file_name'        => $originalName,
                    'file_path'        => $path,
                    'file_size'        => $file->getSize(),
                    'extension'        => $extension,
                ]);

                // Rota ve Link üretme işlemi de bu bloğun içinde olmalıdır
                $fileUrl = route('tasks.attachments.download', $attachment->id);
                $attachmentLogText = "<br><br><span style='color: #0284c7; font-size: 0.85rem; font-weight:600; display:flex; align-items:center; gap:5px;'><i data-lucide='paperclip' style='width:14px;'></i> Ek Belge Yüklendi: <a href='{$fileUrl}' target='_blank' style='color:#2563eb; text-decoration:underline;'>{$originalName}</a></span>";
            }

            // Kurallara uyuyorsa veritabanını sessizce güncelle
            $task->update(['current_stage_id' => $targetStage->id]);

            // ====================================================================
            // DENETİM İZİNE YAZ
            // ====================================================================
            $logDescription = "İşlem yeni aşamaya taşındı: <strong>{$targetStage->name}</strong>.";

            if (!empty($validated['transition_note'])) {
                $logDescription .= "<br><br><span style='color: var(--text-muted); font-style: italic;'>Kullanıcı Notu: " . strip_tags($validated['transition_note']) . "</span>";
            }

            // Eğer dosya yüklendiyse, hazırladığımız HTML şablonu loga eklenir. Yüklenmediyse boş metin ('') eklenir, hata vermez.
            $logDescription .= $attachmentLogText;

            \App\Models\TaskLog::create([
                'task_id'     => $task->id,
                'user_id'     => Auth::id(),
                'action'      => 'stage_transition',
                'description' => $logDescription,
                'old_data'    => ['stage' => $oldStageName],
                'new_data'    => ['stage' => $targetStage->name],
                'ip_address'  => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => "📌 İş başarıyla '{$targetStage->name}' aşamasına taşındı."
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * İş Oluşturma Ekranını Aç (Şablon seçilerek gelinecek)
     */
    public function create(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();

        // Kullanıcı önceden bir şablon seçerek URL'den (?template_id=3) geldiyse:
        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = ProcessTemplate::with('department')->find($request->query('template_id'));
        }

        return view('tasks.create', compact('templates', 'selectedTemplate'));
    }

    /**
     * Dinamik İş Kaydı (Fat Controller Yasak!)
     */
    public function store(StoreTaskRequest $request)
    {
        // 1. Zeki Validasyon'dan geçen tertemiz veriyi al
        $validated = $request->validated();

        // 2. İşi tamamen Servis Uzmanına devret
        $task = $this->taskService->createTask($validated, $request->user());

        // 3. Başarılı dönüş (Sonra Kanban board'a yönlendirilecek)
        return redirect()->route('tasks.index')
            ->with('success', 'İş başarıyla başlatıldı ve proje ekibi atandı.');
    }

    /**
     * AJAX (Tom Select): Yeni iş açarken hızlıca Ad-Hoc ekip üyesi arama API'si
     */
    public function searchUsers(Request $request)
    {
        $search = $request->query('q');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->where('is_active', true)
            ->with('department:id,name') // N+1 için Eager Load
            ->limit(15)
            ->get(['id', 'name', 'department_id'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'department' => $user->department ? $user->department->name : 'Birim Yok'
                ];
            });

        return response()->json($users);
    }
    /**
     * AJAX (Fetch API): Şablon seçildiğinde dinamik alanları, Sıkı Mod durumunu ve Zorunlu Grubu döndürür
     */
    public function getTemplateFields(int $id)
    {
        $template = ProcessTemplate::with(['mandatoryGroup.members.department'])->findOrFail($id);

        $mandatoryGroupData = null;
        if ($template->mandatoryGroup && $template->mandatoryGroup->is_active) {
            $members = $template->mandatoryGroup->members->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'department' => $m->department->name ?? 'Birim Yok',
                    'role' => $m->pivot->role
                ];
            });

            $mandatoryGroupData = [
                'name' => $template->mandatoryGroup->name,
                'allow_ad_hoc' => (bool)$template->allow_ad_hoc_members, // Sıkı mod bayrağı
                'member_ids' => $template->mandatoryGroup->members->pluck('id')->toArray(), // İzolasyon dizisi
                'members' => $members
            ];
        }

        return response()->json([
            'fields' => $template->fields ?? [],
            'mandatory_group' => $mandatoryGroupData
        ]);
    }
    /**
     * BPM ARŞİV MERKEZİ: Tamamlanan İşlerin Listesi
     */
    public function archive(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();

        // Sadece 'completed' olanları (Kapananları) çekiyoruz
        $query = \App\Models\Task::with(['template.department', 'creator', 'users'])
            ->where('status', 'completed');

        // Şablona göre filtreleme yapıldıysa
        if ($request->filled('template_id')) {
            $query->where('process_template_id', $request->query('template_id'));
        }

        // Sayfalama (Pagination) ile N+1'siz veri çekimi
        $tasks = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('tasks.archive', compact('tasks', 'templates'));
    }
    /**
     * Helper: Görevi düzenleme yetkisi var mı ve görev aktif mi?
     */
    private function authorizeTaskEdit(\App\Models\Task $task): void
    {
        if ($task->status !== 'active') {
            abort(403, '🛑 Sadece aktif (açık) görevler düzenlenebilir.');
        }

        $isAuthorized = $task->creator_id === Auth::id()
            || \Illuminate\Support\Facades\DB::table('task_user')->where('task_id', $task->id)->where('user_id', Auth::id())->where('role', 'manager')->exists()
            || Auth::user()->hasRole('Super Admin')
            || Auth::user()->hasRole('Admin');

        if (!$isAuthorized) {
            abort(403, '🛑 Bu süreci düzenlemek için Proje Yöneticisi veya İşin Sahibi olmalısınız.');
        }
    }

    /**
     * Görev Düzenleme Ekranı (Form)
     */
    public function edit(\App\Models\Task $task)
    {
        $this->authorizeTaskEdit($task);

        // İlişkileri yükle (Performans için N+1 kalkanı)
        $task->load(['template.mandatoryGroup.members.department', 'users.department']);

        // TomSelect için aktif kullanıcıları al
        $users = User::where('is_active', true)->with('department:id,name')->orderBy('name')->get();

        // Zorunlu üyeleri ve Kurucuyu Ad-Hoc listesinden (TomSelect'ten) hariç tutmak için tespit et
        $mandatoryUserIds = $task->template->mandatoryGroup ? $task->template->mandatoryGroup->members->pluck('id')->toArray() : [];
        $existingAdHocMembers = $task->users->filter(function ($u) use ($mandatoryUserIds, $task) {
            return !in_array($u->id, $mandatoryUserIds) && $u->id !== $task->creator_id;
        })->values();

        return view('tasks.edit', compact('task', 'users', 'mandatoryUserIds', 'existingAdHocMembers'));
    }

    /**
     * Görev Güncelleme İşlemi (POST/PUT)
     */
    public function update(Request $request, \App\Models\Task $task)
    {
        $this->authorizeTaskEdit($task);

        // 1. Temel Validasyon Kuralları
        $rules = [
            'title' => 'required|string|max:255',
            'team_members' => 'nullable|array',
            'team_members.*.user_id' => 'required|exists:users,id',
            'team_members.*.role' => 'required|in:manager,member',
            'custom_data' => 'nullable|array',
        ];

        // 2. Dinamik Şablon Alanları (EAV/JSON) İçin Validasyon Üretimi
        $fields = $task->template->fields ?? [];
        foreach ($fields as $field) {
            $rule = [];
            $rule[] = !empty($field['required']) ? 'required' : 'nullable';

            if ($field['type'] === 'number') {
                $rule[] = 'numeric';
            } elseif ($field['type'] === 'date') {
                $rule[] = 'date';
            } else {
                $rule[] = 'string';
            }

            $rules["custom_data.{$field['name']}"] = implode('|', $rule);
        }

        // 3. Veriyi Doğrula ve Servise Gönder
        $validatedData = $request->validate($rules);
        $this->taskService->updateTask($task, $validatedData);

        return redirect()->route('tasks.show', $task->id)->with('success', '✨ Süreç bilgileri başarıyla güncellendi.');
    }
    /**
     * GÜVENLİ DOSYA İNDİRME MERKEZİ (Secure File Serve)
     */
    public function downloadAttachment(\App\Models\TaskAttachment $attachment)
    {
        $task = $attachment->task;

        // Güvenlik: Sadece işi başlatan, projede görevi olan (manager/member) veya Yönetici kadrosu görebilir
        $isAuthorized = $task->creator_id === Auth::id()
            || \Illuminate\Support\Facades\DB::table('task_user')->where('task_id', $task->id)->where('user_id', Auth::id())->exists()
            || Auth::user()->hasRole('Super Admin')
            || Auth::user()->hasRole('Admin');

        if (!$isAuthorized) {
            abort(403, '🛑 Güvenlik İhlali: Bu dosyayı görüntüleme yetkiniz bulunmamaktadır.');
        }

        // Dosya 'local' diskte (storage/app/...) olduğu için gerçek fiziksel yolu alıyoruz
        $filePath = storage_path('app/' . $attachment->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'Dosya sunucuda bulunamadı veya silinmiş.');
        }

        // Kullanıcıya dosyayı orijinal adıyla güvenle teslim et
        return response()->download($filePath, $attachment->file_name);
    }
}
