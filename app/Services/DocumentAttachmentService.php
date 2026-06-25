<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DocumentAttachmentService
{
    /**
     * Ana dokümana yeni bir ek belge yükler.
     * * @param Document $document
     * @param UploadedFile $file
     * @param int $userId
     * @return DocumentAttachment
     * @throws Exception
     */
    public function storeAttachment(Document $document, UploadedFile $file, int $userId): DocumentAttachment
    {
        // Kural: Dosya tiplerini hard-code yazmıyoruz. Projenin config/dms.php dosyasından okuyoruz.
        // Fallback (varsayılan) olarak genel kurumsal formatları belirtiyoruz.
        $allowedMimes = config('dms.allowed_attachment_mimes', [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        if (!in_array($file->getClientMimeType(), $allowedMimes)) {
            throw new Exception("Bu dosya formatının ek olarak yüklenmesine izin verilmiyor.");
        }

        return DB::transaction(function () use ($document, $file, $userId) {
            // Güvenli depolama (Public erişime kapalı)
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $directory = 'secure_documents/attachments/' . date('Y/m');

            $savedPath = $file->storeAs($directory, $fileName, 'local');

            if (!$savedPath) {
                throw new Exception("Ek belge sunucuya yazılırken kritik bir hata oluştu.");
            }

            // Veritabanı kaydı
            return DocumentAttachment::create([
                'document_id' => $document->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $savedPath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $userId,
            ]);
        });
    }

    /**
     * Ek belgeyi indirme işlemi. (Yetki devri mantığı içerir)
     * * @param DocumentAttachment $attachment
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function downloadAttachment(DocumentAttachment $attachment, User $user): array
    {
        $document = $attachment->document;

        // YETKİ KONTROLÜ (Delegation): Kullanıcı ana belgeyi görebiliyor mu?
        if ($user->cannot('view', $document)) {
            throw new Exception("Bu ekin bağlı olduğu ana dokümana erişim yetkiniz bulunmamaktadır.");
        }

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            throw new Exception("Fiziksel dosya sunucuda bulunamadı veya taşınmış.");
        }

        // Kullanıcıya kendi yüklediği orijinal ismi döndürüyoruz
        return [
            'path' => $attachment->file_path,
            'name' => $attachment->original_name
        ];
    }

    /**
     * Ek belgeyi güvenli bir şekilde siler (SoftDelete).
     * Sadece Admin veya belgeyi yükleyen kişi silebilir.
     */
    public function deleteAttachment(DocumentAttachment $attachment, User $user): void
    {
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin']);
        $isUploader = $attachment->uploaded_by === $user->id;

        if (!$isAdmin && !$isUploader) {
            throw new Exception("Bu eki silmek için yetkiniz bulunmuyor. Sadece yükleyen kişi veya sistem yöneticileri silebilir.");
        }

        $attachment->delete();
    }
}
