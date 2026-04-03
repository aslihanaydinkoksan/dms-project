<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SudoController extends Controller
{
    public function showVault(Document $document)
    {
        // Eğer zaten yetkisi (süresi) varsa, şifre sormadan direkt belgeye at
        if (session('vault_unlocked_until') && now()->isBefore(session('vault_unlocked_until'))) {
            return redirect()->route('documents.show', $document->id);
        }
        return view('documents.vault', compact('document'));
    }

    public function unlockVault(Request $request, Document $document)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = Auth::user();

        // --- ZERO TRUST (SIFIR GÜVEN) MİMARİSİ ---
        // Kullanıcının profilinden belirlediği özel bir "Kasa Şifresi" varsa onu al.
        // Eğer o alan boşsa (null), standart sistem giriş şifresini (password) baz al.
        $hashedPassword = $user->vault_password ?: $user->password;

        // Kullanıcının formdan girdiği şifre ile seçilen Hash'li şifreyi kıyasla
        if (Hash::check($request->password, $hashedPassword)) {

            // Kasayı şu andan itibaren 15 dakika boyunca AÇIK (Unlocked) işaretle
            session(['vault_unlocked_until' => now()->addMinutes(15)]);

            // Kullanıcı indirme linkine bastıysa indirmeye, show'a bastıysa show'a yönlendir.
            // (Eğer intended hafızada yoksa, fallback olarak belgenin show sayfasına gider)
            return redirect()->intended(route('documents.show', $document->id))
                ->with('success', '🔐 Kasa kilidi açıldı. Çok Gizli belgelere erişiminiz 15 dakika boyunca aktiftir.');
        }

        // Şifre Yanlışsa
        return back()->withErrors(['password' => 'Güvenlik doğrulaması başarısız. Lütfen kasa şifrenizi kontrol edin.']);
    }
}
