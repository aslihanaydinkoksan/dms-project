<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentAttachmentVersion;
use App\Services\DocumentAttachmentService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class DocumentAttachmentController extends Controller
{
    public function __construct(protected DocumentAttachmentService $attachmentService) {}

    public function store(Request $request, Document $document)
    {
        Gate::authorize('manageAttachment', [$document]);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->attachmentService->storeAttachment(
                $document,
                $request->only(['title', 'description']),
                $request->file('file'),
                Auth::id(),
                $request->ip()
            );
            return back()->with('success', __('Ek belge başarıyla sisteme yüklendi.'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, DocumentAttachment $attachment)
    {
        Gate::authorize('manageAttachment', [$attachment->document, $attachment]);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->attachmentService->updateAttachmentMetadata($attachment, $request->only(['title', 'description']), Auth::id(), $request->ip());
            return back()->with('success', __('Ek belge bilgileri güncellendi.'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function checkin(Request $request, DocumentAttachment $attachment)
    {
        Gate::authorize('manageAttachment', [$attachment->document, $attachment]);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'revision_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->attachmentService->checkinAttachment($attachment, $request->file('file'), $request->input('revision_reason'), Auth::id(), $request->ip());
            return back()->with('success', __('Ek belgeye yeni bir versiyon başarıyla eklendi.'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function download(Request $request, DocumentAttachment $attachment)
    {
        // View yetkisi Policy içinde service tarafında veya burada kontrol edilebilir
        Gate::authorize('view', $attachment->document);

        try {
            $versionId = $request->query('v');
            $fileData = $this->attachmentService->downloadAttachment($attachment, $versionId);

            return Storage::disk('local')->download($fileData['path'], $fileData['name']);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(DocumentAttachment $attachment)
    {
        Gate::authorize('manageAttachment', [$attachment->document, $attachment]);

        try {
            $this->attachmentService->deleteAttachment($attachment, Auth::id(), request()->ip());
            return back()->with('success', __('Ek belge başarıyla silindi.'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroyVersion(DocumentAttachmentVersion $version)
    {
        Gate::authorize('manageAttachment', [$version->attachment->document, $version->attachment]);

        try {
            $this->attachmentService->deleteAttachmentVersion($version, Auth::id(), request()->ip());
            return back()->with('success', __('Ek belgenin ilgili versiyonu silindi. (Gerekiyorsa otomatik rollback yapıldı).'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
