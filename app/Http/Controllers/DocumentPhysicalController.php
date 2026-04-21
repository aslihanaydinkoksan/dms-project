<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPhysicalMovement;
use App\Services\DocumentPhysicalService;
use App\Http\Requests\DocumentPhysicalRequest;
use Illuminate\Support\Facades\Auth;

class DocumentPhysicalController extends Controller
{
    public function __construct(protected DocumentPhysicalService $physicalService) {}

    public function store(DocumentPhysicalRequest $request, Document $document)
    {
        try {
            if ($request->action === 'initiate') {
                $receivers = $request->receiver_ids;

                if (count($receivers) > 1) {
                    // Birden fazla kişi seçildiyse Rota Başlat
                    $this->physicalService->startRoutingSlip($document, Auth::id(), $receivers, $request->location_details, $request->comment);
                    $msg = 'Sıralı Posta Rotası (Routing Slip) başarıyla başlatıldı. Evrak ilk sıradaki kişiye yönlendirildi.';
                } else {
                    // Tek kişi seçildiyse normal devir başlat
                    $this->physicalService->initiateTransfer($document, Auth::id(), $receivers[0], $request->location_details, $request->comment);
                    $msg = 'Fiziksel evrak devri başlatıldı. Karşı tarafın onayı bekleniyor.';
                }
            }

            if ($request->ajax()) return response()->json(['message' => $msg]);
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax()) return response()->json(['message' => $e->getMessage()], 500);
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(DocumentPhysicalRequest $request, DocumentPhysicalMovement $movement)
    {
        // Güvenlik: Sadece alıcı kişi kabul/red yapabilir
        if (Auth::id() !== $movement->receiver_id) {
            if ($request->ajax()) return response()->json(['message' => 'Bu işleme yetkiniz yok.'], 403);
            abort(403, 'Bu işleme yetkiniz yok.');
        }

        try {
            if ($request->action === 'accept') {
                $this->physicalService->acceptTransfer($movement, $request->comment, $request->location_details);
                $msg = 'Evrak başarıyla teslim alındı.';
            } else {
                $this->physicalService->rejectTransfer($movement, $request->comment);
                $msg = 'Evrak teslimi reddedildi ve göndericiye iade edildi.';
            }

            if ($request->ajax()) return response()->json(['message' => $msg]);
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax()) return response()->json(['message' => $e->getMessage()], 500);
            return back()->with('error', $e->getMessage());
        }
    }
}
