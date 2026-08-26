<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentAttachmentVersion;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DocumentAttachmentService
{
    private function validateMimeType(UploadedFile $file): void
    {
        $allowedMimes = config('dms.uploads.allowed_extensions', [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        // Extension check yapıyoruz config yapına uygun olarak
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowedMimes)) {
            throw new Exception("Bu dosya formatının ({$ext}) ek olarak yüklenmesine izin verilmiyor.");
        }
    }

    /**
     * 1. CREATE: Yeni Ek Belge ve İlk Versiyonunu (v1) Yükler
     */
    public function storeAttachment(Document $document, array $data, UploadedFile $file, int $userId, string $ip): DocumentAttachment
    {
        $this->validateMimeType($file);

        return DB::transaction(function () use ($document, $data, $file, $userId, $ip) {
            $attachment = DocumentAttachment::create([
                'document_id' => $document->id,
                'title' => $data['title'] ?? $file->getClientOriginalName(),
                'description' => $data['description'] ?? null,
                'uploaded_by' => $userId,
            ]);

            $this->createVersion($attachment, $file, 1, 'İlk yükleme', $userId);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'attachment_added',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'new_values' => ['attachment_title' => $attachment->title],
                'ip_address' => $ip,
                'user_agent' => request()->userAgent()
            ]);

            return $attachment;
        });
    }

    /**
     * 2. UPDATE (Metadata): Ek belgenin Başlık ve Açıklamasını Günceller
     */
    public function updateAttachmentMetadata(DocumentAttachment $attachment, array $data, int $userId, string $ip): void
    {
        $attachment->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'event' => 'attachment_metadata_updated',
            'auditable_type' => Document::class,
            'auditable_id' => $attachment->document_id,
            'new_values' => ['attachment_title' => $attachment->title],
            'ip_address' => $ip,
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * 3. CHECK-IN: Ek Belgeye Yeni Bir Dosya (Versiyon) Yükler
     */
    public function checkinAttachment(DocumentAttachment $attachment, UploadedFile $file, ?string $reason, int $userId, string $ip): void
    {
        $this->validateMimeType($file);

        DB::transaction(function () use ($attachment, $file, $reason, $userId, $ip) {
            /** @var DocumentAttachmentVersion|null $lastVersion */
            $lastVersion = $attachment->versions()->withTrashed()->latest('id')->first();
            $newVersionNumber = $lastVersion ? ((int)$lastVersion->version_number + 1) : 1;

            $this->createVersion($attachment, $file, $newVersionNumber, $reason, $userId);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'attachment_version_added',
                'auditable_type' => Document::class,
                'auditable_id' => $attachment->document_id,
                'new_values' => ['attachment_title' => $attachment->title, 'new_version' => $newVersionNumber],
                'ip_address' => $ip,
                'user_agent' => request()->userAgent()
            ]);
        });
    }

    /**
     * 4. DELETE VERSION (Rollback Destekli): Ek belgenin bir versiyonunu siler.
     */
    public function deleteAttachmentVersion(DocumentAttachmentVersion $version, int $userId, string $ip): void
    {
        DB::transaction(function () use ($version, $userId, $ip) {
            $attachment = $version->attachment;

            if ($attachment->versions()->count() <= 1) {
                throw new Exception("Ek belgenin tek versiyonu silinemez. Bunun yerine ek belgeyi tamamen silmelisiniz.");
            }

            $wasCurrent = $version->is_current;
            $deletedVersionNumber = $version->version_number;

            $version->delete(); // Soft Delete

            $newCurrentVersion = null;
            if ($wasCurrent) {
                $newCurrentVersion = $attachment->versions()->orderBy('id', 'desc')->first();
                if ($newCurrentVersion) {
                    $newCurrentVersion->updateQuietly(['is_current' => true]);
                }
            }

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'attachment_version_deleted',
                'auditable_type' => Document::class,
                'auditable_id' => $attachment->document_id,
                'old_values' => ['deleted_version' => $deletedVersionNumber, 'attachment_title' => $attachment->title],
                'new_values' => ['rollback_to' => $newCurrentVersion ? $newCurrentVersion->version_number : 'Yok'],
                'ip_address' => $ip,
                'user_agent' => request()->userAgent()
            ]);
        });
    }

    /**
     * 5. DELETE ATTACHMENT: Ek belgeyi tüm geçmişiyle tamamen siler
     */
    public function deleteAttachment(DocumentAttachment $attachment, int $userId, string $ip): void
    {
        $documentId = $attachment->document_id;
        $title = $attachment->title;

        $attachment->delete(); // Cascade soft deletes versions via DB if setup, or just soft deletes the parent

        AuditLog::create([
            'user_id' => $userId,
            'event' => 'attachment_deleted',
            'auditable_type' => Document::class,
            'auditable_id' => $documentId,
            'old_values' => ['attachment_title' => $title],
            'ip_address' => $ip,
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * 6. DOWNLOAD: Akıllı versiyon çözücü ile dosya indirir
     */
    public function downloadAttachment(DocumentAttachment $attachment, ?int $requestedVersionId = null): array
    {
        $version = $requestedVersionId
            ? $attachment->versions()->findOrFail($requestedVersionId)
            : $attachment->currentVersion;

        if (!$version || !Storage::disk('local')->exists($version->file_path)) {
            throw new Exception("Fiziksel dosya sunucuda bulunamadı veya taşınmış.");
        }

        return [
            'path' => $version->file_path,
            'name' => $version->original_name
        ];
    }

    /**
     * YARDIMCI METOT: Dosyayı diske yazar ve veritabanı kaydını oluşturur
     */
    private function createVersion(DocumentAttachment $attachment, UploadedFile $file, int $vNumber, ?string $reason, int $userId)
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory = 'secure_documents/attachments/' . date('Y/m');
        $savedPath = $file->storeAs($directory, $fileName, 'local');

        if (!$savedPath) {
            throw new Exception("Dosya sunucuya yazılırken hata oluştu.");
        }

        // Eski versiyonların aktifliğini kaldır
        $attachment->versions()->update(['is_current' => false]);

        return $attachment->versions()->create([
            'version_number' => $vNumber,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $savedPath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'created_by' => $userId,
            'is_current' => true,
            'revision_reason' => $reason
        ]);
    }
}
