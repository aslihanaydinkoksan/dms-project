@extends('layouts.app')

@section('content')
    {{-- ÜST BAŞLIK (Görseldeki Gibi) --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('admin.roles.index') }}" class="text-muted text-decoration-none d-flex align-items-center gap-1" style="font-weight: 500;">
            &larr; Geri
        </a>
        <h1 class="m-0" style="font-size: 1.5rem; font-weight: 600; color: #1e293b;">Rol Düzenle</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius: 8px; padding: 15px; font-weight: 500;">
            <i data-lucide="check-circle" style="width: 20px;"></i> <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius: 8px; padding: 15px; font-weight: 500;">
            <i data-lucide="alert-triangle" style="width: 20px;"></i> <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ROL BİLGİSİ KARTI (Görseldeki Gibi Ferah ve Açıklayıcı) --}}
    <div class="card border-0 mb-5" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-4">
            <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="row align-items-end">
                    <div class="col-md-5 mb-3 mb-md-0">
                        <label style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            ROL ADI (ÖRN: ADMIN, MÜDÜR, MÜŞTERİ)
                        </label>
                        <input type="text" name="name" class="form-control" value="{{ $role->name }}" required style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; font-weight: 500; color: #1e293b;">
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            HİYERARŞİ SEVİYESİ
                        </label>
                        <input type="number" name="hierarchy_level" class="form-control" value="{{ $role->hierarchy_level ?? 0 }}" required min="0" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; font-weight: 500; color: #1e293b;">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100" style="background: #4f46e5; color: white; border-radius: 8px; padding: 12px 15px; font-weight: 600;">
                            Gereksinimleri Güncelle
                        </button>
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                    Bu rolü kullanıcılara atayarak yetki matrisinde seçtiğiniz haklara sahip olmalarını sağlayabilirsiniz. 
                    <strong>Uyarı:</strong> İsim değişikliği yapmak eski başlatılmış görevlerin detaylarındaki (snapshot) rol adını etkilemez ancak yetki matrisini hemen uygular.
                </p>
            </form>
        </div>
    </div>

    {{-- YETKİ MATRİSİ BAŞLIĞI --}}
    <h3 class="mb-4" style="font-size: 1.4rem; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
        Yetki Matrisi
    </h3>

    {{-- MODERN SEKME BUTONLARI (Workflow tasarım diline uygun) --}}
    <div class="workflow-tabs d-flex gap-2 mb-4" style="overflow-x: auto; padding-bottom: 5px;">
        <button type="button" class="w-tab-btn active" data-bs-target="#tab-global">Global Sistem Ayarları</button>
        <button type="button" class="w-tab-btn" data-bs-target="#tab-doctype">Doküman Şablonları</button>
        <button type="button" class="w-tab-btn" data-bs-target="#tab-menu">Menü Erişimleri</button>
        <button type="button" class="w-tab-btn" data-bs-target="#tab-folder">Klasör İzinleri</button>
    </div>

    {{-- TAB İÇERİKLERİ --}}
    <div class="tab-contents-wrapper pb-5">
        
        {{-- TAB 1: GLOBAL YETKİLER (Görseldeki "Kartlı ve Alt Başlıklı" Tasarım) --}}
        <div id="tab-global" class="tab-pane-content d-block">
            <form action="{{ route('admin.roles.matrix.global', $role->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="card workflow-matrix-card">
                    <div class="card-header d-flex align-items-start gap-3">
                        <div class="icon-box" style="color: #d97706; background: #fef3c7;">
                            <i data-lucide="settings" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <h5 class="card-title">Global Sistem Yönetimi</h5>
                            <p class="card-desc">Sistem geneli ayarlar, kullanıcı havuzu ve izolasyon kalkanları</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php
                                $globalPermMap = [
                                    'document.create' => 'Genel Belge Oluşturma',
                                    'document.view_all' => 'Tüm Belgeleri Görüntüleme',
                                    'document.approve' => 'Belge Onaylama',
                                    'document.delete' => 'Belge Silme',
                                    'document.hard_delete' => 'Belge Kalıcı Silme',
                                    'user.manage' => 'Kullanıcı Havuzunu Yönetebilir',
                                    'department.manage' => 'Departmanları Yönetebilir',
                                    'system.settings' => 'Sistem Ayarlarını Değiştirebilir',
                                    'system.destroy' => 'Sistemi İmha Etme (Kritik)',
                                    'document.force_unlock' => 'Kilitli Belgeleri Açma',
                                    'document.view_strictly_confidential' => 'Çok Gizli Belgeleri Görme',
                                    'document.view_confidential' => 'Gizli Belgeleri Görme',
                                    'document.manage_all' => 'Tüm Belgeleri Yönetme',
                                    'document.manage_versions' => 'Versiyonları Yönetebilir',
                                    'document.manage_attachments' => 'Ekleri Yönetebilir',
                                    'notify.global' => 'Global Bildirim Gönderme',
                                    'task.view_all' => 'Tüm Görevleri Görebilir',
                                    'task.create' => 'Görev Başlatabilir',
                                    'task.edit' => 'Görevleri Düzenleyebilir',
                                    'task.delete' => 'Görevleri Silebilir',
                                    'task.restrict_department' => 'Görevi Departmana Kısıtlama',
                                ];

                                $docTypeSlugs = $documentTypes->pluck('slug')->toArray();
                                $filteredGlobals = $specialPermissions->reject(function($sp) use ($docTypeSlugs) {
                                    foreach ($docTypeSlugs as $slug) {
                                        if (\Illuminate\Support\Str::startsWith($sp->name, $slug . '.')) return true; 
                                    }
                                    return false;
                                });
                            @endphp

                            @foreach ($filteredGlobals as $sp)
                                @php
                                    $rawName = $sp->name;
                                    if (array_key_exists($rawName, $globalPermMap)) {
                                        $displayName = $globalPermMap[$rawName];
                                    } elseif (\Illuminate\Support\Str::startsWith($rawName, 'document.view_')) {
                                        $displayName = \Illuminate\Support\Str::title(str_replace('_', ' ', str_replace('document.view_', '', $rawName))) . ' Gizliliğini Görme';
                                    } else {
                                        $displayName = \Illuminate\Support\Str::title(str_replace(['.', '_'], ' ', $rawName));
                                    }
                                @endphp
                                <div class="col-md-6 mb-2">
                                    <label class="permission-item d-flex align-items-start gap-3 p-3 rounded">
                                        <div class="mt-1">
                                            <input type="checkbox" name="permissions[]" value="{{ $rawName }}" {{ $role->hasPermissionTo($rawName) ? 'checked' : '' }} class="workflow-checkbox">
                                        </div>
                                        <div>
                                            <div class="perm-title">{{ $displayName }}</div>
                                            <div class="perm-slug">{{ $rawName }}</div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer text-end bg-white border-top-0 pt-0 pb-4 pe-4">
                        <button type="submit" class="btn workflow-save-btn">Yetki Matrisini Kaydet</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- TAB 2: DOKÜMAN TİPİ MATRİSİ --}}
        <div id="tab-doctype" class="tab-pane-content d-none">
            <form action="{{ route('admin.roles.matrix.document-type', $role->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="card workflow-matrix-card">
                    <div class="card-header d-flex align-items-start gap-3">
                        <div class="icon-box" style="color: #059669; background: #d1fae5;">
                            <i data-lucide="edit" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <h5 class="card-title">Şablon ve Belge Türü İzinleri</h5>
                            <p class="card-desc">Kullanıcıların hangi tür belgelerde okuma, yazma veya silme hakkı olduğunu belirleyin</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table modern-table mb-0" style="font-size: 0.9rem;">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th style="padding: 15px 20px; font-weight: 600; color: #475569;">Doküman Tipi</th>
                                        <th class="text-center" style="color: #475569;">Görüntüle</th>
                                        <th class="text-center" style="color: #475569;">Yükle/Oluştur</th>
                                        <th class="text-center" style="color: #475569;">Düzenle</th>
                                        <th class="text-center" style="color: #475569;">Sil</th>
                                        <th class="text-center" style="color: #475569;">Versiyon</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documentTypes as $type)
                                        @php $rule = $docTypeMatrix->get($type->name); @endphp
                                        <tr class="hover-row">
                                            <td style="padding: 15px 20px; font-weight: 600; color: #1e293b;">
                                                {{ $type->name }}
                                                <div class="perm-slug mt-1">{{ $type->slug }}.*</div>
                                            </td>
                                            <td class="text-center align-middle"><input type="checkbox" name="permissions[{{ $type->name }}][can_view]" value="1" {{ $rule && $rule->can_view ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="permissions[{{ $type->name }}][can_create]" value="1" {{ $rule && $rule->can_create ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="permissions[{{ $type->name }}][can_edit]" value="1" {{ $rule && $rule->can_edit ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="permissions[{{ $type->name }}][can_delete]" value="1" {{ $rule && $rule->can_delete ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="permissions[{{ $type->name }}][can_manage_versions]" value="1" {{ $rule && $rule->can_manage_versions ? 'checked' : '' }} class="workflow-checkbox"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end bg-white border-top p-4">
                        <button type="submit" class="btn workflow-save-btn">Yetki Matrisini Kaydet</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- TAB 3: MENÜ MATRİSİ --}}
        <div id="tab-menu" class="tab-pane-content d-none">
            <form action="{{ route('admin.roles.matrix.menu', $role->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="card workflow-matrix-card">
                    <div class="card-header d-flex align-items-start gap-3">
                        <div class="icon-box" style="color: #2563eb; background: #dbeafe;">
                            <i data-lucide="layout-dashboard" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <h5 class="card-title">Navigasyon ve Modül Görünürlüğü</h5>
                            <p class="card-desc">Sol menüde hangi sayfaların ve modüllerin bu role açık olacağını seçin</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php
                                $menuMap = [
                                    'analytics' => 'Analitik ve İstatistiklere Girebilir',
                                    'dashboard' => 'Ana Ekrana (Dashboard) Girebilir',
                                    'documents' => 'Doküman Modülünü Görebilir',
                                    'executive_cockpit' => 'Yönetici Kokpitine Girebilir',
                                    'folders' => 'Klasör Modülünü Görebilir',
                                    'process_templates' => 'Süreç Tasarım Merkezini Görebilir',
                                    'reports' => 'Sistem Raporlarını Alabilir',
                                    'settings' => 'Admin Paneline Girebilir',
                                    'tasks' => 'Görevler Sekmesini Görebilir',
                                    'tasks_archive' => 'Görev Arşivine Bakabilir',
                                    'user_groups' => 'Kullanıcı Gruplarını Görebilir',
                                    'users' => 'Kullanıcılar Sayfasını Görebilir',
                                ];
                            @endphp
                            
                            @foreach ($menuPermissions as $mp)
                                @php
                                    $rawMenuName = str_replace('menu.', '', $mp->name);
                                    $displayMenuName = $menuMap[$rawMenuName] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $rawMenuName));
                                @endphp
                                <div class="col-md-6 mb-2">
                                    <label class="permission-item d-flex align-items-start gap-3 p-3 rounded">
                                        <div class="mt-1">
                                            <input type="checkbox" name="menu_permissions[]" value="{{ $mp->name }}" {{ $role->hasPermissionTo($mp->name) ? 'checked' : '' }} class="workflow-checkbox">
                                        </div>
                                        <div>
                                            <div class="perm-title">{{ $displayMenuName }}</div>
                                            <div class="perm-slug">{{ $mp->name }}</div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer text-end bg-white border-top-0 pt-0 pb-4 pe-4">
                        <button type="submit" class="btn workflow-save-btn">Yetki Matrisini Kaydet</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- TAB 4: KLASÖR YETKİ MATRİSİ --}}
        <div id="tab-folder" class="tab-pane-content d-none">
            <form action="{{ route('admin.roles.matrix.folder', $role->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="card workflow-matrix-card">
                    <div class="card-header d-flex align-items-start gap-3">
                        <div class="icon-box" style="color: #d946ef; background: #fee2e2;">
                            <i data-lucide="folder-open" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <h5 class="card-title">Dinamik Klasör Yetkileri</h5>
                            <p class="card-desc">Dosya sistemindeki klasör bazlı okuma/yazma sınırlarını yapılandırın</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table modern-table mb-0" style="font-size: 0.9rem;">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th style="padding: 15px 20px; font-weight: 600; color: #475569;">Klasör Adı</th>
                                        <th class="text-center" style="color: #475569;">Görüntüle</th>
                                        <th class="text-center" style="color: #475569;">Belge Yükle</th>
                                        <th class="text-center" style="color: #475569;">Alt Klasör Aç</th>
                                        <th class="text-center" style="color: #475569;">Yönet (Sil/Düz)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $roleFolderPerms = \App\Models\FolderRolePermission::where('role_id', $role->id)->get()->keyBy('folder_id');
                                    @endphp
                                    
                                    @foreach ($folders as $folder)
                                        @php $fRule = $roleFolderPerms->get($folder->id); @endphp
                                        <tr class="hover-row">
                                            <td style="padding: 15px 20px; font-weight: 600; color: #1e293b;">
                                                @if($folder->prefix) <span class="badge bg-light text-dark me-1">[{{ $folder->prefix }}]</span> @endif
                                                {{ $folder->name }}
                                            </td>
                                            <td class="text-center align-middle"><input type="checkbox" name="folder_permissions[{{ $folder->id }}][can_view]" value="1" {{ $fRule && $fRule->can_view ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="folder_permissions[{{ $folder->id }}][can_upload]" value="1" {{ $fRule && $fRule->can_upload ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="folder_permissions[{{ $folder->id }}][can_create_subfolder]" value="1" {{ $fRule && $fRule->can_create_subfolder ? 'checked' : '' }} class="workflow-checkbox"></td>
                                            <td class="text-center align-middle"><input type="checkbox" name="folder_permissions[{{ $folder->id }}][can_manage]" value="1" {{ $fRule && $fRule->can_manage ? 'checked' : '' }} class="workflow-checkbox"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end bg-white border-top p-4">
                        <button type="submit" class="btn workflow-save-btn">Yetki Matrisini Kaydet</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();

        const tabBtns = document.querySelectorAll('.w-tab-btn');
        const tabContents = document.querySelectorAll('.tab-pane-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); 
                e.stopPropagation(); 

                tabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                tabContents.forEach(pane => {
                    pane.classList.remove('d-block');
                    pane.classList.add('d-none');
                });

                const targetId = this.getAttribute('data-bs-target');
                const targetPane = document.querySelector(targetId);
                
                if (targetPane) {
                    targetPane.classList.remove('d-none');
                    targetPane.classList.add('d-block');
                }
            });
        });
    });
</script>

<style>
    /* WORKFLOW TARZI SEKMELER */
    .workflow-tabs .w-tab-btn {
        background: transparent; border: none; padding: 10px 15px; font-size: 0.95rem; font-weight: 600; color: #64748b;
        cursor: pointer; border-bottom: 3px solid transparent; transition: 0.2s; white-space: nowrap;
    }
    .workflow-tabs .w-tab-btn:hover { color: #334155; }
    .workflow-tabs .w-tab-btn.active { color: #4f46e5; border-bottom: 3px solid #4f46e5; }

    /* KART TASARIMI */
    .workflow-matrix-card {
        border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); background: white;
    }
    .workflow-matrix-card .card-header {
        background: white; border-bottom: 1px solid #e2e8f0; padding: 25px; border-radius: 12px 12px 0 0;
    }
    .workflow-matrix-card .icon-box {
        padding: 12px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
    }
    .workflow-matrix-card .card-title {
        margin: 0 0 5px 0; font-size: 1.15rem; font-weight: 600; color: #1e293b;
    }
    .workflow-matrix-card .card-desc {
        margin: 0; font-size: 0.85rem; color: #64748b;
    }

    /* İZİN SATIRLARI (Tıpkı görseldeki gibi) */
    .permission-item {
        border: 1px solid transparent; transition: background 0.2s, border 0.2s;
    }
    .permission-item:hover {
        background: #f8fafc; border: 1px solid #e2e8f0;
    }
    .permission-item .perm-title {
        font-weight: 600; color: #1e293b; font-size: 0.95rem; line-height: 1.4;
    }
    .permission-item .perm-slug {
        color: #94a3b8; font-size: 0.75rem; font-family: monospace; margin-top: 4px; letter-spacing: 0.2px;
    }

    /* MOR/MAVİ KAYDET BUTONU & CHECKBOX */
    .workflow-checkbox {
        width: 20px; height: 20px; accent-color: #4f46e5; cursor: pointer;
    }
    .workflow-save-btn {
        background: #4f46e5; color: white; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 0.95rem;
        transition: 0.2s; border: none; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }
    .workflow-save-btn:hover { background: #4338ca; color: white; transform: translateY(-1px); }

    .d-none { display: none !important; }
    .d-block { display: block !important; }
    .hover-row:hover { background-color: #f8fafc; }
</style>
@endpush