<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    public function index()
    {
        // Grupları, üye sayıları ve mevcut üyeleriyle birlikte (N+1 önlemiyle) çek
        $groups = UserGroup::with(['members' => function ($q) {
            $q->select('users.id', 'users.name', 'users.department_id')->with('department:id,name');
        }])->withCount('members')->latest()->get();

        // Modal içindeki TomSelect kullanıcı seçici için tüm aktif personeli çek
        $users = \App\Models\User::select('id', 'name', 'department_id')
            ->where('is_active', true)
            ->with('department:id,name')
            ->orderBy('name')
            ->get();

        return view('settings.user-groups.index', compact('groups', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_groups,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        UserGroup::create($validated);
        return back()->with('success', 'Kullanıcı grubu oluşturuldu.');
    }

    public function update(Request $request, UserGroup $userGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_groups,name,' . $userGroup->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $userGroup->update($validated);
        return back()->with('success', 'Grup başarıyla güncellendi.');
    }

    public function destroy(UserGroup $userGroup)
    {
        $userGroup->delete();
        return back()->with('success', 'Grup silindi.');
    }

    // Gruba üye ekleme/çıkarma işlemleri (AJAX veya Form için)
    public function syncMembers(Request $request, UserGroup $userGroup)
    {
        $request->validate([
            'members' => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.role' => 'required|in:manager,member'
        ]);

        $syncData = [];
        if ($request->has('members')) {
            foreach ($request->members as $member) {
                $syncData[$member['user_id']] = ['role' => $member['role']];
            }
        }

        $userGroup->members()->sync($syncData);
        return back()->with('success', 'Grup üyeleri güncellendi.');
    }
}
