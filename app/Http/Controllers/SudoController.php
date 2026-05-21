<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use App\Services\VaultService;

class SudoController extends Controller
{
    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     */
    public function __construct(protected VaultService $vaultService) {}

    /**
     * Kasa (Şifre İsteme) Ekranını Gösterir
     */
    public function showVault(Document $document)
    {
        // Yetki (süre) kontrolü Servis'e devredildi. P1006 Hatası çözüldü!
        if ($this->vaultService->isVaultUnlocked()) {
            return redirect()->route('documents.show', $document->id);
        }

        return view('documents.vault', compact('document'));
    }

    /**
     * Kasa Şifresini Doğrular ve Erişimi Açar
     */
    public function unlockVault(Request $request, Document $document)
    {
        $validated = $request->validate([
            'password' => 'required|string'
        ]);

        // Kriptografi ve Session süreci Servis'e devredildi
        $isUnlocked = $this->vaultService->unlockVault($request->user(), $validated['password']);

        if ($isUnlocked) {
            // Kullanıcı indirme linkine bastıysa oraya, basmadıysa fallback olarak show'a yönlendir.
            return redirect()->intended(route('documents.show', $document->id))
                ->with('success', '🔐 Kasa kilidi açıldı. Çok Gizli belgelere erişiminiz 15 dakika boyunca aktiftir.');
        }

        return back()->withErrors(['password' => 'Güvenlik doğrulaması başarısız. Lütfen kasa şifrenizi kontrol edin.']);
    }
}
