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

        DB::transaction(function () use ($users, &$syncedCount, $dummyPassword) {
            foreach ($users as $userData) {
                $user = null;
                $incomingEmail = $userData['email'] ?? null;
                $incomingTc = $userData['tc_no'] ?? null;
                $incomingRegNo = $userData['registration_no'] ?? null;

                if (!$incomingEmail) continue;

                // 1. Önce e-posta ile bulmayı dene
                $user = User::withTrashed()->where('email', $incomingEmail)->first();

                // 2. E-posta ile bulunamadıysa, TC Kimlik No ile bulmayı dene
                if (!$user && !empty($incomingTc)) {
                    $user = User::withTrashed()->where('tc_no', $incomingTc)->first();
                }

                // 3. Hala bulunamadıysa, yepyeni bir personeldir
                if (!$user) {
                    $user = new User();
                    $user->password = $dummyPassword;
                }

                // --- ÇAKIŞMA (CONFLICT) ÇÖZÜCÜ MİMARİ ---

                // TC numarası güncellenecek ama bu TC veritabanında başka bir (eski/unutulmuş) hesapta asılı kalmış olabilir
                if (!empty($incomingTc) && $user->tc_no !== $incomingTc) {
                    $conflictTcUser = User::withTrashed()->where('tc_no', $incomingTc)->where('id', '!=', $user->id)->first();
                    if ($conflictTcUser) {
                        // Çakışan eski kullanıcının TC'sini boşa çıkar ki UNIQUE constraint patlamasın
                        $conflictTcUser->update(['tc_no' => null]);
                    }
                }

                // Sicil numarası için de aynı koruma (Eğer MYS'den geliyorsa)
                if (!empty($incomingRegNo) && $user->registration_no !== $incomingRegNo) {
                    $conflictRegUser = User::withTrashed()->where('registration_no', $incomingRegNo)->where('id', '!=', $user->id)->first();
                    if ($conflictRegUser) {
                        $conflictRegUser->update(['registration_no' => null]);
                    }
                }

                // E-Posta güncellenecek ama bu E-Posta başka hesapta kalmış olabilir (Örn: TC'den bulduk, e-postayı ezeceğiz)
                if (!empty($incomingEmail) && $user->email !== $incomingEmail) {
                    $conflictEmailUser = User::withTrashed()->where('email', $incomingEmail)->where('id', '!=', $user->id)->first();
                    if ($conflictEmailUser) {
                        $conflictEmailUser->update(['email' => 'conflict_' . time() . '_' . $conflictEmailUser->email]);
                    }
                }

                // --- BİLGİLERİ GÜNCELLE VE KAYDET ---

                if ($user->trashed()) {
                    $user->restore();
                }

                $user->name = $userData['name'];
                $user->email = $incomingEmail; // TC ile bulunmuşsa eski e-postayı yenisiyle ezer
                $user->tc_no = $incomingTc;    // Çakışma yukarıda çözüldüğü için artık güvenle yazılabilir
                $user->registration_no = $incomingRegNo;
                $user->is_active = $userData['is_active'] ?? true;

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
