<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    /**
     * Departmanlar listesini getirir.
     */
    public function index()
    {
        // Birime (unit) ve isme göre sıralayarak getiriyoruz.
        $departments = Department::orderBy('unit')->orderBy('name')->get();

        return view('admin.departments.index', compact('departments'));
    }

    /**
     * Yeni departman ekler.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
        ]);

        Department::create(array_merge($validated, ['requires_approval_on_upload' => false]));

        return back()->with('success', '🏢 Yeni departman başarıyla eklendi.');
    }

    /**
     * Departman bilgilerini günceller.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
        ]);

        $department->update($validated);

        return back()->with('success', 'Departman bilgileri başarıyla güncellendi.');
    }

    /**
     * Departmanı siler (Personel kontrolü yaparak).
     */
    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Bu departmana kayıtlı personeller var! Önce personellerin departmanını değiştirin.');
        }

        $department->delete();

        return back()->with('success', 'Departman sistemden başarıyla silindi.');
    }

    /**
     * AJAX ile departman bazlı belge yükleme onay zorunluluğunu (Toggle) günceller.
     */
    public function toggleApproval(Request $request, Department $department)
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);

        $department->update(['requires_approval_on_upload' => $validated['is_active']]);

        return response()->json([
            'success' => true,
            'message' => "{$department->name} departmanı için zorunlu onay kuralı güncellendi."
        ]);
    }
}
