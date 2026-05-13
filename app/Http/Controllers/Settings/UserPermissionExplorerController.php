<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;


class UserPermissionExplorerController extends Controller
{
    /**
     * Ana Ekranı Render Eder
     */
    public function index(Request $request)
    {
        // Mutlak Güvenlik: Sadece Super Admin girebilir
        if (!Auth::user()->hasRole('Super Admin')) {
            abort(403, 'Bu ekrana sadece Super Admin yetkisine sahip kullanıcılar erişebilir.');
        }

        $departments = Department::orderBy('name')->get();
        $users = User::with('department', 'roles')->orderBy('name')->get();

        return view('settings.permission-explorer', compact('departments', 'users'));
    }

    /**
     * AJAX Endpoint: Seçilen kullanıcının RÖNTGENİNİ çeker
     */
    public function getUserDetails(User $user)
    {
        /** @var \App\Models\User $admin */
        $admin = Auth::user();

        if (!$admin->hasRole('Super Admin')) {
            return response()->json(['error' => 'Yetkisiz erişim.'], 403);
        }

        // 1. EAGER LOADING ZIRHI
        $user->load([
            'department',
            'roles.permissions',
            'permissions',
            'specificFolders.departments'
        ]);

        // 2. VEKALET BİLGİSİ
        $delegatorIds = $user->getActiveDelegatorIds();

        /** @var Collection<int, User> $delegators */
        $delegators = User::with('department', 'roles')->whereIn('id', $delegatorIds)->get();

        $formattedDelegators = [];
        foreach ($delegators as $d) {
            $formattedDelegators[] = [
                'name' => $d->name,
                'department' => $d->department ? $d->department->name : '',
                'roles' => $d->roles->pluck('name')->implode(', ')
            ];
        }

        // 3. TEHLİKELİ (KIRMIZI) YETKİLER
        $dangerousPermissions = ['document.view_all', 'document.manage_all', 'document.force_unlock'];

        /** @var \Illuminate\Support\Collection $allPerms */
        $allPerms = $user->getAllPermissions();
        $userDangerousPerms = $allPerms->whereIn('name', $dangerousPermissions)->pluck('name');

        // 4. İSTİSNAİ (DIŞ) KLASÖR ERİŞİMLERİ
        $userDeptId = $user->department_id;
        $formattedExternalFolders = [];

        /** @var Collection<int, \App\Models\Folder> $specificFolders */
        $specificFolders = $user->specificFolders;

        foreach ($specificFolders as $folder) {
            $isGlobal = $folder->departments->isEmpty();
            $isNotMyDept = !$folder->departments->contains('id', $userDeptId);

            if ($isGlobal || $isNotMyDept) {
                $formattedExternalFolders[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'prefix' => $folder->prefix,
                    'access_level' => $folder->pivot->access_level
                ];
            }
        }

        // 5. JSON OLARAK UI'A FIRLAT
        return response()->json([
            'basic_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department ? $user->department->name : 'Departman Atanmamış',
            ],
            'hierarchy_level' => $user->roles->max('hierarchy_level') ?? 0,
            'roles' => $user->roles->pluck('name'),
            'dangerous_permissions' => $userDangerousPerms,
            'delegators' => $formattedDelegators,
            'external_folders' => $formattedExternalFolders
        ]);
    }
}
