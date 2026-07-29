<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

class UserController extends Controller
{
    /**
     * Bağımlılık Enjeksiyonu
     */
    public function __construct(protected UserService $userService) {}

    public function index(Request $request): View
    {
        // Gelen filtre parametrelerini dizi olarak al
        $filters = $request->only(['q', 'department_id', 'role', 'status']);

        $users = User::with(['department', 'roles'])
            ->advancedFilter($filters) // Yeni yazdığımız scope'u çağırdık
            ->orderBy('name', 'asc')
            ->paginate(50)
            ->withQueryString(); // Sayfalama butonlarının filtreleri unutmaması için kritik

        // Dropdown menüleri doldurmak için gerekli veriler
        $departments = Department::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'departments', 'roles'));
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('profile.show', $user->id);
    }

    public function create(): View
    {
        $roles = Auth::user()->hasRole('Super Admin')
            ? Role::all()
            : Role::where('name', '!=', 'Super Admin')->get();

        $departments = Department::orderBy('unit')->orderBy('name')->get()->groupBy('unit');

        return view('users.create', compact('roles', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        if (in_array('Super Admin', $validated['roles']) && !Auth::user()->hasRole('Super Admin')) {
            abort(403, 'Bu rolü atama yetkiniz yok.');
        }

        $this->userService->createUser($validated, $validated['roles']);

        return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    public function edit(User $user): View
    {
        $departments = Department::orderBy('unit')->orderBy('name')->get()->groupBy('unit');

        $roles = Auth::user()->hasRole('Super Admin')
            ? Role::all()
            : Role::where('name', '!=', 'Super Admin')->get();

        $userRoles = $user->roles->pluck('name')->toArray();

        return view('users.edit', compact('user', 'departments', 'roles', 'userRoles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->has('is_active');
            $data['can_manage_acl'] = $request->has('can_manage_acl');

            $hasAclPermission = Auth::user()->hasAnyRole(['Super Admin', 'Admin']);

            $this->userService->updateUser($user, $data, $request->input('roles', []), $hasAclPermission);

            return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla güncellendi.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Kullanıcı güncellenemedi: ' . $e->getMessage());
        }
    }

    public function destroy(User $user): RedirectResponse
    {
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

    /**
     * ONAY BEKLEYENLER SAYFASI
     */
    public function onayBekleyenler(): View
    {
        $bekleyenler = User::with('department')->where('is_active', false)->latest()->get();
        $departments = Department::orderBy('name')->get();
        $roles = Role::where('name', '!=', 'Super Admin')->get();

        return view('users.bekleyen_basvurular', compact('bekleyenler', 'departments', 'roles'));
    }

    /**
     * BAŞVURUYU ONAYLA VE MERKEZE BİLDİR
     * DİKKAT: P1132 Hatası çözümü için 'int $id' tip belirtimi eklendi!
     */
    public function basvuruOnayla(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'role' => 'required',
            'department_id' => 'required|exists:departments,id'
        ]);

        try {
            // İş mantığı ve Dış API çağrısı tamamen Servise devredildi.
            $user = $this->userService->approveApplication($id, $validated);

            return redirect()->route('users.onay_bekleyenler')
                ->with('success', $user->name . ' başarıyla onaylandı ve Merkez ile eşitlendi!');
        } catch (Exception $e) {
            return redirect()->route('users.onay_bekleyenler')
                ->with('error', "Kullanıcı DMS'te onaylandı FAKAT Merkez API güncellenemedi! Sebep: " . $e->getMessage());
        }
    }
}
