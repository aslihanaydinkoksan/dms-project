<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\User; // YENİ EKLENDİ
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // YENİ EKLENDİ

class FolderPermissionController extends Controller
{
    /**
     * AJAX için: Seçilen klasörün mevcut ROL yetkilerini JSON döner.
     */
    public function getPermissions(Folder $folder)
    {
        // Yetkileri role_id anahtarıyla formatlayarak gönderiyoruz ki JS kolay okusun
        return response()->json(
            $folder->rolePermissions->keyBy('role_id')
        );
    }

    /**
     * Formdan gelen ROL matris verilerini veritabanına yazar (Sync)
     */
    public function sync(Request $request, Folder $folder)
    {
        // YENİ ACL KİLİDİ: Sadece Adminler veya ACL yöneticileri Rol Matrisini değiştirebilir!
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin']) && !Auth::user()->can_manage_acl) {
            abort(403, 'Güvenlik ve Yetki Matrisini yönetme (ACL) izniniz bulunmamaktadır.');
        }

        $request->validate([
            'permissions' => 'nullable|array'
        ]);

        try {
            DB::transaction(function () use ($request, $folder) {
                // 1. Önce bu klasörün tüm eski yetkilerini temizle
                $folder->rolePermissions()->delete();

                // 2. Formdan seçili (checked) olarak gelen yeni yetkileri yaz
                if ($request->has('permissions')) {
                    foreach ($request->permissions as $roleId => $perms) {
                        $folder->rolePermissions()->create([
                            'role_id' => $roleId,
                            'can_view' => isset($perms['can_view']),
                            'can_upload' => isset($perms['can_upload']),
                            'can_create_subfolder' => isset($perms['can_create_subfolder']),
                            'can_manage' => isset($perms['can_manage']),
                        ]);
                    }
                }
            });

            return back()->with('success', '✅ ' . $folder->name . ' klasörünün rol yetkileri başarıyla güncellendi.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Klasör Yetki Matrisi Hatası: ' . $e->getMessage());
            return back()->with('error', 'Yetkiler kaydedilirken bir hata oluştu.');
        }
    }

    /**
     * YENİ: Klasöre yeni bir İSTİSNA KULLANICI yetkisi ekler (Granular ACL).
     */
    public function store(Request $request, Folder $folder)
    {
        // YENİ ACL KİLİDİ: Sadece Adminler veya ACL yöneticileri!
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin']) && !Auth::user()->can_manage_acl) {
            abort(403, 'Güvenlik ve Yetki Matrisini yönetme (ACL) izniniz bulunmamaktadır.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:read,upload,manage'
        ]);

        \App\Models\FolderUserPermission::updateOrCreate(
            ['user_id' => $request->user_id, 'folder_id' => $folder->id],
            ['access_level' => $request->access_level]
        );

        return back()->with('success', 'Kullanıcıya klasör için özel yetki başarıyla tanımlandı.');
    }

    /**
     * YENİ: Klasörden İSTİSNA KULLANICI yetkisini siler (Granular ACL).
     */
    public function destroy(Folder $folder, User $user)
    {
        // YENİ ACL KİLİDİ: Sadece Adminler veya ACL yöneticileri!
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin']) && !Auth::user()->can_manage_acl) {
            abort(403, 'Güvenlik ve Yetki Matrisini yönetme (ACL) izniniz bulunmamaktadır.');
        }

        $folder->specificUsers()->detach($user->id);

        return back()->with('success', 'Özel yetki başarıyla kaldırıldı.');
    }
}
