<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    /**
     * ANA DİZİN: Sadece Parent'ı olmayan kök klasörleri getirir.
     */
    public function index()
    {
        // Klasörleri çekerken departments ilişkisini de alıyoruz (N+1 problemi olmasın diye)
        $folders = Folder::visibleTo(Auth::user())
            ->whereNull('parent_id')
            ->with('departments')
            ->orderBy('name')
            ->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('folders.index', compact('folders', 'departments'));
    }

    /**
     * KLASÖR İÇİ: Alt klasörleri ve içindeki belgeleri getirir.
     */
    public function show(Folder $folder)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $folder->load('departments');

        // --- VEKALET KİMLİK KARTLARINI ÇIKART ---
        $delegatorIds = $user->getActiveDelegatorIds();
        $allUserIds = array_merge([$user->id], $delegatorIds);
        $delegators = \App\Models\User::with('roles')->whereIn('id', $delegatorIds)->get();

        $allDeptIds = array_filter(array_merge([$user->department_id], $delegators->pluck('department_id')->toArray()));
        $allRoleIds = array_merge($user->roles->pluck('id')->toArray(), $delegators->flatMap->roles->pluck('id')->toArray());

        // 1. GÜÇ KONTROLLERİ (Closure içine \App\Models\User ekledik)
        $isAdmin = $user->id === 1 || $user->hasAnyRole(['Super Admin', 'Admin']) ||
            $delegators->contains(function (\App\Models\User $d) {
                return $d->id === 1 || $d->hasAnyRole(['Super Admin', 'Admin']);
            });

        $hasViewAll = $user->hasPermissionTo('document.view_all') ||
            $delegators->contains(function (\App\Models\User $d) {
                return $d->hasPermissionTo('document.view_all');
            });

        $isGlobalFolder = $folder->departments->isEmpty();
        $isMyDepartment = $folder->departments->whereIn('id', $allDeptIds)->isNotEmpty();

        // 2. ZIRH DELİCİLER (Granular & Matris)
        $hasGranularAccess = $folder->specificUsers()->whereIn('users.id', $allUserIds)->exists();

        $hasMatrixAccess = $folder->rolePermissions()
            ->whereIn('role_id', $allRoleIds)
            ->where('can_view', true)
            ->exists();

        if (!$isAdmin && !$hasViewAll && !$isGlobalFolder && !$isMyDepartment && !$hasGranularAccess && !$hasMatrixAccess) {
            abort(403, 'Bu klasöre erişim yetkiniz bulunmuyor (Departman İzolasyonu veya Vekalet Yetersizliği).');
        }

        // 2. Alt Klasörler
        $subfolders = $folder->children()->visibleTo($user)->orderBy('name')->get();

        // 3. İçindeki Belgeler
        $documents = $folder->documents()
            ->authorizedForUser($user)
            ->latest()
            ->get();

        // 4. Breadcrumb (Yol İzi)
        $breadcrumbs = $folder->getBreadcrumbs();

        // 5. EKLENEN KISIM: Alt klasör oluşturma formundaki Checkbox'lar için tüm departmanlar
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('folders.show', compact('folder', 'subfolders', 'documents', 'breadcrumbs', 'departments'));
    }

    /**
     * YENİ KLASÖR OLUŞTURMA (Akıllı Kalıtım ve Departman İzolasyonu)
     */
    public function store(Request $request)
    {
        // 1. Gelen Veriyi Sıkı Bir Şekilde Doğrula
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'prefix' => 'nullable|string|max:20', // Prefix alanını unutmuyoruz!
            'department_ids' => 'nullable|array', // Çoklu seçim (Checkbox) için
            'department_ids.*' => 'exists:departments,id' // Seçilen ID'ler gerçek mi?
        ]);

        try {
            // 2. Klasörün Temel Bilgilerini Kaydet
            $folder = Folder::create([
                'name' => $validatedData['name'],
                'parent_id' => $validatedData['parent_id'] ?? null,
                'prefix' => $validatedData['prefix'] ?? null,
            ]);

            // 3. MİRAS ALMA VEYA YENİ ATAMA MANTIĞI (Pivot Tablo Senkronizasyonu)
            if (!empty($validatedData['parent_id'])) {
                // ALT KLASÖR İŞLEMİ:
                $parent = Folder::with('departments')->find($validatedData['parent_id']);

                // Eğer üst klasör kısıtlıysa (İzole ise), alt klasör de ZORUNLU olarak o kısıtlamayı miras alır.
                if ($parent && $parent->departments->count() > 0) {
                    $folder->departments()->sync($parent->departments->pluck('id')->toArray());
                }
                // Eğer üst klasör genel/açık ise ama bu alt klasöre özel departman seçildiyse:
                else if (!empty($validatedData['department_ids'])) {
                    $folder->departments()->sync($validatedData['department_ids']);
                }
            } else {
                // ANA DİZİN (KÖK KLASÖR) İŞLEMİ:
                // Modaldan (index.blade.php) seçilen departmanları Pivot Tabloya yaz (Sync)
                if (!empty($validatedData['department_ids'])) {
                    $folder->departments()->sync($validatedData['department_ids']);
                }
            }

            return back()->with('success', '📁 Klasör ve erişim kuralları başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Klasör oluşturma hatası: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Klasör oluşturulurken bir hata meydana geldi: ' . $e->getMessage());
        }
    }
    /**
     * Klasör düzenleme formunu gösterir.
     */
    public function edit(Folder $folder, \App\Services\FolderService $folderService)
    {
        // Sadece yetkili kişiler klasör düzenleyebilir
        \Illuminate\Support\Facades\Gate::authorize('update', $folder);

        // ZIRHLI LİSTEYİ SERVİSTEN ÇEK
        $flatFolders = $folderService->getFlatFolderList();

        // Kendisini (Inception/Sonsuz Döngü) engellemek için listeden çıkart
        if (isset($flatFolders[$folder->id])) {
            unset($flatFolders[$folder->id]);
        }

        $departments = \App\Models\Department::orderBy('name')->get();
        $folderDepartmentIds = $folder->departments->pluck('id')->toArray();

        return view('folders.edit', compact('folder', 'flatFolders', 'departments', 'folderDepartmentIds'));
    }

    /**
     * Klasör bilgilerini günceller.
     */
    public function update(Request $request, Folder $folder)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'prefix' => 'nullable|string|max:20',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id'
        ]);

        // Mantık Kalkanı: Klasör kendisinin veya kendi alt klasörünün içine taşınamaz
        if ($request->parent_id == $folder->id) {
            return back()->with('error', 'Bir klasör kendisinin alt klasörü yapılamaz.');
        }

        $folder->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'prefix' => $request->prefix,
        ]);


        // DEPARTMAN SENKRONİZASYONU
        if (empty($request->parent_id)) {
            // Sadece kök klasörse departmanları güncelle
            if ($request->has('department_ids')) {
                $folder->departments()->sync($request->department_ids);
            } else {
                $folder->departments()->detach(); // Hiçbir şey seçilmediyse global yap
            }
        } else {
            // Alt klasörse üst klasörün departmanlarını ZORLA miras al
            $parent = Folder::with('departments')->find($request->parent_id);
            if ($parent && $parent->departments->count() > 0) {
                $folder->departments()->sync($parent->departments->pluck('id')->toArray());
            } else {
                $folder->departments()->detach();
            }
        }

        return redirect()->route('folders.show', $folder->id)
            ->with('success', '📁 Klasör başarıyla güncellendi.');
    }
    /**
     * Klasörü güvenli bir şekilde siler (Soft Delete).
     * Dolu klasörlerin silinmesini engeller.
     */
    public function destroy(Folder $folder)
    {
        // 1. Yetki Kontrolü (Policy)
        \Illuminate\Support\Facades\Gate::authorize('delete', $folder);

        // 2. KORUMA KALKANI: Klasör dolu mu?
        if ($folder->children()->count() > 0 || $folder->documents()->count() > 0) {
            return back()->with('error', '⛔ Bu klasör silinemez çünkü içinde alt klasörler veya belgeler bulunuyor. Lütfen önce içeriği boşaltın.');
        }

        try {
            $parentId = $folder->parent_id;

            // 3. Soft Delete İşlemi
            $folder->delete();

            // 4. Akıllı Yönlendirme (Kök klasörse ana sayfaya, alt klasörse üst klasöre dön)
            if ($parentId) {
                return redirect()->route('folders.show', $parentId)->with('success', '🗑️ Klasör başarıyla sistem arşivine kaldırıldı (Soft Delete).');
            }
            return redirect()->route('folders.index')->with('success', '🗑️ Ana klasör başarıyla sistem arşivine kaldırıldı (Soft Delete).');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Klasör Silme Hatası: ' . $e->getMessage());
            return back()->with('error', 'Klasör silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }
}
