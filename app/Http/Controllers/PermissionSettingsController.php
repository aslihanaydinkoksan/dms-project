<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PermissionSettingsService;
use Spatie\Permission\Models\Role;
use App\Models\DocumentType;
use App\Models\Department;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\RedirectResponse;

class PermissionSettingsController extends Controller
{
    /**
     * Bağımlılık Enjeksiyonu
     */
    public function __construct(protected PermissionSettingsService $settingsService) {}

    public function index()
    {
        // Tüm View datası Servis tarafından tek paket halinde hazırlanır
        $data = $this->settingsService->getSettingsPageData();
        return view('settings.permissions', $data);
    }

    public function update(Request $request)
    {
        try {
            $this->settingsService->updateMatrix(
                $request->input('permissions', []),
                $request->input('special_permissions', []),
                $request->input('menu_permissions', [])
            );

            return back()->with('success', 'Tüm Yetki Matrisi ve Özel Güvenlik İzinleri başarıyla güncellendi.');
        } catch (Exception $e) {
            Log::error('Matris Kayıt Hatası: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage()]);
        }
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'hierarchy_level' => 'required|integer|min:0'
        ]);

        Role::create($validated);

        return back()->with('success', "🛡️ Yeni rol ({$validated['name']}) başarıyla oluşturuldu.");
    }

    public function updateRole(Request $request, Role $role)
    {
        // DÜZELTME: config() dönüşü (array) olarak zorlandı (Defensive Programming)
        $protectedRoles = (array) config('dms.security.protected_roles', ['Super Admin', 'Admin']);

        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', 'Sistem için kritik olan kök rollerin adı değiştirilemez.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'hierarchy_level' => 'required|integer|min:0'
        ]);

        $role->update($validated);

        return back()->with('success', 'Rol başarıyla güncellendi.');
    }

    public function destroyRole(Role $role)
    {
        // DÜZELTME: config() dönüşü (array) olarak zorlandı (Defensive Programming)
        $protectedRoles = (array) config('dms.security.protected_roles', ['Super Admin', 'Admin']);

        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', 'Sistem için kritik olan kök roller silinemez.');
        }

        $roleName = $role->name;
        $role->delete();

        return back()->with('success', "'{$roleName}' rolü ve bu role bağlı tüm yetki tanımlamaları silindi.");
    }

    public function toggleDepartmentApproval(Request $request, Department $department)
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $department->update(['requires_approval_on_upload' => $validated['is_active']]);

        return response()->json([
            'success' => true,
            'message' => "{$department->name} departmanı için zorunlu onay kuralı güncellendi."
        ]);
    }

    /**
     * Yeni doküman tipi ve şablonunu sisteme kaydeder.
     */
    public function storeDocumentType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
            'department_id' => 'nullable|integer|exists:departments,id',
            'custom_fields' => 'nullable|array',
            'requires_expiration_date' => 'nullable|boolean',
            'is_form_based' => 'nullable|boolean' // YENİ: Validasyon kuralı enjeksiyonu
        ]);

        // Checkbox güvenliği: Gönderilmediyse false (0) olmasını garanti altına alıyoruz
        $validated['is_form_based'] = $request->boolean('is_form_based');

        $this->settingsService->createDocumentType($validated);

        return back()->with('success', '📄 Yeni doküman tipi ve özel form alanları başarıyla oluşturuldu.');
    }

    /**
     * Mevcut doküman tipini ve şablon özelliklerini günceller.
     */
    public function updateDocumentType(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
            'department_id' => 'nullable|integer|exists:departments,id',
            'custom_fields' => 'nullable|array',
            'requires_expiration_date' => 'nullable|boolean',
            'is_form_based' => 'nullable|boolean' // YENİ: Validasyon kuralı enjeksiyonu
        ]);

        // Checkbox güvenliği: Gönderilmediyse false (0) olmasını garanti altına alıyoruz
        $validated['is_form_based'] = $request->boolean('is_form_based');

        $this->settingsService->updateDocumentType($documentType, $validated);

        return back()->with('success', 'Doküman tipi ve özel form alanları başarıyla güncellendi.');
    }

    /**
     * Doküman tipini kalıcı olarak siler.
     */
    public function destroyDocumentType(DocumentType $documentType): RedirectResponse
    {
        $documentType->delete();
        return back()->with('success', 'Doküman tipi ve buna bağlı tüm sistem yetkileri kalıcı olarak silindi.');
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
        ]);

        Department::create(array_merge($validated, ['requires_approval_on_upload' => false]));

        return back()->with('success', '🏢 Yeni departman başarıyla eklendi.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
        ]);

        $department->update($validated);
        return back()->with('success', 'Departman bilgileri güncellendi.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Bu departmana kayıtlı personeller var! Önce personellerin departmanını değiştirin.');
        }

        $department->delete();
        return back()->with('success', 'Departman sistemden silindi.');
    }

    public function storePrivacyLevel(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|alpha_dash|max:50',
            'label' => 'required|string|max:255'
        ]);

        $this->settingsService->createPrivacyLevel($validated['key'], $validated['label']);

        return back()->with('success', "🛡️ Yeni gizlilik seviyesi ({$validated['label']}) başarıyla eklendi.");
    }

    public function destroyPrivacyLevel(string $key)
    {
        try {
            $this->settingsService->deletePrivacyLevel($key);
            return back()->with('success', 'Gizlilik seviyesi başarıyla silindi.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
