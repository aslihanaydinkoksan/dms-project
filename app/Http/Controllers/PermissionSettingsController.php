<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentType;

class PermissionSettingsController extends Controller
{
    public function index()
    {
        // 1. Rolleri Al (Super Admin hariç, çünkü o zaten Tanrı modunda)
        $roles = Role::where('name', '!=', 'Super Admin')->orderBy('hierarchy_level', 'desc')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $documentTypes = DocumentType::where('is_active', true)->orderBy('name')->get();

        // 2. Kategori Matrisi (3D)
        $categories = ['Sözleşme', 'Vekaletname', 'İpotek/Rehin', 'Dava Dosyası'];
        $existingPermissions = DB::table('role_category_permissions')
            ->get()
            ->groupBy('role_id')
            ->map(function ($items) {
                return $items->keyBy('category');
            });

        // 3. Özel/Global Yetkiler (Spatie)
        $specialPermissions = Permission::whereIn('name', [
            'document.view_strictly_confidential',
            'document.view_all',
            'document.manage_all',
            'document.force_unlock'
        ])->get();

        return view('settings.permissions', compact('roles', 'categories', 'existingPermissions', 'specialPermissions', 'departments', 'documentTypes'));
    }

    public function update(Request $request)
    {
        DB::transaction(function () use ($request) {

            // --- 1. KISIM: 3D KATEGORİ MATRİSİNİ GÜNCELLE ---
            DB::table('role_category_permissions')->delete();
            $permissions = $request->input('permissions', []);
            $insertData = [];

            foreach ($permissions as $roleId => $categories) {
                foreach ($categories as $categoryName => $actions) {
                    $insertData[] = [
                        'role_id' => $roleId,
                        'category' => $categoryName,
                        'can_view' => isset($actions['can_view']) ? 1 : 0,
                        'can_create' => isset($actions['can_create']) ? 1 : 0,
                        'can_edit' => isset($actions['can_edit']) ? 1 : 0,
                        'can_delete' => isset($actions['can_delete']) ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (count($insertData) > 0) {
                DB::table('role_category_permissions')->insert($insertData);
            }

            // --- 2. KISIM: SPATIE ÖZEL (GLOBAL) YETKİLERİ GÜNCELLE ---
            $specialPermInput = $request->input('special_permissions', []);
            $roles = Role::where('name', '!=', 'Super Admin')->get();

            foreach ($roles as $role) {
                /** @var \Spatie\Permission\Models\Role $role */
                // Eğer formdan bu rol için işaretlenmiş yetkiler geldiyse onları senkronize et, gelmediyse boş dizi [] gönderip hepsini sil
                $assignedPermNames = $specialPermInput[$role->id] ?? [];

                // Sadece formda sunduğumuz "Özel Yetkiler" üzerinden işlem yap (rolün diğer sistem yetkilerini bozmamak için)
                $validPermissionsToSync = Permission::whereIn('name', $assignedPermNames)->get();

                // syncPermissions metodu Spatie'ye aittir ve var olanları ezip sadece gönderdiklerini yazar
                $role->syncPermissions($validPermissionsToSync);
            }
        });

        // Spatie Ön Belleğini Temizle (Kritik!)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'Tüm Yetki Matrisi ve Özel Güvenlik İzinleri başarıyla güncellendi.');
    }
    /**
     * Arayüzden sisteme yeni bir Rol (Role) ekler.
     */
    public function storeRole(Request $request)
    {
        // 1. Validasyon: Rol adı boş olamaz ve sistemde aynısı (unique) bulunamaz
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ], [
            'name.required' => 'Lütfen eklenecek rolün adını girin.',
            'name.unique' => 'Bu rol sistemde zaten kayıtlı. Lütfen farklı bir isim deneyin.'
        ]);

        // 2. Spatie ile rolü oluştur
        Role::create(['name' => $request->name]);

        // 3. Başarı mesajıyla geri dön
        return back()->with('success', '🛡️ Yeni rol (' . $request->name . ') başarıyla oluşturuldu. Artık bu role yetki atayabilirsiniz.');
    }
    /**
     * Mevcut bir rolün adını günceller.
     */
    public function updateRole(Request $request, Role $role)
    {
        // Güvenlik: Kök rollerin adı değiştirilemez
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return back()->with('error', 'Sistem için kritik olan kök rollerin adı değiştirilemez.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id
        ]);

        $oldName = $role->name;
        $role->update(['name' => $request->name]);

        return back()->with('success', "Rol ismi '$oldName' -> '{$request->name}' olarak güncellendi.");
    }

    /**
     * Bir rolü sistemden tamamen siler.
     */
    public function destroyRole(Role $role)
    {
        // Güvenlik: Kök roller silinemez
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return back()->with('error', 'Sistem için kritik olan kök roller silinemez.');
        }

        // Eğer bu role atanmış kullanıcılar varsa Spatie otomatik yönetir 
        // ama istersen burada $role->users()->count() kontrolü yapıp uyarı verebilirsin.

        $roleName = $role->name;
        $role->delete();

        return back()->with('success', "'$roleName' rolü ve bu role bağlı tüm yetki tanımlamaları silindi.");
    }
    /**
     * Departmanın zorunlu onay ayarını AJAX ile günceller.
     */
    public function toggleDepartmentApproval(Request $request, \App\Models\Department $department)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $department->update(['requires_approval_on_upload' => $request->is_active]);

        return response()->json([
            'success' => true,
            'message' => $department->name . ' departmanı için zorunlu onay kuralı güncellendi.'
        ]);
    }
    /**
     * Yeni bir Doküman Tipi ekler.
     */
    public function storeDocumentType(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255',
            'name' => 'required|string|max:255|unique:document_types,name'
        ]);

        // Model Observer'ımız (yukarıda yazdığımız created olayı) sayesinde 
        // Spatie yetkileri arkaplanda otomatik olarak üretilecek!
        DocumentType::create([
            'category' => $request->category,
            'name' => $request->name
        ]);

        return back()->with('success', '📄 Yeni doküman tipi ve bağlı yetkileri başarıyla oluşturuldu.');
    }

    /**
     * Mevcut bir Doküman Tipini ve Özel Form Alanlarını (Custom Fields) günceller.
     */
    public function updateDocumentType(Request $request, DocumentType $documentType)
    {
        $request->validate([
            'category' => 'required|string|max:255',
            'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
            'custom_fields' => 'nullable|array' // YENİ: Dinamik alanları dizi olarak bekle
        ]);

        $fields = [];

        // Eğer arayüzden özel alanlar gönderildiyse bunları temizleyip JSON'a hazırla
        if ($request->has('custom_fields')) {
            foreach ($request->custom_fields as $field) {
                // Boş satırları yoksay
                if (!empty($field['label']) && !empty($field['name'])) {
                    $fields[] = [
                        'label' => $field['label'],
                        // Sistem adını (name) otomatik olarak boşluksuz İngilizce karaktere (slug) çeviriyoruz ki hata olmasın
                        'name' => \Illuminate\Support\Str::slug($field['name'], '_'),
                        'type' => $field['type'],
                        'placeholder' => $field['placeholder'] ?? ''
                    ];
                }
            }
        }

        $documentType->update([
            'category' => $request->category,
            'name' => $request->name,
            'custom_fields' => empty($fields) ? null : $fields // Boşsa null yap, doluysa kaydet
        ]);

        return back()->with('success', 'Doküman tipi ve özel form alanları başarıyla güncellendi.');
    }

    /**
     * Doküman Tipini sistemden siler.
     */
    public function destroyDocumentType(DocumentType $documentType)
    {
        // Model Observer'ımız (deleted olayı) sayesinde Spatie yetkileri de silinecek.
        $documentType->delete();

        return back()->with('success', 'Doküman tipi ve buna bağlı tüm sistem yetkileri kalıcı olarak silindi.');
    }
    /**
     * Yeni bir departman ekler.
     */
    public function storeDepartment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
        ]);

        \App\Models\Department::create([
            'name' => $request->name,
            'unit' => $request->unit,
            'requires_approval_on_upload' => false // Varsayılan olarak kapalı gelsin
        ]);

        return back()->with('success', '🏢 Yeni departman başarıyla eklendi.');
    }

    /**
     * Mevcut bir departmanı günceller.
     */
    public function updateDepartment(Request $request, \App\Models\Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
        ]);

        $department->update([
            'name' => $request->name,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Departman bilgileri güncellendi.');
    }

    /**
     * Departmanı siler.
     */
    public function destroyDepartment(\App\Models\Department $department)
    {
        // Güvenlik: Bu departmanda kullanıcı varsa silinmesini engelle
        if ($department->users()->count() > 0) {
            return back()->with('error', 'Bu departmana kayıtlı personeller var! Önce personellerin departmanını değiştirin.');
        }

        $department->delete();
        return back()->with('success', 'Departman sistemden silindi.');
    }
}
