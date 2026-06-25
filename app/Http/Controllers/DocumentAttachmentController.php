<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Services\DocumentAttachmentService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentAttachmentController extends Controller
{
    protected DocumentAttachmentService $attachmentService;

    // Dependency Injection (Bağımlılık Enjeksiyonu) ile Service'i sınıfa dahil ediyoruz.
    public function __construct(DocumentAttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Yeni ek belge yükleme (Store)
     */
    public function store(Request $request, Document $document)
    {
        // 1. Temel Validasyon (İş mantığı değil, sadece HTTP kalkanı)
        $request->validate([
            'file' => ['required', 'file', 'max:20480'] // Max 20MB
        ], [
            'file.required' => __('Lütfen yüklenecek bir dosya seçin.'),
            'file.max' => __('Ek dosya boyutu en fazla 20MB olabilir.')
        ]);

        // 2. Service'e Paslama
        try {
            $this->attachmentService->storeAttachment($document, $request->file('file'), $request->user()->id);
            return back()->with('success', __('Ek belge başarıyla sisteme yüklendi.'));
        } catch (Exception $e) {
            Log::error('Ek Belge Yükleme Hatası: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ek belge indirme (Download)
     */
    public function download(DocumentAttachment $attachment)
    {
        try {
            // 1. Service katmanı yetki kontrolünü yapar ve path/name döner
            $fileData = $this->attachmentService->downloadAttachment($attachment, Auth::user());

            // 2. İşletim sistemi bağımsız, en güvenli ve temiz indirme yöntemi
            return Storage::disk('local')->download(
                $fileData['path'],
                $fileData['name']
            );
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ek belgeyi güvenli silme (SoftDelete)
     */
    public function destroy(DocumentAttachment $attachment)
    {
        try {
            $this->attachmentService->deleteAttachment($attachment, Auth::user());
            return back()->with('success', __('Ek belge başarıyla silindi.'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
