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
        // 1. Rolleri Al
        $roles = Role::where('name', '!=', 'Super Admin')->orderBy('hierarchy_level', 'desc')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $documentTypes = DocumentType::where('is_active', true)->orderBy('name')->get();
        $folders = \App\Models\Folder::orderBy('name')->get();

        // 2. Kategori Matrisi (3D)
        $categories = DocumentType::where('is_active', true)->pluck('name')->toArray();
        $existingPermissions = DB::table('role_category_permissions')
            ->get()->groupBy('role_id')->map(function ($items) {
                return $items->keyBy('category');
            });

        // YENİ: Gizlilik Seviyelerini Çek ve Yetki İsimlerine Dönüştür
        $privacyLevels = \App\Models\SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Departmana Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);

        $dynamicPrivacyPermissions = [];
        foreach ($privacyLevels as $key => $label) {
            if ($key !== 'public') { // Herkese açık için yetkiye gerek yok
                $dynamicPrivacyPermissions[] = 'document.view_' . $key;
            }
        }

        // 3. Özel/Global Yetkiler (Spatie)
        $specialPermissions = Permission::whereIn('name', array_merge([
            'document.view_all',
            'document.manage_all',
            'document.force_unlock',
            'notify.global'
        ], $dynamicPrivacyPermissions))->get();

        $menuPermissions = Permission::where('name', 'like', 'menu.%')->get();

        return view('settings.permissions', compact('roles', 'categories', 'existingPermissions', 'specialPermissions', 'menuPermissions', 'departments', 'documentTypes', 'folders', 'privacyLevels'));
    }

    public function update(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {

                // =========================================================
                // 1. KISIM: 3D KATEGORİ MATRİSİNİ GÜNCELLE
                // =========================================================
                // Formdan gelen veriler tam senkronizasyon gerektirir. 
                // İşareti kaldırılanlar gelmeyeceği için önce tabloyu sıfırlıyoruz.
                DB::table('role_category_permissions')->delete();

                $permissions = $request->input('permissions', []);
                $insertData = [];

                foreach ($permissions as $roleId => $categories) {
                    foreach ($categories as $categoryName => $actions) {

                        // PHP'nin boşlukları alt çizgiye çevirme huyunu yeniyoruz!
                        // "Hukuk_Belgeleri" -> "Hukuk Belgeleri" olarak geri çevirip DB'ye öyle yazıyoruz.
                        $cleanCategoryName = str_replace('_', ' ', $categoryName);

                        $insertData[] = [
                            'role_id' => $roleId,
                            'category' => $cleanCategoryName,
                            'can_view' => isset($actions['can_view']) ? 1 : 0,
                            'can_create' => isset($actions['can_create']) ? 1 : 0,
                            'can_edit' => isset($actions['can_edit']) ? 1 : 0,
                            'can_delete' => isset($actions['can_delete']) ? 1 : 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Toplu halde (Bulk Insert) veritabanına yazıyoruz (Maksimum Performans)
                if (count($insertData) > 0) {
                    DB::table('role_category_permissions')->insert($insertData);
                }

                // =========================================================
                // 2. KISIM: SPATIE ÖZEL VE MENÜ YETKİLERİNİ GÜVENLE BİRLEŞTİR
                // =========================================================
                $specialPermInput = $request->input('special_permissions', []);
                $menuPermissionsInput = $request->input('menu_permissions', []);
                $roles = Role::where('name', '!=', 'Super Admin')->get();

                foreach ($roles as $role) {
                    /** @var \Spatie\Permission\Models\Role $role */

                    // Kırmızı Çizgi (Global) İzinleri Al
                    $roleSpecial = $specialPermInput[$role->id] ?? [];
                    // Menü İzinlerini Al
                    $roleMenu = $menuPermissionsInput[$role->id] ?? [];

                    // Her iki diziyi TEK bir havuzda birleştiriyoruz ki birbirlerini ezmesinler!
                    $allAssignedPerms = array_merge($roleSpecial, $roleMenu);

                    // Sadece sistemde gerçekten var olan yetkileri filtrele
                    $validPermissionsToSync = Permission::whereIn('name', $allAssignedPerms)->get();

                    // Tek seferde, güvenle Spatie'ye teslim et
                    $role->syncPermissions($validPermissionsToSync);
                }
            });

            // Spatie Ön Belleğini Temizle (Kritik!)
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return back()->with('success', 'Tüm Yetki Matrisi ve Özel Güvenlik İzinleri başarıyla güncellendi.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Matris Kayıt Hatası: ' . $e->getMessage());
            return back()->withInput()->withErrors(['name' => __('Kayıt sırasında bir hata oluştu: ') . $e->getMessage()]);
        }
    }
    /**
     * Arayüzden sisteme yeni bir Rol (Role) ekler.
     */
    public function storeRole(Request $request)
    {
        // 1. Validasyon: Rol adı boş olamaz ve sistemde aynısı (unique) bulunamaz
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'hierarchy_level' => 'required|integer|min:0'
        ], [
            'name.required' => 'Lütfen eklenecek rolün adını girin.',
            'name.unique' => 'Bu rol sistemde zaten kayıtlı. Lütfen farklı bir isim deneyin.',
            'hierarchy_level' => $request->hierarchy_level < 0 ? 'Hiyerarşi seviyesi negatif olamaz.' : 'Hiyerarşi seviyesi gereklidir.'
        ]);

        // 2. Spatie ile rolü oluştur
        Role::create(['name' => $request->name, 'hierarchy_level' => $request->hierarchy_level]);

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
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'hierarchy_level' => 'required|integer|min:0'
        ]);

        $oldName = $role->name;
        $role->update(['name' => $request->name, 'hierarchy_level' => $request->hierarchy_level]);

        return back()->with('success', "Rol başarıyla güncellendi.");
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
     * Yeni bir Doküman Tipi ekler ve Özel Form Alanlarını bağlar.
     */
    public function storeDocumentType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
            'custom_fields' => 'nullable|array' // YENİ
        ]);

        // Gelen dinamik alanları temizle ve hazırla
        $fields = $this->processCustomFields($request->custom_fields);

        // Model Observer'ımız (created olayı) Spatie yetkilerini otomatik üretecek!
        DocumentType::create([
            'name' => $request->name,
            'custom_fields' => empty($fields) ? null : $fields ,
            'requires_expiration_date' => $request->has('requires_expiration_date')
        ]);

        return back()->with('success', '📄 Yeni doküman tipi ve özel form alanları başarıyla oluşturuldu.');
    }

    /**
     * Mevcut bir Doküman Tipini ve Özel Form Alanlarını (Custom Fields) günceller.
     */
    public function updateDocumentType(Request $request, DocumentType $documentType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
            'custom_fields' => 'nullable|array'
        ]);

        // Gelen dinamik alanları temizle ve hazırla
        $fields = $this->processCustomFields($request->custom_fields);

        $documentType->update([
            'name' => $request->name,
            'custom_fields' => empty($fields) ? null : $fields,
            'requires_expiration_date' => $request->has('requires_expiration_date')
        ]);

        return back()->with('success', 'Doküman tipi ve özel form alanları başarıyla güncellendi.');
    }
    /**
     * YARDIMCI METOT: Arayüzden gelen karmaşık dinamik form verisini temizler ve JSON'a hazırlar.
     */
    private function processCustomFields(?array $customFields): array
    {
        $fields = [];

        if ($customFields) {
            foreach ($customFields as $field) {
                // Sadece adı ve etiketi dolu olan geçerli satırları al
                if (!empty($field['label']) && !empty($field['name'])) {
                    $fields[] = [
                        'label' => $field['label'],
                        // Sistem adını (name) otomatik olarak boşluksuz karaktere (slug) çeviriyoruz
                        'name' => \Illuminate\Support\Str::slug($field['name'], '_'),
                        'type' => $field['type'] ?? 'text',
                        'placeholder' => $field['placeholder'] ?? '',
                        // Checkbox işaretliyse true, değilse false kaydet!
                        'required' => isset($field['required']) ? true : false,
                    ];
                }
            }
        }

        return $fields;
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
    /**
     * Yeni bir Gizlilik Seviyesi Ekler
     */
    public function storePrivacyLevel(Request $request)
    {
        $request->validate([
            'key' => 'required|string|alpha_dash|max:50', // Sadece harf, rakam, tire ve altçizgi (Örn: board_only)
            'label' => 'required|string|max:255'
        ]);

        // Mevcut ayarları çek
        $privacyLevels = \App\Models\SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Departmana Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);

        // Yeni ayarı diziye ekle
        $privacyLevels[strtolower($request->key)] = $request->label;

        // Veritabanına kaydet (JSON olarak)
        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'privacy_levels'],
            ['value' => $privacyLevels, 'description' => 'Sistemin Dinamik Gizlilik Seviyeleri']
        );
        Permission::firstOrCreate(['name' => 'document.view_' . strtolower($request->key)]);

        return back()->with('success', '🛡️ Yeni gizlilik seviyesi (' . $request->label . ') başarıyla eklendi.');
    }

    /**
     * Mevcut bir Gizlilik Seviyesini Siler
     */
    public function destroyPrivacyLevel($key)
    {
        // Güvenlik: Çekirdek (Sistem) gizlilik seviyeleri silinemez
        if (in_array($key, ['public', 'confidential', 'strictly_confidential'])) {
            return back()->with('error', 'Sistemin çekirdek gizlilik seviyeleri silinemez.');
        }

        $privacyLevels = \App\Models\SystemSetting::getByKey('privacy_levels', []);

        // Eğer key dizide varsa sil ve tekrar kaydet
        if (isset($privacyLevels[$key])) {
            unset($privacyLevels[$key]);

            \App\Models\SystemSetting::updateOrCreate(
                ['key' => 'privacy_levels'],
                ['value' => $privacyLevels]
            );
        }

        return back()->with('success', 'Gizlilik seviyesi başarıyla silindi.');
    }
}
