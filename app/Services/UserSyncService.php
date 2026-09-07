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

                if (!$incomingEmail) continue;

                // --- WORKFLOW PROJESİNDEKİ KUSURSUZ EŞLEŞTİRME MANTIĞI ---
                
                // 1. Önce e-posta ile bulmayı dene
                $user = User::withTrashed()->where('email', $incomingEmail)->first();

                // 2. E-posta ile bulunamadıysa (değişmiş olabilir), TC Kimlik No ile bulmayı dene
                if (!$user && !empty($incomingTc)) {
                    $user = User::withTrashed()->where('tc_no', $incomingTc)->first();
                }

                // 3. Hala bulunamadıysa, bu gerçekten yepyeni bir personeldir
                if (!$user) {
                    $user = new User();
                    $user->password = $dummyPassword;
                }

                // --- BİLGİLERİ GÜNCELLE VE KAYDET ---
                
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->name = $userData['name'];
                $user->email = $incomingEmail; // TC ile bulunmuşsa eski e-postayı yenisiyle ezer
                $user->tc_no = $incomingTc;    // Gelecekteki eşleşmeler için DB'ye işler
                $user->registration_no = $userData['registration_no'] ?? null;
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
