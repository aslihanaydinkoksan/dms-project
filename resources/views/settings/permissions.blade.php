@extends('layouts.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">🛡️ Roller ve Yetki Matrisi</h1>
        <p class="text-muted">Sistemdeki kullanıcı rollerini, departman onaylarını ve doküman tiplerini yönetin.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @error('name')
        <div class="alert alert-danger">⚠️ {{ $message }}</div>
    @enderror

    <div class="layout-split mb-30"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 25px;">
        <div class="card glass-card" style="border-top: 4px solid var(--accent-color);">
            <div class="card-header flex-between">
                <h4 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center;">
                    <i data-lucide="file-type" style="width: 20px; color: var(--accent-color); margin-right: 8px;"></i>
                    Doküman Tiplerini Yönet
                </h4>
            </div>
            <div class="card-body p-0">
                <div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); background: #f8fafc;">
                    <form action="{{ route('settings.document-types.store') }}" method="POST"
                        style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @csrf
                        <input type="text" name="category" class="form-control form-control-sm"
                            placeholder="Kategori (Örn: Hukuk)" required style="flex: 1; min-width: 120px;"
                            list="category-list">
                        <datalist id="category-list">
                            <option value="Hukuk">
                            <option value="İnsan Kaynakları">
                            <option value="Kalite Yönetimi">
                            <option value="Üretim">
                        </datalist>

                        <input type="text" name="name" class="form-control form-control-sm"
                            placeholder="Tip Adı (Örn: Sözleşme)" required style="flex: 1; min-width: 150px;">
                        <button type="submit" class="btn btn-sm btn-primary" style="padding: 0 20px;">Ekle</button>
                    </form>
                </div>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table modern-table" style="margin: 0;">
                        <thead
                            style="background: var(--bg-color); position: sticky; top: 0; z-index: 5; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <tr>
                                <th style="padding: 15px 20px;">Kategori</th>
                                <th style="padding: 15px 20px;">Tip Adı</th>
                                <th class="text-right" style="padding: 15px 20px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documentTypes->sortBy('category') as $type)
                                <tr id="edit-type-{{ $type->id }}" style="display: none; background: #f1f5f9;">
                                    <td colspan="3" style="padding: 20px; border-bottom: 3px solid var(--accent-color);">
                                        <form action="{{ route('settings.document-types.update', $type->id) }}"
                                            method="POST"
                                            style="background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                                            @csrf @method('PUT')

                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                                                <h5
                                                    style="margin: 0; color: var(--primary-color); display: flex; align-items: center; gap: 8px; font-size: 1.05rem;">
                                                    <i data-lucide="settings-2" style="width: 20px;"></i> Tipi ve Form
                                                    Alanlarını Düzenle
                                                </h5>
                                                <div style="display: flex; gap: 10px;">
                                                    <button type="submit" class="btn btn-sm btn-success"
                                                        style="padding: 6px 15px;">💾 Kaydet</button>
                                                    <button type="button" class="btn btn-sm btn-secondary"
                                                        onclick="toggleTypeEdit('{{ $type->id }}')"
                                                        style="padding: 6px 15px;">İptal</button>
                                                </div>
                                            </div>

                                            <div class="form-grid"
                                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                                                <div>
                                                    <label
                                                        style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; display: block;">Kategori</label>
                                                    <input type="text" name="category" class="form-control"
                                                        value="{{ $type->category }}" required>
                                                </div>
                                                <div>
                                                    <label
                                                        style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; display: block;">Doküman
                                                        Tipi Adı</label>
                                                    <input type="text" name="name" class="form-control"
                                                        value="{{ $type->name }}" required>
                                                </div>
                                            </div>

                                            <div
                                                style="background: #f8fafc; border: 1px dashed #94a3b8; border-radius: 8px; padding: 20px;">
                                                <div
                                                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                                    <div>
                                                        <strong style="font-size: 0.95rem; color: #334155;">Bu Tipe Özel
                                                            Ekstra Form Alanları</strong>
                                                        <small
                                                            style="display: block; color: var(--text-muted); margin-top: 4px;">Kullanıcılar
                                                            bu belge tipini seçtiğinde aşağıdaki alanlar
                                                            açılacaktır.</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="addCustomField('{{ $type->id }}')"
                                                        style="background: #fff;">
                                                        + Yeni Alan Ekle
                                                    </button>
                                                </div>

                                                <div id="fields-container-{{ $type->id }}"
                                                    style="display: flex; flex-direction: column; gap: 12px;">
                                                    @php $fields = $type->custom_fields ?? []; @endphp

                                                    @if (empty($fields))
                                                        <div class="empty-field-msg text-center text-muted"
                                                            style="padding: 15px; font-size: 0.9rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                                            Şu an bu tipe özel ekstra bir alan yok. Sağ üstten
                                                            ekleyebilirsiniz.
                                                        </div>
                                                    @endif

                                                    @foreach ($fields as $index => $field)
                                                        <div class="field-row"
                                                            style="display: flex; gap: 15px; align-items: center; background: #ffffff; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                                            <div style="flex: 2;">
                                                                <input type="text"
                                                                    name="custom_fields[{{ $index }}][label]"
                                                                    value="{{ $field['label'] }}" class="form-control"
                                                                    placeholder="Ekranda Görünen Ad (Örn: Sözleşme Bedeli)"
                                                                    required title="Kullanıcının formda göreceği isim">
                                                            </div>
                                                            <div style="flex: 2;">
                                                                <input type="text"
                                                                    name="custom_fields[{{ $index }}][name]"
                                                                    value="{{ $field['name'] }}" class="form-control"
                                                                    placeholder="Sistem Adı (Örn: bedel)" required
                                                                    title="Veritabanına kaydedilecek JSON anahtarı (Boşluksuz yazın)">
                                                            </div>
                                                            <div style="flex: 1;">
                                                                <select name="custom_fields[{{ $index }}][type]"
                                                                    class="form-control">
                                                                    <option value="text"
                                                                        {{ $field['type'] == 'text' ? 'selected' : '' }}>
                                                                        Yazı (Text)</option>
                                                                    <option value="number"
                                                                        {{ $field['type'] == 'number' ? 'selected' : '' }}>
                                                                        Sayı (Number)</option>
                                                                    <option value="date"
                                                                        {{ $field['type'] == 'date' ? 'selected' : '' }}>
                                                                        Tarih (Date)</option>
                                                                </select>
                                                            </div>
                                                            <button type="button" class="btn btn-outline-danger"
                                                                style="padding: 8px 12px;"
                                                                onclick="this.closest('.field-row').remove()"
                                                                title="Alanı Sil">✖</button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>

                                <tr id="view-type-{{ $type->id }}" style="transition: background 0.2s ease;">
                                    <td style="padding: 15px 20px;">
                                        <span class="badge badge-secondary"
                                            style="font-size: 0.8rem; background: #e2e8f0; color: #475569; padding: 5px 10px; border-radius: 4px;">
                                            {{ $type->category ?? 'Genel' }}
                                        </span>
                                    </td>
                                    <td style="padding: 15px 20px; font-weight: 500; color: #1e293b;">{{ $type->name }}
                                    </td>
                                    <td style="padding: 15px 20px; text-align: right;">
                                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="toggleTypeEdit('{{ $type->id }}')"
                                                title="Düzenle">✏️</button>
                                            <form action="{{ route('settings.document-types.destroy', $type->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Bu doküman tipini silmek istediğinize emin misiniz?')"
                                                style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Sil">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card glass-card">
            <div class="widget-header flex-between"
                style="padding: 15px 20px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h4 style="margin:0; font-size: 1.1rem; color: var(--text-color);">
                        <i data-lucide="building-2" style="width: 20px; margin-right: 8px; vertical-align: middle;"></i>
                        Tesis ve Departman Yönetimi
                    </h4>
                </div>
            </div>

            <div class="card-body p-0">
                <div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); background: #f8fafc;">
                    <form action="{{ route('settings.departments.store') }}" method="POST"
                        style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @csrf
                        <input type="text" name="unit" class="form-control form-control-sm"
                            placeholder="Tesis/Birim (Örn: Preform)" required style="flex: 1; min-width: 120px;"
                            list="unit-list">
                        <datalist id="unit-list">
                            <option value="Merkez">
                            <option value="Preform">
                            <option value="Levha">
                            <option value="Kopet">
                            <option value="Rezin">
                        </datalist>

                        <input type="text" name="name" class="form-control form-control-sm"
                            placeholder="Departman (Örn: İnsan Kaynakları)" required style="flex: 2; min-width: 150px;">

                        <button type="submit" class="btn btn-sm btn-primary" style="padding: 0 20px;">Ekle</button>
                    </form>
                </div>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table modern-table" style="margin:0;">
                        <thead
                            style="background: var(--bg-color); position: sticky; top: 0; z-index: 5; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <tr>
                                <th style="padding: 15px 20px;">Tesis / Birim</th>
                                <th style="padding: 15px 20px;">Departman Adı</th>
                                <th class="text-center" style="padding: 15px 20px;">Yönetici Onayı</th>
                                <th class="text-right" style="padding: 15px 20px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($departments->sortBy('unit') as $dept)
                                <tr>
                                    <td colspan="4" style="padding: 0; border: none;">
                                        <form action="{{ route('settings.departments.update', $dept->id) }}"
                                            method="POST" id="edit-dept-{{ $dept->id }}"
                                            style="display: none; padding: 15px 20px; background: #f1f5f9; gap: 15px; align-items: center; margin: 0; border-bottom: 2px solid var(--accent-color);">
                                            @csrf @method('PUT')
                                            <input type="text" name="unit" class="form-control"
                                                value="{{ $dept->unit }}" required style="width: 140px;">
                                            <input type="text" name="name" class="form-control"
                                                value="{{ $dept->name }}" required style="flex: 1;">
                                            <button type="submit" class="btn btn-success">Kaydet</button>
                                            <button type="button" class="btn btn-secondary"
                                                onclick="toggleDeptEdit('{{ $dept->id }}')">İptal</button>
                                        </form>
                                    </td>
                                </tr>

                                <tr id="view-dept-{{ $dept->id }}">
                                    <td style="padding: 15px 20px;">
                                        <span class="badge badge-secondary"
                                            style="font-size: 0.8rem; background: #e2e8f0; color: #475569; padding: 5px 10px; border-radius: 4px;">
                                            {{ $dept->unit ?? 'Merkez' }}
                                        </span>
                                    </td>
                                    <td style="padding: 15px 20px; font-weight: 500; color: #1e293b;">{{ $dept->name }}
                                    </td>
                                    <td class="text-center" style="padding: 15px 20px;">
                                        <label class="toggle-switch" style="display: inline-flex; margin: 0;">
                                            <input type="checkbox"
                                                onchange="toggleDeptApproval({{ $dept->id }}, this)"
                                                {{ $dept->requires_approval_on_upload ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </td>
                                    <td class="text-right" style="padding: 15px 20px;">
                                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="toggleDeptEdit('{{ $dept->id }}')"
                                                title="Düzenle">✏️</button>
                                            <form action="{{ route('settings.departments.destroy', $dept->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Bu departmanı silmek istediğinize emin misiniz?')"
                                                style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Sil">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="layout-split mb-30" style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px;">
        <div class="card glass-card" style="border-top: 4px solid var(--primary-color);">
            <div class="card-header">
                <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center;">
                    <i data-lucide="shield-plus" style="width: 20px; color: var(--primary-color); margin-right: 8px;"></i>
                    Yeni Rol Tanımla
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5;">
                    Sisteme yeni bir departman veya hiyerarşik yetki grubu ekleyin.
                </p>
                <form action="{{ route('settings.roles.store') }}" method="POST" class="modern-form">
                    @csrf
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: 600; color: #475569;">Rol Adı</label>
                        <input type="text" name="name" class="form-control" placeholder="Örn: Finans Departmanı"
                            required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 10px;">
                        <i data-lucide="plus" style="width: 18px; margin-right: 5px; vertical-align: text-bottom;"></i>
                        Rolü
                        Ekle
                    </button>
                </form>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header">
                <h4 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center;">
                    <i data-lucide="settings-2" style="width: 20px; color: var(--text-color); margin-right: 8px;"></i>
                    Mevcut Rolleri Yönet
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table modern-table" style="margin: 0;">
                        <thead
                            style="background: #f8fafc; position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <tr>
                                <th style="padding: 15px 20px;">Rol Adı</th>
                                <th style="width: 150px; padding: 15px 20px; text-align: right;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr style="transition: background 0.2s ease;">
                                    <td style="padding: 15px 20px;">
                                        <form action="{{ route('settings.roles.update', $role->id) }}" method="POST"
                                            id="edit-role-{{ $role->id }}"
                                            style="display: none; gap: 15px; align-items: center; margin: 0;">
                                            @csrf @method('PUT')
                                            <input type="text" name="name" class="form-control"
                                                value="{{ $role->name }}" required style="width: 60%;">
                                            <button type="submit" class="btn btn-success">Kaydet</button>
                                            <button type="button" class="btn btn-secondary"
                                                onclick="toggleEdit('{{ $role->id }}')">İptal</button>
                                        </form>

                                        <span id="view-role-{{ $role->id }}" class="font-bold"
                                            style="color: #1e293b;">
                                            {{ $role->name }}
                                            @if (in_array($role->name, ['Super Admin', 'Admin']))
                                                <span class="badge badge-warning"
                                                    style="margin-left: 8px; font-size: 0.75rem; font-weight: normal; background: #fffbeb; color: #ea580c; border: 1px solid #fde68a; padding: 4px 8px;">
                                                    Sistem Kilidi 🔒
                                                </span>
                                            @endif
                                        </span>
                                    </td>
                                    <td style="padding: 15px 20px; text-align: right;">
                                        @if (!in_array($role->name, ['Super Admin', 'Admin']))
                                            <div id="actions-role-{{ $role->id }}"
                                                style="display: flex; justify-content: flex-end; gap: 8px;">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    onclick="toggleEdit('{{ $role->id }}')" title="Düzenle">
                                                    ✏️
                                                </button>
                                                <form action="{{ route('settings.roles.destroy', $role->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Bu rolü silmek istediğinize emin misiniz? Bu role bağlı tüm yetkiler kaybolacaktır.')"
                                                    style="margin: 0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Sil">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section-divider mt-30 mb-30" style="border-top: 2px dashed #cbd5e1;"></div>

    <div class="page-header mb-20">
        <h2 class="page-title" style="font-size: 1.4rem;">⚙️ 3D Kategori Yetki Matrisi</h2>
        <p class="text-muted">Hangi rolün, hangi doküman kategorisinde ne gibi işlemler yapabileceğini belirleyin.</p>
    </div>

    <form action="{{ route('settings.permissions.update') }}" method="POST" id="permissionsForm">
        @csrf

        <div class="matrix-container glass-card mb-30">
            <div class="matrix-tabs"
                style="display: flex; gap: 5px; overflow-x: auto; border-bottom: 2px solid var(--border-color); background: #f8fafc; padding: 15px 15px 0 15px; border-radius: 8px 8px 0 0;">
                @foreach ($documentTypes as $index => $type)
                    <button type="button" class="matrix-tab {{ $index === 0 ? 'active' : '' }}"
                        data-matrixtarget="matrix-{{ $type->slug }}"
                        style="padding: 12px 25px; border: none; background: {{ $index === 0 ? '#fff' : 'transparent' }}; border-bottom: {{ $index === 0 ? '3px solid var(--primary-color)' : '3px solid transparent' }}; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; font-size: 0.95rem; transition: background 0.2s;">
                        📂 {{ $type->name }}
                    </button>
                @endforeach
            </div>

            @foreach ($documentTypes as $index => $type)
                <div class="matrix-content {{ $index === 0 ? 'active' : '' }}" id="matrix-{{ $type->slug }}"
                    style="display: {{ $index === 0 ? 'block' : 'none' }}; padding: 25px;">
                    <table class="table modern-table matrix-table">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th style="padding: 15px;">Sistem Rolü</th>
                                <th class="text-center" style="padding: 15px;">👁️ Görüntüleme (View)</th>
                                <th class="text-center" style="padding: 15px;">📤 Yükleme (Create)</th>
                                <th class="text-center" style="padding: 15px;">📝 Revize Etme (Edit)</th>
                                <th class="text-center" style="padding: 15px;">🗑️ İmha (Delete)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                @if (!in_array($role->name, ['Super Admin', 'Admin']))
                                    <tr style="transition: background 0.2s; hover: background: #f8fafc;">
                                        <td class="font-bold" style="padding: 15px;">{{ $role->name }}</td>

                                        @foreach (['view', 'create', 'edit', 'delete'] as $action)
                                            @php
                                                $permissionName = $type->slug . '.' . $action;
                                                $hasPermission = $role->hasPermissionTo($permissionName);
                                            @endphp
                                            <td class="text-center" style="padding: 15px;">
                                                <label class="custom-checkbox d-inline-block">
                                                    <input type="checkbox" name="permissions[{{ $role->id }}][]"
                                                        value="{{ $permissionName }}"
                                                        {{ $hasPermission ? 'checked' : '' }}>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
        <div class="card glass-card mb-30" style="border-top: 4px solid var(--danger-color);">
            <div class="card-header flex-between" style="padding-bottom: 15px;">
                <div>
                    <h3 style="margin: 0; color: var(--danger-color); font-size: 1.2rem;">
                        🛡️ Özel Güvenlik Kalkanları (Global Yetkiler)
                    </h3>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="alert alert-warning" style="margin: 20px; border-radius: 8px;">
                    <strong>Dikkat:</strong> Buradaki yetkiler kategori kurallarını ezerek tüm sistemde geçerli olur.
                    <strong>"Çok Gizli"</strong> yetkisini sadece en üst düzey yöneticilere verin.
                </div>

                <div class="table-responsive">
                    <table class="table modern-table" style="margin: 0;">
                        <thead style="background: #fff5f5;">
                            <tr>
                                <th style="width: 200px; padding: 15px 20px;">Kullanıcı Rolü</th>
                                @foreach ($specialPermissions as $sp)
                                    <th class="text-center" style="padding: 15px 10px; font-size: 0.85rem;">
                                        @if ($sp->name == 'document.view_strictly_confidential')
                                            🕵️ Çok Gizli Belge Gör.
                                        @elseif($sp->name == 'document.view_all')
                                            🌍 Tüm Belgeleri Gör.
                                        @elseif($sp->name == 'document.manage_all')
                                            👑 Tam Yetki (Bypass)
                                        @elseif($sp->name == 'document.force_unlock')
                                            ⚠️ Kilit Zorla Açma
                                        @else
                                            {{ $sp->name }}
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                @if ($role->name !== 'Super Admin')
                                    <tr style="transition: background 0.2s;">
                                        <td style="font-weight: bold; color: var(--primary-color); padding: 15px 20px;">
                                            {{ $role->name }}
                                        </td>

                                        @foreach ($specialPermissions as $sp)
                                            <td class="text-center">
                                                <label class="custom-checkbox d-inline-block">
                                                    <input type="checkbox"
                                                        name="special_permissions[{{ $role->id }}][]"
                                                        value="{{ $sp->name }}"
                                                        {{ $role->hasPermissionTo($sp->name) ? 'checked' : '' }}>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="form-actions text-right"
            style="position: sticky; bottom: 20px; z-index: 100; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; box-shadow: 0 -10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;">
            <p class="text-muted d-inline-block" style="margin-right: 20px; font-size: 0.95rem;">
                Tüm kategorilerdeki değişiklikler tek seferde kaydedilecektir.
            </p>
            <button type="submit" class="btn btn-success"
                style="font-size: 1.1rem; padding: 12px 40px; border-radius: 30px; font-weight: 600; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);">
                💾 Tüm Matrisi Kaydet
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Doküman Tipi Inline Düzenleme Scripti
        function toggleTypeEdit(typeId) {
            const form = document.getElementById('edit-type-' + typeId);
            const view = document.getElementById('view-type-' + typeId);

            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'table-row';
                view.style.display = 'none';
            } else {
                form.style.display = 'none';
                view.style.display = 'table-row';
            }
        }

        // MATRİS SEKME DEĞİŞTİRME MANTIĞI
        document.addEventListener('DOMContentLoaded', function() {
            const matrixTabs = document.querySelectorAll('.matrix-tab');
            const matrixContents = document.querySelectorAll('.matrix-content');

            matrixTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();

                    matrixTabs.forEach(t => {
                        t.classList.remove('active');
                        t.style.background = 'transparent';
                        t.style.borderBottom = '3px solid transparent';
                    });

                    matrixContents.forEach(c => {
                        c.style.display = 'none';
                        c.classList.remove('active');
                    });

                    this.classList.add('active');
                    this.style.background = '#fff';
                    this.style.borderBottom = '3px solid var(--primary-color)';

                    const targetId = this.getAttribute('data-matrixtarget');
                    const targetContent = document.getElementById(targetId);
                    if (targetContent) {
                        targetContent.style.display = 'block';
                        targetContent.classList.add('active');
                    }
                });
            });
        });

        // Departman Inline Düzenleme
        function toggleDeptEdit(deptId) {
            const form = document.getElementById('edit-dept-' + deptId);
            const view = document.getElementById('view-dept-' + deptId);

            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'flex';
                view.style.display = 'none';
            } else {
                form.style.display = 'none';
                view.style.display = 'table-row';
            }
        }

        // Dinamik Form Alanı Ekleme Motoru
        function addCustomField(typeId) {
            const container = document.getElementById('fields-container-' + typeId);
            const emptyMsg = container.querySelector('.empty-field-msg');
            if (emptyMsg) emptyMsg.style.display = 'none';

            const index = Date.now();

            const rowHtml = `
                <div class="field-row" style="display: flex; gap: 15px; align-items: center; background: #ffffff; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); animation: fadeIn 0.3s ease;">
                    <div style="flex: 2;">
                        <input type="text" name="custom_fields[${index}][label]" class="form-control" placeholder="Ekranda Görünen Ad (Örn: Para Birimi)" required>
                    </div>
                    <div style="flex: 2;">
                        <input type="text" name="custom_fields[${index}][name]" class="form-control" placeholder="Sistem Adı (Örn: para_birimi)" required>
                    </div>
                    <div style="flex: 1;">
                        <select name="custom_fields[${index}][type]" class="form-control">
                            <option value="text">Yazı (Text)</option>
                            <option value="number">Sayı (Number)</option>
                            <option value="date">Tarih (Date)</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-danger" style="padding: 8px 12px;" onclick="this.closest('.field-row').remove()">✖</button>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', rowHtml);
        }

        // Rol Inline Düzenleme Scripti
        function toggleEdit(roleId) {
            const form = document.getElementById('edit-role-' + roleId);
            const view = document.getElementById('view-role-' + roleId);
            const actions = document.getElementById('actions-role-' + roleId);

            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'flex';
                view.style.display = 'none';
                if (actions) actions.style.display = 'none';
            } else {
                form.style.display = 'none';
                view.style.display = 'inline';
                if (actions) actions.style.display = 'flex';
            }
        }

        // Departman Onay Toggle Scripti (AJAX)
        function toggleDeptApproval(deptId, checkbox) {
            checkbox.disabled = true;

            fetch(`/settings/departments/${deptId}/toggle-approval`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        is_active: checkbox.checked ? 1 : 0
                    })
                })
                .then(response => response.json())
                .then(data => {
                    checkbox.disabled = false;
                })
                .catch(error => {
                    checkbox.disabled = false;
                    checkbox.checked = !checkbox.checked;
                    alert('İşlem sırasında bir hata oluştu.');
                });
        }
    </script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush
