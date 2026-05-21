<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class VaultService
{
    /**
     * Kullanıcının kasası anlık olarak açık mı? (Süre kontrolü)
     */
    public function isVaultUnlocked(): bool
    {
        $unlockedUntil = session('vault_unlocked_until');

        // DEFANSİF PROGRAMLAMA: Gelen veri gerçekten bir Carbon objesi mi?
        if ($unlockedUntil instanceof Carbon) {
            return now()->isBefore($unlockedUntil);
        }

        return false;
    }

    /**
     * Kasa şifresini doğrular ve oturumu (Sudo modunu) başlatır
     */
    public function unlockVault(User $user, string $password): bool
    {
        // --- ZERO TRUST (SIFIR GÜVEN) MİMARİSİ ---
        // Kullanıcının profilinden belirlediği özel bir "Kasa Şifresi" varsa onu al.
        // Eğer o alan boşsa (null), standart sistem giriş şifresini (password) baz al.
        $hashedPassword = $user->vault_password ?: $user->password;

        if (Hash::check($password, $hashedPassword)) {
            // Kasayı şu andan itibaren 15 dakika boyunca AÇIK (Unlocked) işaretle
            session(['vault_unlocked_until' => now()->addMinutes(15)]);
            return true;
        }

        return false;
    }
}
