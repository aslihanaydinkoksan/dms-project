<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class UserService
{
    /**
     * Yeni kullanıcı oluşturur ve rollerini eşitler
     */
    public function createUser(array $data, array $roles): User
    {
        return DB::transaction(function () use ($data, $roles) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'department_id' => $data['department_id'] ?? null,
                'is_active' => true
            ]);

            $user->syncRoles($roles);

            return $user;
        });
    }

    /**
     * Kullanıcı bilgilerini ve yetkilerini günceller
     */
    public function updateUser(User $user, array $data, array $roles, bool $hasAclPermission = false): void
    {
        DB::transaction(function () use ($user, $data, $roles, $hasAclPermission) {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']); // Şifre boşsa güncellemeyi iptal et
            }

            // Sadece yetkili kişiler ACL (Erişim Kontrol Listesi) yönetimi yapabilir
            if (!$hasAclPermission) {
                unset($data['can_manage_acl']);
            }

            $user->update($data);
            $user->syncRoles($roles);
        });
    }

    /**
     * Bekleyen bir kullanıcı başvurusunu onaylar, departmanını atar ve Merkezi SSO'ya bildirir (Webhook)
     */
    public function approveApplication(int $userId, array $data): User
    {
        $user = User::findOrFail($userId);

        $user->is_active = true;
        $user->department_id = $data['department_id'];
        $user->save();

        $user->syncRoles([$data['role']]);

        // === MERKEZİ API (SSO) ENTEGRASYONU ===
        $apiKey = env('CENTRAL_SSO_API_KEY');
        $centralUrl = rtrim(env('CENTRAL_SSO_URL'), '/');

        $response = Http::timeout(5)->withHeaders([
            'X-App-Key' => $apiKey,
            'Accept' => 'application/json'
        ])->post($centralUrl . '/api/internal/uygulama-basvuru-durum', [
            'email' => $user->email,
            'status' => 'approved'
        ]);

        if ($response->failed()) {
            $errorDetail = $response->json('message') ?? $response->body();
            // Hatayı fırlatıyoruz ki Controller catch bloğunda yakalayıp kullanıcıya mesaj göstersin
            throw new Exception($errorDetail);
        }

        return $user;
    }
}
