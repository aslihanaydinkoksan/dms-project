<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Önce Bilgi Teknolojileri departmanını bulalım
        $itDepartment = Department::where('name', 'Bilgi Teknolojileri')->first();

        // Admin kullanıcısını oluşturalım (Eğer varsa üzerine yazmaz, firstOrCreate)
        $admin = User::firstOrCreate(
            ['email' => 'admin@koksan.com'], // Bu e-posta ile arar
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'department_id' => $itDepartment ? $itDepartment->id : null,
                'is_active' => true,
            ]
        );

        // Spatie üzerinden Super Admin rolünü atayalım
        // assignRole metodu Spatie'nin HasRoles trait'inden gelir.
        if (!$admin->hasRole('Super Admin')) {
            $admin->assignRole('Super Admin');
        }
    }
}