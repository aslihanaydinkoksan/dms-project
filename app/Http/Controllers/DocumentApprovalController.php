<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentApprovalService;
use App\Http\Requests\StartWorkflowRequest;
use App\Http\Requests\ApproveDocumentRequest;
use App\Http\Requests\RejectDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentApprovalController extends Controller
{
    protected DocumentApprovalService $approvalService;

    // Dependency Injection
    public function __construct(DocumentApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Onay akışını başlatır. (StartWorkflowRequest güvenlik duvarı devrede)
     */
    public function start(StartWorkflowRequest $request, Document $document): RedirectResponse
    {
        try {
            // validated() metodu sadece rules() içindeki güvenli verileri döndürür.
            $this->approvalService->startWorkflow(
                $document,
                $request->validated('approvers'),
                Auth::id(),
                $request->ip() ?? '0.0.0.0',
                $request->userAgent() ?? 'Unknown'
            );

            return back()->with('success', 'Onay akışı başarıyla başlatıldı.');
        } catch (Exception $e) {
            Log::error('DMS Workflow Start Hatası: ' . $e->getMessage(), ['doc_id' => $document->id]);
            return back()->with('error', 'Akış başlatılamadı: ' . $e->getMessage());
        }
    }

    /**
     * Bekleyen onay adımını onaylar. (ApproveDocumentRequest güvenlik duvarı devrede)
     */
    public function approve(ApproveDocumentRequest $request, Document $document): RedirectResponse
    {
        try {
            $this->approvalService->approveDocument(
                $document,
                Auth::id(),
                $request->ip() ?? '0.0.0.0',
                $request->userAgent() ?? 'Unknown'
            );

            return back()->with('success', 'Belge başarıyla onaylandı.');
        } catch (Exception $e) {
            Log::error('DMS Onaylama Hatası: ' . $e->getMessage(), ['doc_id' => $document->id]);
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Belgeyi reddeder. (RejectDocumentRequest güvenlik duvarı devrede - Yorumsuz geçilmez)
     */
    public function reject(RejectDocumentRequest $request, Document $document): RedirectResponse
    {
        try {
            $this->approvalService->rejectDocument(
                $document,
                Auth::id(),
                $request->validated('comment'), // Sadece zorunlu comment verisini al
                $request->ip() ?? '0.0.0.0',
                $request->userAgent() ?? 'Unknown'
            );

            return back()->with('success', 'Belge reddedildi ve onay akışı durduruldu.');
        } catch (Exception $e) {
            Log::error('DMS Reddetme Hatası: ' . $e->getMessage(), ['doc_id' => $document->id]);
            return back()->with('error', $e->getMessage());
        }
    }
}
