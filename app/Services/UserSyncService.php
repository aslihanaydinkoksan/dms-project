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

        $centralUsers = $this->mysApi->getAllUsers();
        $syncedCount = 0;
        $dummyPassword = Hash::make(Str::random(16));

        /** @var \Illuminate\Database\Eloquent\Collection|User[] $localUsers */
        $localUsers = User::withTrashed()->get();
        $usersByEmail = $localUsers->keyBy('email');
        $usersByTc = $localUsers->keyBy('tc_no')->filter(fn($u, $k) => !empty($k));

        DB::transaction(function () use ($centralUsers, &$syncedCount, $usersByEmail, $usersByTc, $dummyPassword) {
            foreach ($centralUsers as $centralUser) {
                if (empty($centralUser['email'])) continue;

                $incomingTc = $centralUser['tc_no'] ?? null;
                if (empty($incomingTc) && preg_match('/^[0-9]{10,11}@/', $centralUser['email'])) {
                    $incomingTc = explode('@', $centralUser['email'])[0];
                }
                $incomingRegNo = $centralUser['registration_no'] ?? null;

                // --- WORKFLOW EŞLEŞTİRME MANTIĞI ---
                /** @var User|null $user */
                $user = $usersByEmail->get($centralUser['email']) ?? 
                        (!empty($incomingTc) ? $usersByTc->get($incomingTc) : null);

                // --- DMS ÇAKIŞMA ÇÖZÜCÜ (UNIQUE KALKANI) ---
                // Eğer TC veya E-posta başka bir hayalet hesapta kalmışsa, hata vermemesi için onu null yap.
                $targetId = $user ? $user->id : 0;

                if (!empty($incomingTc)) {
                    $conflictTc = User::withTrashed()->where('tc_no', $incomingTc)->where('id', '!=', $targetId)->first();
                    if ($conflictTc) $conflictTc->update(['tc_no' => null]);
                }

                if (!empty($incomingRegNo)) {
                    $conflictReg = User::withTrashed()->where('registration_no', $incomingRegNo)->where('id', '!=', $targetId)->first();
                    if ($conflictReg) $conflictReg->update(['registration_no' => null]);
                }

                $newEmail = $centralUser['email'];
                if ($user) {
                    if (preg_match('/^[0-9]{10,11}@/', $newEmail) && !preg_match('/^[0-9]{10,11}@/', $user->email ?? '')) {
                        $newEmail = $user->email; 
                    }
                }

                if (!empty($newEmail)) {
                    $conflictEmail = User::withTrashed()->where('email', $newEmail)->where('id', '!=', $targetId)->first();
                    if ($conflictEmail) $conflictEmail->update(['email' => 'conflict_' . time() . '_' . $conflictEmail->email]);
                }
                // ------------------------------------------

                if ($user) {
                    // 1. MEVCUT KULLANICIYI GÜNCELLE
                    if ($user->trashed()) {
                        $user->restore();
                    }

                    $user->update([
                        'tc_no'           => $incomingTc ?? $user->tc_no,
                        'registration_no' => $incomingRegNo ?? $user->registration_no,
                        'name'            => $centralUser['name'],
                        'email'           => $newEmail,
                        'is_active'       => $centralUser['is_active'] ?? true,
                        'department_id'   => $centralUser['department']['id'] ?? $user->department_id,
                    ]);
                    $syncedCount++;
                } else {
                    // 2. YENİ KULLANICI EKLE
                    User::create([
                        'name'            => $centralUser['name'],
                        'email'           => $newEmail,
                        'password'        => $dummyPassword,
                        'tc_no'           => $incomingTc,
                        'registration_no' => $incomingRegNo,
                        'is_active'       => $centralUser['is_active'] ?? true,
                        'department_id'   => $centralUser['department']['id'] ?? null,
                    ]);
                    $syncedCount++;
                }
            }
        });

        return $syncedCount;
    }
}
