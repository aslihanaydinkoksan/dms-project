<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\FolderRolePermission; // Eklememiz gereken model

class RoleMatrixController extends Controller
{
    /**
     * 1. GLOBAL YETKİLERİ GÜNCELLE
     */
    public function updateGlobal(Request $request, Role $role)
    {
        // Spatie'de Sync yaparsak Menü yetkileri silinir! 
        // Bu yüzden sadece "Menu OLMAYAN" yetkileri alıp, var olan menu yetkileriyle birleştirip kaydediyoruz.
        
        $requestedGlobals = $request->input('permissions', []);
        $existingMenus = $role->permissions()->where('name', 'like', 'menu.%')->pluck('name')->toArray();
        
        $validGlobals = Permission::whereIn('name', $requestedGlobals)->pluck('name')->toArray();
        
        $role->syncPermissions(array_merge($validGlobals, $existingMenus));

        return back()->with('success', 'Global kalkan yetkileri başarıyla güncellendi.');
    }

    /**
     * 2. DOKÜMAN TİPİ MATRİSİNİ GÜNCELLE
     */
    public function updateDocumentType(Request $request, Role $role)
    {
        $permissions = $request->input('permissions', []);

        DB::transaction(function () use ($role, $permissions) {
            // Sadece bu role ait eski doküman tipi kurallarını sil
            DB::table('role_category_permissions')->where('role_id', $role->id)->delete();
            
            $insertData = [];
            foreach ($permissions as $categoryName => $actions) {
                $cleanCategoryName = str_replace('_', ' ', $categoryName);
                $insertData[] = [
                    'role_id' => $role->id,
                    'category' => $cleanCategoryName,
                    'can_view' => isset($actions['can_view']) ? 1 : 0,
                    'can_create' => isset($actions['can_create']) ? 1 : 0,
                    'can_edit' => isset($actions['can_edit']) ? 1 : 0,
                    'can_delete' => isset($actions['can_delete']) ? 1 : 0,
                    'can_manage_versions' => isset($actions['can_manage_versions']) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertData)) {
                DB::table('role_category_permissions')->insert($insertData);
            }
        });

        return back()->with('success', 'Doküman tipine özel yetki matrisi başarıyla güncellendi.');
    }

    /**
     * 3. MENÜ ERİŞİM MATRİSİNİ GÜNCELLE
     */
    public function updateMenu(Request $request, Role $role)
    {
        $requestedMenus = $request->input('menu_permissions', []);
        
        // Global yetkileri koruyarak sadece menüleri güncelliyoruz.
        $existingGlobals = $role->permissions()->where('name', 'not like', 'menu.%')->pluck('name')->toArray();
        $validMenus = Permission::whereIn('name', $requestedMenus)->pluck('name')->toArray();

        $role->syncPermissions(array_merge($existingGlobals, $validMenus));

        return back()->with('success', 'Menü ve navigasyon erişimleri güncellendi.');
    }

    /**
     * 4. KLASÖR YETKİ MATRİSİNİ GÜNCELLE (TAM UYUMLU YENİ METOT)
     */
    public function updateFolder(Request $request, Role $role)
    {
        // Gelen payload yapısı: ['folder_id_1' => ['can_view' => 1, 'can_upload' => 1], 'folder_id_2' => [...]]
        $folderPermissions = $request->input('folder_permissions', []);

        DB::transaction(function () use ($role, $folderPermissions) {
            // 1. Önce bu ROL'e ait tüm klasör yetkilerini temizle
            FolderRolePermission::where('role_id', $role->id)->delete();

            // 2. Seçili gelen klasör yetkilerini tabloya yaz
            $insertData = [];
            foreach ($folderPermissions as $folderId => $perms) {
                // Eğer hiçbir yetki seçilmemişse kaydetme (gereksiz satır oluşmasın)
                if (empty($perms)) {
                    continue; 
                }

                $insertData[] = [
                    'folder_id' => $folderId,
                    'role_id' => $role->id,
                    'can_view' => isset($perms['can_view']) ? 1 : 0,
                    'can_upload' => isset($perms['can_upload']) ? 1 : 0,
                    'can_create_subfolder' => isset($perms['can_create_subfolder']) ? 1 : 0,
                    'can_manage' => isset($perms['can_manage']) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertData)) {
                FolderRolePermission::insert($insertData);
            }
        });

        return back()->with('success', 'Klasör yetkileri başarıyla güncellendi.');
    }
}