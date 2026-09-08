<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class UserSyncService
{
    protected MysApiService $mysApi;

    public function __construct(MysApiService $mysApi)
    {
        $this->mysApi = $mysApi;
    }

    /**
     * Kullanıcıları MYS üzerinden senkronize eder.
     */
    public function sync(): int
    {
        set_time_limit(300);

        $users = $this->mysApi->getAllUsers();
        $syncedCount = 0;
        $dummyPassword = Hash::make(Str::random(16));

        // 1. TÜM KULLANICILARI RAM'E AL (Editör için tip belirttik)
        /** @var \Illuminate\Database\Eloquent\Collection|User[] $localUsers */
        $localUsers = User::withTrashed()->get();

        $usersByTc = $localUsers->keyBy('tc_no')->filter(fn($u, $k) => !empty($k));
        $usersByEmail = $localUsers->keyBy('email');

        // 2. İSME GÖRE GRUPLA (HAYALET HESAPLARI GÖZARDI EDEREK)
        // Intelephense hatasını çözmek için $u değişkeninin User modeli olduğunu (User $u) olarak belirttik
        $usersByName = $localUsers->filter(function (User $u) {
            return !$u->trashed() && !str_starts_with($u->email ?? '', 'merged_') && !str_starts_with($u->email ?? '', 'conflict_');
        })->groupBy(function (User $u) {
            $name = str_replace(['İ', 'I'], ['i', 'ı'], $u->name);
            return trim(mb_strtolower($name, 'UTF-8'));
        });

        DB::transaction(function () use ($users, &$syncedCount, $dummyPassword, $usersByTc, $usersByEmail, $usersByName) {
            foreach ($users as $userData) {
                
                // Editöre $user değişkeninin User modeli veya null olabileceğini söylüyoruz
                /** @var User|null $user */
                $user = null;

                $incomingEmail = $userData['email'] ?? null;
                $incomingName = $userData['name'] ?? null;
                $incomingTc = $userData['tc_no'] ?? null;
                $incomingRegNo = $userData['registration_no'] ?? null;

                if (!$incomingEmail) continue;

                // A. MYS'den gelen e-posta TC numarası mı?
                $isIncomingTcEmail = preg_match('/^[0-9]{10,11}@/', $incomingEmail);
                if ($isIncomingTcEmail && empty($incomingTc)) {
                    $incomingTc = explode('@', $incomingEmail)[0];
                }

                // --- 1. AŞAMA: KESİN EŞLEŞTİRME ---
                if (!empty($incomingTc) && $usersByTc->has($incomingTc)) {
                    $user = $usersByTc->get($incomingTc);
                }

                // --- 2. AŞAMA: E-POSTA İLE EŞLEŞTİRME ---
                if (!$user && !empty($incomingEmail) && $usersByEmail->has($incomingEmail)) {
                    $user = $usersByEmail->get($incomingEmail);
                }

                // --- 3. AŞAMA: İSİM İLE EŞLEŞTİRME (ÇÖP KUTUSU HARİÇ) ---
                if (!$user && !empty($incomingName)) {
                    $normalizedIncomingName = str_replace(['İ', 'I'], ['i', 'ı'], $incomingName);
                    $normalizedIncomingName = trim(mb_strtolower($normalizedIncomingName, 'UTF-8'));

                    if ($usersByName->has($normalizedIncomingName)) {
                        $potentialUsers = $usersByName->get($normalizedIncomingName);
                        if ($potentialUsers->count() === 1) {
                            $user = $potentialUsers->first();
                        }
                    }
                }

                // --- 4. AŞAMA: YENİ KAYIT ---
                if (!$user) {
                    $user = new User();
                    $user->password = $dummyPassword;
                }

                // --- ÇAKIŞMA ÇÖZÜCÜ ---
                if (!empty($incomingTc) && $user->tc_no !== $incomingTc) {
                    $conflictTcUser = User::withTrashed()->where('tc_no', $incomingTc)->where('id', '!=', $user->id)->first();
                    if ($conflictTcUser) $conflictTcUser->update(['tc_no' => null]);
                }

                if (!empty($incomingRegNo) && $user->registration_no !== $incomingRegNo) {
                    $conflictRegUser = User::withTrashed()->where('registration_no', $incomingRegNo)->where('id', '!=', $user->id)->first();
                    if ($conflictRegUser) $conflictRegUser->update(['registration_no' => null]);
                }

                if (!empty($incomingEmail) && $user->email !== $incomingEmail) {
                    $conflictEmailUser = User::withTrashed()->where('email', $incomingEmail)->where('id', '!=', $user->id)->first();
                    if ($conflictEmailUser) $conflictEmailUser->update(['email' => 'conflict_' . time() . '_' . $conflictEmailUser->email]);
                }

                // --- BİLGİLERİ GÜNCELLE ---
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->name = $incomingName;
                $user->tc_no = $incomingTc;
                $user->registration_no = $incomingRegNo;
                $user->is_active = $userData['is_active'] ?? true;

                // E-POSTA KALKANI
                $isExistingProperEmail = !empty($user->email) && !preg_match('/^[0-9]{10,11}@/', $user->email);
                if (!($isIncomingTcEmail && $isExistingProperEmail)) {
                    $user->email = $incomingEmail;
                }

                if (!empty($userData['department'])) {
                    $user->department_id = $userData['department']['id'] ?? null;
                }

                $user->save();
                $syncedCount++;
            }
        });

        return $syncedCount;
    }
}
