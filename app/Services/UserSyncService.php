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

        // WORKFLOW PROJESİ MANTIĞI: N+1 Koruması için veritabanını RAM'e (Memory) alıyoruz.
        /** @var \Illuminate\Database\Eloquent\Collection|User[] $localUsers */
        $localUsers = User::withTrashed()->get();
        $usersByEmail = $localUsers->keyBy('email');
        $usersByTc = $localUsers->keyBy('tc_no')->filter(fn($u, $k) => !empty($k));

        DB::transaction(function () use ($centralUsers, &$syncedCount, $usersByEmail, $usersByTc, $dummyPassword) {
            foreach ($centralUsers as $centralUser) {
                if (empty($centralUser['email'])) continue;

                // MYS'nin boş gönderdiği TC'yi e-postanın içinden ayıklıyoruz
                $incomingTc = $centralUser['tc_no'] ?? null;
                if (empty($incomingTc) && preg_match('/^[0-9]{10,11}@/', $centralUser['email'])) {
                    $incomingTc = explode('@', $centralUser['email'])[0];
                }

                // --- WORKFLOW EŞLEŞTİRME MANTIĞI ---
                // Sadece E-Posta veya TC Numarasına bakar. (İsim benzerliği aramaz)
                /** @var User|null $user */
                $user = $usersByEmail->get($centralUser['email']) ?? 
                        (!empty($incomingTc) ? $usersByTc->get($incomingTc) : null);

                if ($user) {
                    // 1. MEVCUT KULLANICIYI GÜNCELLE
                    if ($user->trashed()) {
                        $user->restore();
                    }

                    // Asıl hesaptaki düzgün (isim.soyisim) e-postanın, rakamlı çöp e-postayla ezilmesini engelle
                    $newEmail = $centralUser['email'];
                    if (preg_match('/^[0-9]{10,11}@/', $newEmail) && !preg_match('/^[0-9]{10,11}@/', $user->email ?? '')) {
                        $newEmail = $user->email; 
                    }

                    $user->update([
                        'tc_no'           => $incomingTc ?? $user->tc_no,
                        'registration_no' => $centralUser['registration_no'] ?? $user->registration_no,
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
                        'email'           => $centralUser['email'],
                        'password'        => $dummyPassword,
                        'tc_no'           => $incomingTc,
                        'registration_no' => $centralUser['registration_no'] ?? null,
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
