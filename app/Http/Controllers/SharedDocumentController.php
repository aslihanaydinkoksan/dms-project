<?php

namespace App\Http\Controllers;

use App\Models\DocumentExternalShare;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Storage;

class SharedDocumentController extends Controller
{
    /**
     * Dış paydaşlar için jeton kontrollü döküman izleme ekranı.
     */
    public function show(string $token, Request $request)
    {
        // 1. Eager Loading ile 'versions' ve 'versions.createdBy' ilişkilerini de çekiyoruz
        $share = DocumentExternalShare::with(['document.versions.createdBy', 'document.attachments', 'creator'])
            ->where('token', $token)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$share || !$share->document->status) {
            abort(404, 'Geçersiz veya süresi dolmuş güvenli erişim jetonu.');
        }

        $document = $share->document;
        $currentVersion = $document->currentVersion;
        $attachments = $document->attachments;
        
        // 2. Tüm versiyonları en yeniden en eskiye sıralayarak alıyoruz
        $versions = $document->versions->sortByDesc('version_number');

        if (!$currentVersion) {
            abort(404, 'Dokümana ait güncel bir fiziksel versiyon bulunamadı.');
        }

        // --- DIŞ PAYLAŞIM AUDIT LOGGING ---
        AuditLog::create([
            'user_id' => $share->created_by,
            'event' => 'guest_external_view',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'new_values' => [
                'accessed_by_email' => $share->email,
                'token' => $token,
                'version' => $currentVersion->version_number
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // 3. 'versions' değişkenini compact içine ekledik
        return view('documents.shared-view', compact('document', 'currentVersion', 'share', 'attachments', 'versions', 'token'));
    }

    /**
     * Dış paydaşlar için Ana Doküman veya Spesifik Bir Versiyon İndirme
     */
    public function download(string $token, Request $request)
    {
        $share = DocumentExternalShare::where('token', $token)->firstOrFail();

        if ($share->expires_at && $share->expires_at->isPast()) {
            abort(403, 'Bu bağlantının süresi dolmuştur.');
        }

        $document = $share->document;
        $versionId = $request->query('v'); // URL'den ?v=15 parametresini yakala

        // Güvenlik: İstenen versiyon ID'si var mı ve GERÇEKTEN bu dokümana mı ait?
        if ($versionId) {
            $versionToDownload = $document->versions()->where('id', $versionId)->firstOrFail();
        } else {
            // Parametre yoksa aktif/güncel versiyonu ver
            $versionToDownload = $document->currentVersion;
        }

        if (!$versionToDownload || !Storage::disk('local')->exists($versionToDownload->file_path)) {
            abort(404, 'Fiziksel dosya sunucuda bulunamadı.');
        }

        // İndirme işlemini tetikle
        return Storage::disk('local')->download(
            $versionToDownload->file_path, 
            $versionToDownload->original_file_name ?? ($document->document_number . '_v' . $versionToDownload->version_number . '.pdf')
        );
    }
    public function downloadAttachment(string $token, int $attachmentId)
    {
        $share = DocumentExternalShare::where('token', $token)->firstOrFail();

        // Dış paydaşın indirmek istediği ek, gerçekten paylaşılan dokümana mı ait?
        $attachment = \App\Models\DocumentAttachment::where('id', $attachmentId)
            ->where('document_id', $share->document_id)
            ->firstOrFail();

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    }
}
