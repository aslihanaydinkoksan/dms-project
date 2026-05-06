<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Notifications\DocumentRevisionAlert;
use App\Notifications\WorkflowActionRequired;

class DocumentService
{
    /**
     * Yeni bir doküman ve ilk versiyonunu oluşturur.
     * * @param array $data Doğrulanmış form verileri
     * @param UploadedFile $file Yüklenen fiziksel dosya
     * @return Document
     * @throws Exception
     */
    public function storeDocument(array $data, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($data, $file) {

            // YENİ PARÇA: Formdan gelen ID'ye göre Doküman Tipini bul ve Kategorisini al
            $documentType = \App\Models\DocumentType::find($data['document_type_id']);
            $categoryName = $documentType ? $documentType->category : 'Genel';
            
            // 1. Ana Dokümanı Oluştur (Değişmeyen üst veri - Metadata)
            /** @var \App\Models\Document $document */
            $document = Document::create([
                'folder_id' => $data['folder_id'],
                'title' => $data['title'],
                'document_number' => $data['document_number'],
                'document_type_id' => $data['document_type_id'],
                'category' => $categoryName, // İŞTE EKSİK OLAN SİHİRLİ DOKUNUŞ BURASI!
                'related_department_id' => $data['related_department_id'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'privacy_level' => $data['privacy_level'],
                'expire_at' => $data['expire_at'] ?? null, // YENİ: Bitiş Tarihi
                'is_locked' => false,
                'status' => 'draft', // YENİ: Başlangıç statüsü
            ]);

            // YENİ: Etiketleri (Tags) Polymorphic Pivot Tabloya Bağla
            if (!empty($data['tags'])) {
                $document->tags()->sync($data['tags']);
            }

            // 2. Güvenli Dosya Yükleme (Storage)
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $directory = 'secure_documents/' . date('Y/m');

            $savedPath = $file->storeAs($directory, $fileName, 'local');

            if (!$savedPath) {
                throw new Exception("Dosya sunucuya yazılırken kritik bir hata oluştu.");
            }

            // 3. İlk Versiyonu Oluştur
            try {
                DocumentVersion::create([
                    // DİKKAT: $document->id() değil, $document->id kullanıyoruz!
                    'document_id' => $document->id,
                    'version_number' => '1.0',
                    'file_path' => $savedPath,
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'created_by' => Auth::id(),
                    'is_current' => true,
                ]);
            } catch (Exception $e) {
                // Hata durumunda yetim dosyayı (orphan file) temizle
                Storage::disk('local')->delete($savedPath);
                throw $e;
            }

            return $document;
        });
    }

    /**
     * İndirme işlemi için güncel versiyonu bulur, loglar ve dosya yolunu döner.
     *
     * @param Document $document
     * @param int $userId İşlemi yapan kullanıcı
     * @param string $ipAddress Kullanıcının IP adresi
     * @param string $userAgent Kullanıcının Tarayıcı bilgisi
     * @return array Dosya yolu ve gösterilecek ismi
     * @throws Exception
     */
    public function downloadDocument(Document $document, int $userId, string $ipAddress, string $userAgent): array
    {
        // 1. Güncel Versiyonu Bul (Modeldeki currentVersion ilişkisini kullanıyoruz)
        $currentVersion = $document->currentVersion;

        if (!$currentVersion) {
            throw new Exception("Bu dokümana ait aktif bir versiyon bulunamadı.");
        }

        // 2. Fiziksel Dosya Kontrolü
        if (!Storage::disk('local')->exists($currentVersion->file_path)) {
            throw new Exception("Fiziksel dosya sunucuda bulunamadı veya taşınmış.");
        }

        // 3. İzlenebilirlik (Audit Logging)
        // Polymorphic ilişki sayesinde tek bir satırla log atıyoruz.
        AuditLog::create([
            'user_id' => $userId,
            'event' => 'downloaded',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'new_values' => ['version' => $currentVersion->version_number], // Hangi versiyonu indirdi?
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // 4. Controller'a dosya verilerini döndür
        // Kullanıcıya karmaşık UUID ismi yerine, evrak numarası ve versiyonu içeren temiz bir isim veriyoruz.
        $extension = pathinfo($currentVersion->file_path, PATHINFO_EXTENSION);
        $downloadName = sprintf("%s_v%s.%s", $document->document_number, $currentVersion->version_number, $extension);

        return [
            'path' => $currentVersion->file_path,
            'name' => $downloadName
        ];
    }
    /**
     * 1. CHECK-OUT: Belgeyi revizyon için kilitler.
     */
    public function checkoutDocument(Document $document, int $userId, string $ip, string $userAgent): void
    {
        // 1. Durum Kontrolü: Zaten kilitli mi?
        if ($document->is_locked) {
            $lockerName = $document->lockedBy ? $document->lockedBy->name : 'Bilinmeyen Kullanıcı';
            throw new Exception("Bu belge şu anda {$lockerName} tarafından revize edilmektedir. İşlem bitene kadar kilitlidir.");
        }

        // 2. Veritabanı İşlemleri (Transaction)
        DB::transaction(function () use ($document, $userId, $ip, $userAgent) {

            $document->updateQuietly([
                'is_locked' => true,
                'locked_by' => $userId
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'locked_for_revision',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        });

        // 3. BİLDİRİM TETİKLEYİCİ (Sadece Transaction başarıyla biterse çalışır)
        // Eğer belgeyi kilitleyen kişi, belgenin sahibi değilse sahibine haber ver!
        if ($document->currentVersion && $document->currentVersion->created_by !== $userId) {
            $owner = User::find($document->currentVersion->created_by);
            $actor = User::find($userId);

            if ($owner && $actor) {
                // Kuyruğa fırlat (Ekranda donma yapmaz)
                $owner->notify(new DocumentRevisionAlert($document, 'checked_out', $actor->name));
            }
        }
    }

    /**
     * 2. CHECK-IN: Belgeye yeni versiyon ekler, kilidi kaldırır ve ONAY AKIŞINI BAŞTAN BAŞLATIR.
     */
    public function checkinDocument(Document $document, UploadedFile $file, ?string $reason, int $userId): void
    {
        // Bildirim atılacak onaycıları transaction dışında toplamak için boş bir dizi oluşturuyoruz
        $firstStepUsersToNotify = [];

        DB::transaction(function () use ($document, $file, $reason, $userId, &$firstStepUsersToNotify) {
            $currentMaxVersion = $document->versions()->max('version_number') ?? 0;
            $newVersionNumber = $currentMaxVersion + 1;

            // Belgenin halihazırda bir onay akışı (workflow) var mı?
            $hasApprovals = $document->approvals()->exists();

            // SADECE ONAY GEREKMİYORSA ESKİLERİN YAYININI KES (Onay gerekiyorsa onaylanınca kesilecek)
            if (!$hasApprovals) {
                $document->versions()->update(['is_current' => false]);
            }

            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('secure_documents', $fileName, 'local');

            $document->versions()->create([
                'version_number' => $newVersionNumber,
                'file_path' => $filePath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'created_by' => $userId,
                'is_current' => !$hasApprovals, // Onay gerekiyorsa False olarak başlar!
                'revision_reason' => $reason
            ]);

            // --- KİLİT VE AKIŞ GÜNCELLEMESİ ---
            if (!$hasApprovals) {
                // Onay akışı yoksa direkt yayınla ve kilidi aç
                $document->updateQuietly(['is_locked' => false, 'locked_by' => null, 'status' => 'published']);
            } else {
                // ONAY AKIŞI VARSA: Akışı baştan başlat, kilidi aç, statüyü bekliyor yap!
                $document->updateQuietly(['is_locked' => false, 'locked_by' => null, 'status' => 'pending_approval']);

                // Eski onayları ve redleri sıfırla ki tekrar onaycıların önüne düşsün
                $document->approvals()->update(['status' => 'pending']);

                // Akışın ilk adımındaki kullanıcıları bul (Bildirim atmak için)
                $minStep = $document->approvals()->min('step_order');
                $firstStepUserIds = $document->approvals()->where('step_order', $minStep)->pluck('user_id');
                $firstStepUsersToNotify = User::whereIn('id', $firstStepUserIds)->get();
            }
        });

        // TRANSACTION BİTTİ, BİLDİRİMLERİ FIRLAT:
        $document->refresh();

        // 1. Belgenin Asıl Sahibine Bildirim (Eğer işlemi başkası yaptıysa)
        $originalOwnerId = $document->versions()->orderBy('version_number', 'asc')->first()->created_by ?? null;
        if ($originalOwnerId && $originalOwnerId !== $userId) {
            $owner = User::find($originalOwnerId);
            $actor = User::find($userId);

            if ($owner && $actor) {
                $owner->notify(new DocumentRevisionAlert($document, 'checked_in', $actor->name));
            }
        }

        /** @var User $approverUser */
        foreach ($firstStepUsersToNotify as $approverUser) {
            $approverUser->notify(new WorkflowActionRequired($document, 'pending_your_approval'));
        }
    }

    /**
     * 3. FORCE UNLOCK: Yöneticiler için hayat kurtaran zorla kilit açma metodu.
     */
    public function forceUnlock(Document $document, int $userId, string $ip, string $userAgent): void
    {
        if (!$document->is_locked) {
            throw new Exception("Belge zaten kilitli değil.");
        }

        DB::transaction(function () use ($document, $userId, $ip, $userAgent) {
            $oldLocker = $document->lockedBy ? $document->lockedBy->name : 'Bilinmeyen';

            $document->updateQuietly([
                'is_locked' => false,
                'locked_by' => null
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'force_unlocked_by_admin',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'old_values' => ['locked_by' => $oldLocker],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        });
    }
    /**
     * UI için Bildirim Gidebilecek Üst Yöneticileri Departmana Göre Gruplayarak Getirir.
     */
    public function getNotifiableSuperiors(User $user)
    {
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin']);
        $userMaxLevel = $user->roles()->max('hierarchy_level') ?? 0;

        // Adminler otomatik olarak global yetkiye sahiptir
        $hasGlobalNotify = $isAdmin;
        if (!$hasGlobalNotify) {
            try {
                $hasGlobalNotify = $user->hasPermissionTo('notify.global');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            }
        }

        $query = User::with(['department', 'roles'])
            ->where('is_active', true)
            ->where('id', '!=', $user->id);

        // HİYERARŞİ ÇÖZÜMÜ: Eğer kullanıcı Super Admin veya Admin DEĞİLSE kendinden üstleri arasın.
        // Yöneticiler bu sınıra takılmadan bildirim atabilecek tüm listeyi görebilir.
        if (!$isAdmin) {
            $query->whereHas('roles', function ($q) use ($userMaxLevel) {
                $q->where('hierarchy_level', '>', $userMaxLevel);
            });
        }

        // Global yetkisi YOKSA, sadece KENDİ departmanındaki üstlerini görsün
        if (!$hasGlobalNotify) {
            $query->where('department_id', $user->department_id);
        }

        return $query->get()->groupBy(function ($u) {
            return $u->department ? $u->department->name : __('Bağımsız Yöneticiler');
        });
    }

    /**
     * Belge Yüklendikten Sonra Seçili Yöneticilere Bildirim Atar ve Pivot Loga Yazar.
     */
    public function notifySuperiors(Document $document, array $notifiedUserIds, User $uploader): void
    {
        if (empty($notifiedUserIds)) return;

        $validUsers = User::whereIn('id', $notifiedUserIds)->get();
        $usersToNotify = collect();

        foreach ($validUsers as $targetUser) {
            /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $targetUser */
            // GÜVENLİK DUVARI: Belge "Çok Gizli" ise ve yöneticinin bunu görme yetkisi yoksa, formu bypass etmiş olsa bile atla!
            if ($document->privacy_level === 'strictly_confidential') {
                $hasClearance = false;
                try {
                    $hasClearance = $targetUser->hasPermissionTo('document.view_strictly_confidential');
                } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                }

                if (!$hasClearance) continue;
            }

            $usersToNotify->push($targetUser);
        }

        if ($usersToNotify->isNotEmpty()) {
            // 1. Audit Pivot Tablosuna Yaz
            $document->notifiedUsers()->syncWithoutDetaching($usersToNotify->pluck('id')->toArray());

            // 2. Bildirimleri Fırlat
            \Illuminate\Support\Facades\Notification::send(
                $usersToNotify,
                new \App\Notifications\DocumentUploadedNotification($document, $uploader)
            );
        }
    }
    /**
     * Kurumsal Toplu Belge Yükleme Motoru (Batch Upload)
     */
    public function batchStore(array $globalData, array $files, array $documentsMeta, array $approvers, array $notifiedUserIds, User $user, ?string $ip, ?string $userAgent): array
    {
        $createdDocuments = [];

        // DB::transaction ile ya hepsi ya hiçbiri (Veri Bütünlüğü Kalkanı)
        DB::transaction(function () use ($globalData, $files, $documentsMeta, $approvers, $user, $ip, $userAgent, &$createdDocuments) {

            // Hukuk / Departman Onay Mantığı
            if ($user->department && $user->department->requires_approval_on_upload) {
                $deptAdmin = User::role('Admin')->where('department_id', $user->department_id)->first() ?? User::role('Super Admin')->first();
                if ($deptAdmin) {
                    foreach ($approvers as &$app) {
                        $app['step_order'] += 1;
                    }
                    array_unshift($approvers, ['user_id' => $deptAdmin->id, 'step_order' => 1]);
                }
            }

            foreach ($files as $index => $file) {
                $docMeta = $documentsMeta[$index];

                // Her dosya için global ve spesifik datayı birleştir
                $singleData = array_merge($globalData, $docMeta);

                // Numaratörü çalıştır (Her evraka özel no)
                $singleData['document_number'] = app(\App\Services\DocumentNumberService::class)->generateNextNumber($globalData['folder_id']);

                // Klasik store işlemini çağır
                $document = $this->storeDocument($singleData, $file);

                if (count($approvers) > 0) {
                    app(\App\Services\DocumentApprovalService::class)->startWorkflow($document, $approvers, $user->id, $ip ?? '0.0.0.0', $userAgent ?? 'Unknown');
                } else {
                    $document->updateQuietly(['status' => 'published']);
                }

                $createdDocuments[] = $document;
            }
        });

        // Tüm işlem bitince Toplu Bildirim at
        $this->notifySuperiorsBatch($createdDocuments, $notifiedUserIds, $user);

        return $createdDocuments;
    }

    /**
     * Toplu Yükleme Bildirim ve Audit Log Motoru
     */
    public function notifySuperiorsBatch(array $documents, array $notifiedUserIds, User $uploader): void
    {
        if (empty($notifiedUserIds) || empty($documents)) return;

        $validUsers = User::whereIn('id', $notifiedUserIds)->get();
        $usersToNotify = collect();

        foreach ($validUsers as $targetUser) {
            /** @var \App\Models\User $targetUser */
            $allowedDocs = collect();

            // Her belge için kullanıcının "Çok Gizli" clearance kontrolü
            foreach ($documents as $doc) {
                if ($doc->privacy_level === 'strictly_confidential') {
                    try {
                        if ($targetUser->hasPermissionTo('document.view_strictly_confidential')) {
                            $allowedDocs->push($doc);
                        }
                    } catch (Exception $e) {
                    }
                } else {
                    $allowedDocs->push($doc);
                }
            }

            if ($allowedDocs->isNotEmpty()) {
                // Her belge için Audit Log (Pivot) yaz
                foreach ($allowedDocs as $d) {
                    $d->notifiedUsers()->syncWithoutDetaching([$targetUser->id]);
                }
                $usersToNotify->push($targetUser);
            }
        }

        if ($usersToNotify->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send(
                $usersToNotify,
                new \App\Notifications\BatchDocumentUploadedNotification($documents, $uploader)
            );
        }
    }
}
