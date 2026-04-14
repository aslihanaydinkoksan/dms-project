<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Kullanıcı listesini getirir.
     */
    public function index(): View
    {
        // N+1 sorgu problemini önlemek için ilişkileri baştan yüklüyoruz
        $users = User::with(['department', 'roles'])->latest()->paginate(15);
        return view('users.index', compact('users'));
    }

    /**
     * Yeni kullanıcı ekleme formunu gösterir.
     */
    public function create()
    {
        // Super Admin rolünü standart adminlerin listesinden gizle
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        if (Auth::user()->hasRole('Super Admin')) {
            $roles = Role::all(); // Sadece Super Admin diğer Super Adminleri görebilir/atayabilir
        }
        $departments = Department::orderBy('unit')->orderBy('name')->get()->groupBy('unit');
        return view('users.create', compact('roles', 'departments'));
    }

    /**
     * Yeni kullanıcıyı veritabanına kaydeder.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        // Güvenlik Kalkanı
        if (in_array('Super Admin', $request->roles) && !Auth::user()->hasRole('Super Admin')) {
            abort(403, 'Bu rolü atama yetkiniz yok.');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department_id' => $request->department_id,
            'is_active' => true
        ]);

        // Spatie Sync Roles (Önceki rolleri silip yenisini atar)
        $user->syncRoles($request->roles);

        return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    /**
     * Kullanıcı düzenleme formunu gösterir.
     */
    public function edit(User $user): View
    {
        $departments = Department::orderBy('unit')->orderBy('name')->get()->groupBy('unit');
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        if (Auth::user()->hasRole('Super Admin')) {
            $roles = Role::all();
        }
        // Kullanıcının mevcut rollerini array olarak alıyoruz (Checkbox'ları işaretlemek için)
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('users.edit', compact('user', 'departments', 'roles', 'userRoles'));
    }

    /**
     * Kullanıcı bilgilerini günceller.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $user) {
                $data = $request->validated();

                // Eğer şifre alanı doluysa yeni şifreyi hashle, boşsa eski şifreyi koru
                if (!empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }

                // Checkbox'lar işaretlenmemişse formdan false/null gelir.
                // Manuel olarak boolean'a çeviriyoruz ki veritabanına 0 veya 1 olarak yazılsın.
                $data['is_active'] = $request->has('is_active');

                // YENİ ACL KONTROLÜ: Sadece Super Admin veya Adminler birine bu yetkiyi verebilir!
                if (Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
                    $data['can_manage_acl'] = $request->has('can_manage_acl');
                } else {
                    // Admin değilse, kullanıcının mevcut ACL yetkisini koru (değiştirmesine izin verme)
                    unset($data['can_manage_acl']);
                }

                $user->update($data);

                // Spatie Rollerini Senkronize Et (Eskileri siler, yenileri ekler)
                $user->syncRoles($request->roles ?? []);
            });

            return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla güncellendi.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Kullanıcı güncellenemedi: ' . $e->getMessage());
        }
    }
    /**
     * Kullanıcıyı sistemden siler (Soft Delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        // Güvenlik: Kullanıcı kendini silemez!
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        try {
            $user->delete(); // Soft delete uygulanır
            return back()->with('success', 'Kullanıcı sistemden başarıyla silindi.');
        } catch (Exception $e) {
            return back()->with('error', 'Kullanıcı silinemedi: ' . $e->getMessage());
        }
    }
}
