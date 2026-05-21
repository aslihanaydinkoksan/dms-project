<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPhysicalMovement;
use App\Services\DocumentPhysicalService;
use App\Http\Requests\DocumentPhysicalRequest;
use Illuminate\Support\Facades\Gate;
use Exception;

class DocumentPhysicalController extends Controller
{
    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     */
    public function __construct(protected DocumentPhysicalService $physicalService) {}

    /**
     * Yeni bir fiziksel zimmet veya rota başlatır
     */
    public function store(DocumentPhysicalRequest $request, Document $document)
    {
        try {
            // İş mantığı tamamen Servise devredildi.
            // Servis işlemi yapar ve kullanıcıya gösterilecek doğru mesajı ($msg) döner.
            $msg = $this->physicalService->handleInitiation(
                $document,
                $request->user()->id,
                $request->validated()
            );

            if ($request->ajax()) return response()->json(['message' => $msg]);
            return back()->with('success', $msg);
        } catch (Exception $e) {
            if ($request->ajax()) return response()->json(['message' => $e->getMessage()], 400);
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Gelen zimmeti kabul eder veya reddeder
     */
    public function update(DocumentPhysicalRequest $request, DocumentPhysicalMovement $movement)
    {
        // 1. ZIRH: Manuel id kontrolü yerine Policy kullanıyoruz (Mükemmel Mimari)
        Gate::authorize('respond', $movement);

        try {
            // 2. Yanıt verme mantığı Servise devredildi
            $msg = $this->physicalService->handleResponse(
                $movement,
                $request->validated()
            );

            if ($request->ajax()) return response()->json(['message' => $msg]);
            return back()->with('success', $msg);
        } catch (Exception $e) {
            if ($request->ajax()) return response()->json(['message' => $e->getMessage()], 400);
            return back()->with('error', $e->getMessage());
        }
    }
}
