<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Önbelleği temizle
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Yetkileri Tanımla (Çift yazılanları sildik, listeyi temizledik)
        $permissions = [
            'document.create',
            'document.view_all', // Tüm belgeleri (çok gizliler hariç) görebilme
            'document.manage_all', // Tüm belgeleri kilitleme/düzenleme (Super Admin/Admin'e özel)
            'document.approve',
            'document.delete',
            'document.hard_delete',
            'user.manage',
            'department.manage',
            'system.settings',
            'system.destroy',
            'document.force_unlock',
            'document.view_strictly_confidential', // ÇOK GİZLİ belgeleri görme yetkisi
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Rütbeler ve "Gerçek" Yetki Dağılımı
        $rolesMatrix = [
            'Super Admin' => [
                'level' => 100,
                'permissions' => Permission::all() // Her şeye yetkili
            ],
            'Admin' => [
                'level' => 90,
                'permissions' => Permission::where('name', '!=', 'system.destroy')->get() // Kırmızı buton hariç her şey
            ],
            'Direktör' => [
                'level' => 80,
                // Direktörler departman yönetir, onay verir, geneli görebilir ve ÇOK GİZLİ'leri görebilir.
                // AMA "manage_all" ve "force_unlock" verilmez, belgelere müdahale yetkisi 3D matristen gelir!
                'permissions' => ['document.create', 'document.view_all', 'document.approve', 'document.delete', 'department.manage', 'document.view_strictly_confidential']
            ],
            'Müdür' => [
                'level' => 70,
                // Müdürler kullanıcı yönetebilir, geneli görebilir ama ÇOK GİZLİ'leri GÖREMEZ!
                'permissions' => ['document.create', 'document.view_all', 'document.approve', 'user.manage']
            ],
            'Departman Yöneticisi' => [
                'level' => 60,
                // Kendi departmanındaki akışları yönetir
                'permissions' => ['document.create', 'document.approve']
            ],
            'Standart Kullanıcı' => [
                'level' => 10,
                // SADECE belge yükleyebilir. Görme ve düzenleme hakları 3D Matris'ten veya doğrudan ona atanan akışlardan gelir!
                'permissions' => ['document.create']
            ],
        ];

        // 3. Veritabanına Yaz ve Senkronize Et
        foreach ($rolesMatrix as $roleName => $data) {
            $role = Role::firstOrNew(['name' => $roleName]);
            $role->hierarchy_level = $data['level'];
            $role->save();

            $role->syncPermissions($data['permissions']);
        }
    }
}
