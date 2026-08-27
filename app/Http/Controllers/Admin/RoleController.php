<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\DocumentType;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('name', '!=', 'Super Admin')
            ->orderBy('hierarchy_level', 'desc')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'hierarchy_level' => 'required|integer|min:0'
        ]);

        Role::create(['name' => $validated['name'], 'hierarchy_level' => $validated['hierarchy_level']]);

        return back()->with('success', "🛡️ Yeni rol ({$validated['name']}) başarıyla oluşturuldu.");
    }

    public function update(Request $request, Role $role)
    {
        $protectedRoles = (array) config('dms.security.protected_roles', ['Super Admin', 'Admin']);

        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', 'Sistem için kritik olan kök rollerin adı değiştirilemez.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'hierarchy_level' => 'required|integer|min:0'
        ]);

        $role->update($validated);

        return back()->with('success', 'Rol bilgileri başarıyla güncellendi.');
    }

    public function destroy(Role $role)
    {
        $protectedRoles = (array) config('dms.security.protected_roles', ['Super Admin', 'Admin']);

        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', 'Sistem için kritik olan kök roller silinemez.');
        }

        $roleName = $role->name;
        $role->delete();

        return back()->with('success', "'{$roleName}' rolü ve bu role bağlı tüm yetki tanımlamaları silindi.");
    }

    /**
     * MATRİS DETAY SAYFASI (Sekmeli UI İçin Veri Hazırlığı)
     */
    public function edit(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Super Admin rolünün matrisi değiştirilemez.');
        }

        // 1. Global İzinler
        $specialPermissions = Permission::where('name', 'not like', 'menu.%')->get();

        // 2. Menü İzinleri
        $menuPermissions = Permission::where('name', 'like', 'menu.%')->get();

        // 3. Doküman Tipleri
        $documentTypes = DocumentType::where('is_active', true)->orderBy('name')->get();
        $docTypeMatrix = DB::table('role_category_permissions')->where('role_id', $role->id)->get()->keyBy('category');

        // 4. Klasörler
        $folders = Folder::orderBy('name')->get();

        return view('admin.roles.edit', compact(
            'role',
            'specialPermissions',
            'menuPermissions',
            'documentTypes',
            'docTypeMatrix',
            'folders'
        ));
    }
}
