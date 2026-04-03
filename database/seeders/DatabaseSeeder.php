<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // İşlem sırası Foreign Key hatalarını önlemek için çok önemlidir:
        // 1. Önce departmanlar (Kullanıcıların bağlanacağı yer)
        // 2. Sonra Roller ve Yetkiler (Kullanıcılara atanacak güçler)
        // 3. En son Kullanıcılar
        
        $this->call([
            DepartmentSeeder::class,
            RoleAndPermissionSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
