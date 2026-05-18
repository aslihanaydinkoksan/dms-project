<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Merkezi API'den dönüşü karşılar.
     * URL: /sso/login?token=...
     */
    public function login(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->route('login')->withErrors('Geçersiz giriş isteği (Token yok).');
        }

        // Merkezi API'ye token'ı doğrulat (Burası Merkezi Sistemine gider)
        $response = Http::get('https://kys.koksan.com/merkezi_yonetim_sistemi/api/auth/verify-sso-token', [
            'token' => $token
        ]);

        if ($response->failed()) {
            return redirect('https://kys.koksan.com/merkezi_yonetim_sistemi')->with('hata', 'Merkezi oturum doğrulanamadı.');
        }

        $centralData = $response->json();
        $centralUser = $centralData['user'];

        // Yerel veritabanında (DMS) ara
        $localUser = User::where('email', $centralUser['email'])->first();

        // --- SENARYO A: Kullanıcı Zaten Var ---
        if ($localUser) {
            $localUser->update([
                'name' => $centralUser['first_name'] . ' ' . $centralUser['last_name'],
            ]);

            if ($localUser->is_active) {
                Auth::login($localUser);
                return redirect()->route('dashboard');
            } else {
                return redirect()->route('sso.onay_bekliyor');
            }
        }

        // --- SENARYO B: Kullanıcı Yok (İlk Giriş / Başvuru) ---
        session(['sso_user_data' => $centralUser]);
        return redirect()->route('sso.basvuru_formu');
    }

    /**
     * Eksik olan Başvuru Formu Metodu (Hatanın kaynağı burasıydı)
     */
    public function basvuruFormu()
    {
        $centralUser = session('sso_user_data');
        if (!$centralUser) {
            return redirect('https://kys.koksan.com/merkezi_yonetim_sistemi');
        }

        // DMS'teki departmanları çekiyoruz
        $departments = Department::orderBy('name')->get(); 

        return view('auth.sso_basvuru', compact('centralUser', 'departments'));
    }

    /**
     * Başvuruyu DMS yerel veritabanına kaydet
     */
    public function basvuruKaydet(Request $request)
    {
        $centralUser = session('sso_user_data');
        if (!$centralUser) {
            return redirect('https://kys.koksan.com/merkezi_yonetim_sistemi');
        }

        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        // Kullanıcıyı pasif olarak oluştur (Yöneticinin onaylaması gerekecek)
        User::create([
            'name' => $centralUser['first_name'] . ' ' . $centralUser['last_name'],
            'email' => $centralUser['email'],
            'password' => bcrypt(Str::random(16)), 
            'department_id' => $request->department_id,
            'is_active' => false, // Onay bekliyor modu
        ]);

        session()->forget('sso_user_data');

        return redirect()->route('sso.onay_bekliyor');
    }

    /**
     * Onay Bekleme Ekranı
     */
    public function onayBekliyor()
    {
        return view('auth.onay_bekliyor');
    }
}