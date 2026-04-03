<?php

namespace App\Http\Controllers;

use App\Models\UserDelegation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewDelegationAssigned;

class DelegationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $given = $user->givenDelegations()->with('proxy')->orderByDesc('created_at')->get();
        $received = $user->receivedDelegations()->with('delegator')->orderByDesc('created_at')->get();
        $users = User::where('is_active', true)->where('id', '!=', $user->id)->orderBy('name')->get();

        return view('profile.delegations', compact('given', 'received', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proxy_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'required|string|max:255'
        ], [
            'proxy_id.required' => 'Lütfen vekalet vereceğiniz personeli seçin.',
            'start_date.required' => 'Vekalet başlangıç tarihi zorunludur.',
            'end_date.after' => 'Bitiş tarihi, başlangıç tarihinden daha ileri bir zaman olmalıdır.',
            'reason.required' => 'Vekalet sebebi boş geçilemez (Örn: Yıllık İzin, Sağlık Raporu).'
        ]);

        // 1. Vekaleti Veritabanına Kaydet
        $delegation = UserDelegation::create([
            'delegator_id' => Auth::id(),
            'proxy_id' => $request->proxy_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'is_active' => true
        ]);

        // 2. ASENKRON BİLDİRİMİ FIRLAT (Beyin Cerrahi)
        $proxyUser = User::find($request->proxy_id);
        if ($proxyUser) {
            $proxyUser->notify(new NewDelegationAssigned($delegation));
        }

        return back()->with('success', 'Vekalet başarıyla tanımlandı. İş akışlarınız bu tarihler arasında vekilinize yönlendirilecektir.');
    }

    public function destroy(UserDelegation $delegation)
    {
        // Sadece vekaleti veren kişi silebilir
        if ($delegation->delegator_id !== Auth::id()) abort(403);
        $delegation->delete();
        return back()->with('success', 'Vekalet başarıyla iptal edildi.');
    }
}
