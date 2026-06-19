<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentType;
use App\Models\Department;
use App\Models\Folder;
use App\Models\SystemSetting;
use Illuminate\Support\Str;
use Exception;

class PermissionSettingsService
{
    /**
     * Ayarlar sayfası için gerekli olan devasa veriyi hazırlar (Read Model)
     */
    public function getSettingsPageData(): array
    {
        $roles = Role::where('name', '!=', 'Super Admin')->orderBy('hierarchy_level', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $documentTypes = DocumentType::where('is_active', true)->orderBy('name')->get();
        $folders = Folder::orderBy('name')->get();

        $categories = DocumentType::where('is_active', true)->pluck('name')->toArray();
        $existingPermissions = DB::table('role_category_permissions')
            ->get()->groupBy('role_id')->map(fn($items) => $items->keyBy('category'));

        $privacyLevels = SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Departmana Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);

        $dynamicPrivacyPermissions = [];
        foreach ($privacyLevels as $key => $label) {
            if ($key !== 'public') {
                $dynamicPrivacyPermissions[] = 'document.view_' . $key;
            }
        }

        // ====================================================================
        // Config Dizi Düzleştirme (Flatten) Mantığı
        // ====================================================================
        $corePermissionsConfig = (array) config('dms.core_permissions', []);
        $flatCorePermissions = [];

        foreach ($corePermissionsConfig as $moduleKey => $moduleData) {
            // Eğer config 2 boyutluysa (Örn: 'task' => ['permissions' => ['task.view' => '...']])
            if (is_array($moduleData) && isset($moduleData['permissions']) && is_array($moduleData['permissions'])) {
                // Sadece anahtarları (task.view vb.) alıp düz diziye ekle
                $flatCorePermissions = array_merge($flatCorePermissions, array_keys($moduleData['permissions']));
            } else {
                // Eğer config eski düz usulde yazılmışsa (Geriye Dönük Uyumluluk)
                $flatCorePermissions = array_merge($flatCorePermissions, is_array($moduleData) ? $moduleData : [$moduleData]);
            }
        }

        // ====================================================================
        // Çekirdek İzinlerin Otomatik Aktivasyonu (Initialization)
        // ====================================================================
        // config/dms.php dosyasına eklenen yeni yetkiler DB'de yoksa otomatik oluşturulur.
        // Bu sayede sistem yöneticisi sayfayı açtığında matriste kör nokta oluşmaz.
        foreach ($flatCorePermissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web'
            ]);
        }

        $specialPermissions = Permission::whereIn('name', array_merge($flatCorePermissions, $dynamicPrivacyPermissions))->get();

        $expectedMenus = [];
        $routes = \Illuminate\Support\Facades\Route::getRoutes()->getRoutes();

        // Sistemdeki tüm rotaları ve kalkanları (middleware) tara
        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            foreach ($middlewares as $mw) {
                // Eğer kalkan 'can:menu.' ile başlıyorsa, bunu bir menü yetkisi olarak algıla
                if (is_string($mw) && str_starts_with($mw, 'can:menu.')) {
                    $permissionName = str_replace('can:', '', $mw);
                    if (!in_array($permissionName, $expectedMenus)) {
                        $expectedMenus[] = $permissionName;
                    }
                }
            }
        }
        foreach ($expectedMenus as $menuName) {
            Permission::firstOrCreate(['name' => $menuName, 'guard_name' => 'web']);
        }
        $menuPermissions = Permission::where('name', 'like', 'menu.%')->get();

        return compact('roles', 'categories', 'existingPermissions', 'specialPermissions', 'menuPermissions', 'departments', 'documentTypes', 'folders', 'privacyLevels');
    }

    /**
     * 3D Yetki Matrisini ve Özel İzinleri Veritabanına Yazar
     */
    public function updateMatrix(array $permissions, array $specialPermInput, array $menuPermissionsInput): void
    {
        DB::transaction(function () use ($permissions, $specialPermInput, $menuPermissionsInput) {

            // 1. 3D Matrisi Güncelle (Bulk Insert)
            DB::table('role_category_permissions')->delete();
            $insertData = [];

            foreach ($permissions as $roleId => $categories) {
                foreach ($categories as $categoryName => $actions) {
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

            if (count($insertData) > 0) {
                DB::table('role_category_permissions')->insert($insertData);
            }

            // 2. Spatie Özel ve Menü Yetkilerini Birleştir ve Senkronize Et
            $roles = Role::where('name', '!=', 'Super Admin')->get();

            foreach ($roles as $role) {
                /** @var Role $role */

                $roleSpecial = $specialPermInput[$role->id] ?? [];
                $roleMenu = $menuPermissionsInput[$role->id] ?? [];

                $allAssignedPerms = array_merge($roleSpecial, $roleMenu);
                $validPermissionsToSync = Permission::whereIn('name', $allAssignedPerms)->get();

                $role->syncPermissions($validPermissionsToSync);
            }
        });

        // Spatie Ön Belleğini Temizle
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Yeni Doküman Tipi ve Özel Alanları işleyip veritabanına kaydeder
     */
    public function createDocumentType(array $data): void
    {
        $fields = $this->processCustomFields($data['custom_fields'] ?? null);

        DocumentType::create([
            'name' => $data['name'],
            'department_id' => $data['department_id'] ?? null,
            'custom_fields' => empty($fields) ? null : $fields,
            'requires_expiration_date' => $data['requires_expiration_date'] ?? false,
            'is_form_based' => $data['is_form_based'] ?? false
        ]);
    }

    /**
     * Mevcut Doküman Tipini ve Özel Alanları günceller
     */
    public function updateDocumentType(DocumentType $documentType, array $data): void
    {
        $fields = $this->processCustomFields($data['custom_fields'] ?? null);

        $documentType->update([
            'name' => $data['name'],
            'department_id' => $data['department_id'] ?? null,
            'custom_fields' => empty($fields) ? null : $fields,
            'requires_expiration_date' => $data['requires_expiration_date'] ?? false,
            'is_form_based' => $data['is_form_based'] ?? false
        ]);
    }

    /**
     * Dinamik Gizlilik Seviyesini Sisteme Ekler (Hard-coded yasağına tam uyum)
     */
    public function createPrivacyLevel(string $key, string $label): void
    {
        $privacyLevels = SystemSetting::getByKey('privacy_levels', [
            'public' => 'Herkese Açık',
            'confidential' => 'Departmana Özel',
            'strictly_confidential' => 'Çok Gizli'
        ]);

        $privacyLevels[strtolower($key)] = $label;

        SystemSetting::updateOrCreate(
            ['key' => 'privacy_levels'],
            ['value' => $privacyLevels, 'description' => 'Sistemin Dinamik Gizlilik Seviyeleri']
        );

        Permission::firstOrCreate(['name' => 'document.view_' . strtolower($key)]);
    }

    /**
     * Gizlilik Seviyesini Sistemden Siler
     */
    public function deletePrivacyLevel(string $key): void
    {
        // DÜZELTME 3: config() dönüşünü (array) olarak zorluyoruz
        $corePrivacyLevels = (array) config('dms.security.core_privacy_levels', ['public', 'confidential', 'strictly_confidential']);

        if (in_array($key, $corePrivacyLevels)) {
            throw new Exception('Sistemin çekirdek gizlilik seviyeleri silinemez.');
        }

        $privacyLevels = SystemSetting::getByKey('privacy_levels', []);

        if (isset($privacyLevels[$key])) {
            unset($privacyLevels[$key]);
            SystemSetting::updateOrCreate(['key' => 'privacy_levels'], ['value' => $privacyLevels]);
        }
    }

    /**
     * Arayüzden gelen karmaşık dinamik form verisini temizler ve JSON'a hazırlar.
     */
    private function processCustomFields(?array $customFields): array
    {
        $fields = [];
        if ($customFields) {
            foreach ($customFields as $field) {
                if (!empty($field['label']) && !empty($field['name'])) {
                    $fields[] = [
                        'label' => $field['label'],
                        'name' => Str::slug($field['name'], '_'),
                        'type' => $field['type'] ?? 'text',
                        'placeholder' => $field['placeholder'] ?? '',
                        'required' => isset($field['required']) ? true : false,
                    ];
                }
            }
        }
        return $fields;
    }
}
