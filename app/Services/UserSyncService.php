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
        // Ne olur ne olmaz diye PHP'nin zaman sınırını 5 dakikaya çıkarıyoruz (Büyük veriler için güvenlik önlemi)
        set_time_limit(300);

        $users = $this->mysApi->getAllUsers();
        $syncedCount = 0;

        // Bcrypt darboğazını aşmak için: Şifreyi döngü dışında SADECE BİR KEZ hashliyoruz.
        $dummyPassword = Hash::make(Str::random(16));

        DB::transaction(function () use ($users, &$syncedCount, $dummyPassword) {
            foreach ($users as $userData) {
                $user = User::firstOrNew(['email' => $userData['email']]);

                $user->name = $userData['name'];
                $user->department_id = $userData['department'] ? $userData['department']['id'] : null;
                $user->is_active = $userData['is_active'];

                if (!$user->exists) {
                    // Önceden oluşturulmuş tek şifreyi veriyoruz (Sıfır performans kaybı)
                    $user->password = $dummyPassword;
                }

                $user->save();
                $syncedCount++;
            }
        });

        return $syncedCount;
    }
}
