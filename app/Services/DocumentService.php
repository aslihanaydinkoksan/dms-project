<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Notifications\DocumentRevisionAlert;

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
            
            // 1. Ana Dokümanı Oluştur (Değişmeyen üst veri - Metadata)
            /** @var \App\Models\Document $document */
            $document = Document::create([
                'folder_id' => $data['folder_id'],
                'title' => $data['title'],
                'document_number' => $data['document_number'],
                'document_type_id' => $data['document_type_id'],
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
            $owner = \App\Models\User::find($document->currentVersion->created_by);
            $actor = \App\Models\User::find($userId);

            if ($owner && $actor) {
                // Kuyruğa fırlat (Ekranda donma yapmaz)
                $owner->notify(new DocumentRevisionAlert($document, 'checked_out', $actor->name));
            }
        }
    }

    /**
     * 2. CHECK-IN: Belgeye yeni versiyon ekler ve kilidi kaldırır.
     */
    public function checkinDocument(Document $document, UploadedFile $file, ?string $reason, int $userId): void
    {
        $user = \App\Models\User::find($userId);
        $requiresApproval = $user->department && $user->department->requires_approval_on_upload;

        DB::transaction(function () use ($document, $file, $reason, $userId, $requiresApproval) {
            $currentMaxVersion = $document->versions()->max('version_number') ?? 0;
            $newVersionNumber = $currentMaxVersion + 1;

            // DİKKAT: SADECE ONAY GEREKMİYORSA ESKİLERİN YAYININI KES
            if (!$requiresApproval) {
                $document->versions()->update(['is_current' => false]);
            }

            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('secure_documents', $fileName, 'local');

            $document->versions()->create([
                'version_number' => $newVersionNumber,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'created_by' => $userId,
                'is_current' => !$requiresApproval, // Onay gerekiyorsa False olarak başlar!
                'revision_reason' => $reason
            ]);

            // Kilit durumunu güncelle
            if (!$requiresApproval) {
                $document->updateQuietly(['is_locked' => false, 'locked_by' => null, 'status' => 'published']);
            } else {
                // Onay gerekiyorsa kilit açılmaz, belge "onay bekliyor" statüsüne çekilir
                $document->updateQuietly(['status' => 'pending_approval']);
            }
        });

        // 2. BİLDİRİM TETİKLEYİCİ (Sadece Transaction başarıyla biterse çalışır)
        $document->refresh(); // Veritabanındaki yeni versiyon verilerini modele yansıt

        // Önceki (İlk) versiyonu yükleyen kişiyi bulalım (Asıl sahip)
        $originalOwnerId = $document->versions()->orderBy('version_number', 'asc')->first()->created_by ?? null;

        // Yeni versiyon yüklendiğinde, işlemi yapan kişi belgenin asıl sahibi değilse uyar.
        if ($originalOwnerId && $originalOwnerId !== $userId) {
            $owner = \App\Models\User::find($originalOwnerId);
            $actor = \App\Models\User::find($userId);

            if ($owner && $actor) {
                // Kuyruğa fırlat (Ekranda donma yapmaz)
                $owner->notify(new DocumentRevisionAlert($document, 'checked_in', $actor->name));
            }
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
}
