<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuPermissionSeeder extends Seeder
{
    public function run()
    {
        // Ana menü yetkileri
        $menus = [
            'menu.dashboard',
            'menu.documents',
            'menu.folders',
            'menu.reports',
            'menu.settings',
            'menu.users'
        ];

        foreach ($menus as $menu) {
            Permission::firstOrCreate(['name' => $menu, 'guard_name' => 'web']);
        }

        // Opsiyonel: Admin rolüne varsayılan olarak hepsini verelim
        /** @var \Spatie\Permission\Models\Role $adminRole */
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($menus);
        }
    }
}
