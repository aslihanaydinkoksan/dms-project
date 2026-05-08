@extends('layouts.app')

@section('content')
    <div class="page-header mb-30">
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">🛡️
            {{ __('Sistem ve Yetki Yönetimi') }}</h1>
        <p class="text-muted">
            {{ __('Sistemdeki organizasyon yapısını, belge tiplerini ve tüm güvenlik/erişim kurallarını buradan yönetin.') }}
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center"
            style="border-radius: 8px; padding: 15px; font-weight: 500; gap: 8px;">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @error('name')
        <div class="alert alert-danger d-flex align-items-center"
            style="border-radius: 8px; padding: 15px; font-weight: 500; gap: 8px;">
            <i data-lucide="alert-triangle" style="width: 20px;"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <div class="form-section-divider mb-20" style="border-top: 2px solid #e2e8f0; padding-top: 20px;">
        <h3
            style="color: var(--secondary-color); font-size: 1.2rem; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="blocks" style="width: 22px;"></i> 1. {{ __('Organizasyon ve Tanımlamalar') }}
        </h3>
    </div>

    <div class="admin-dashboard-grid mb-40">

        {{-- 1. KART: DOKÜMAN TİPLERİ VE FORMLAR --}}
        <div class="card glass-card admin-card-full"
            style="border-top: 4px solid var(--accent-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="card-header flex-between"
                style="background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                <h4
                    style="margin: 0; font-size: 1.1rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="file-type" style="width: 20px; color: var(--accent-color);"></i>
                    {{ __('Doküman Tipleri ve Dinamik Formlar') }}
                </h4>
            </div>

            <div class="card-body p-0">
                <div style="display: flex; flex-wrap: wrap;">

                    <div
                        style="flex: 1 1 50%; min-width: 400px; padding: 24px; border-right: 1px solid var(--border-color);">
                        <h5
                            style="margin-top: 0; margin-bottom: 15px; color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">
                            {{ __('Kayıtlı Tipler') }}</h5>
                        <div class="table-responsive custom-scrollbar"
                            style="max-height: 420px; border: 1px solid var(--border-color); border-radius: 8px;">
                            <table class="table modern-table" style="margin: 0; font-size: 0.9rem;">
                                <thead
                                    style="background: #f1f5f9; position: sticky; top: 0; z-index: 5; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                    <tr>
                                        <th style="padding: 12px 15px;">{{ __('Doküman Tipi Adı') }}</th>
                                        <th class="text-right" style="padding: 12px 15px; width: 100px;">{{ __('İşlem') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documentTypes->sortBy('category') as $type)
                                        <tr class="hover-row">
                                            <td style="padding: 12px 15px; vertical-align: middle;">
                                                <div
                                                    style="font-weight: 600; color: var(--primary-color); font-size: 0.95rem;">
                                                    {{ $type->name }}</div>
                                            </td>
                                            <td style="padding: 12px 15px; vertical-align: middle;">
                                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                    <button type="button" class="btn btn-sm btn-outline-primary action-btn"
                                                        onclick="editDocType({{ $type->id }}, '{{ $type->name }}', {{ json_encode($type->custom_fields ?? []) }}, {{ $type->requires_expiration_date ? 'true' : 'false' }})"
                                                        title="{{ __('Düzenle') }}">
                                                        <i data-lucide="edit"></i>
                                                    </button>

                                                    <form
                                                        action="{{ route('settings.document-types.destroy', $type->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('{{ __('Emin misiniz?') }}')"
                                                        style="margin: 0;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-danger action-btn"
                                                            title="{{ __('Sil') }}">
                                                            <i data-lucide="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="flex: 1 1 50%; min-width: 400px; padding: 24px;">
                        <form id="docTypeForm" action="{{ route('settings.document-types.store') }}" method="POST"
                            class="p-3"
                            style="background: #f8fafc; border-radius: 8px; border: 1px solid var(--border-color); height: 100%;">
                            @csrf
                            <div id="methodSpoofer"></div>

                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h5 id="formTitle"
                                    style="margin: 0; color: var(--primary-color); font-size: 1.05rem; display: flex; align-items: center; gap: 6px;">
                                    ✨ {{ __('Yeni Doküman Tipi Ekle') }}
                                </h5>
                                <button type="button" id="cancelEditBtn" class="btn btn-sm btn-outline-secondary"
                                    style="display: none; padding: 4px 10px; font-weight: 500;">
                                    {{ __('Vazgeç & Yeni Ekle') }}
                                </button>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label
                                        style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block;">{{ __('Doküman Tipi Adı') }}
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="dtName" class="form-control"
                                        placeholder="{{ __('Örn: Sözleşme') }}" required
                                        style="padding: 10px; border-radius: 6px;">
                                </div>
                                <div class="col-md-6" style="display: flex; align-items: flex-end; padding-bottom: 5px;">
                                    <label
                                        style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: var(--text-color); cursor: pointer; margin: 0;">
                                        <input type="checkbox" name="requires_expiration_date" id="dtRequiresExp"
                                            value="1"
                                            style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                                        {{ __('Geçerlilik Tarihi Zorunlu Olsun') }}
                                    </label>
                                </div>
                            </div>

                            <div style="padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <label
                                        style="font-size: 0.9rem; font-weight: 600; color: var(--secondary-color); margin: 0;">{{ __('Dinamik Form Alanları (Opsiyonel)') }}</label>
                                    <button type="button" id="addCustomFieldBtn"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                        style="font-weight: 500;">
                                        <i data-lucide="plus" style="width: 14px;"></i> {{ __('Alan Ekle') }}
                                    </button>
                                </div>

                                <div id="customFieldsWrapper" class="custom-scrollbar"
                                    style="display: flex; flex-direction: column; gap: 10px; max-height: 250px; overflow-y: auto; padding-right: 5px;">
                                </div>
                            </div>

                            <div style="margin-top: 25px; text-align: right;">
                                <button type="submit" id="submitDocTypeBtn"
                                    class="btn btn-primary d-inline-flex align-items-center gap-2"
                                    style="padding: 10px 25px; font-weight: 500;">
                                    <i data-lucide="save" style="width: 18px;"></i> {{ __('Kaydet') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. KART: TESİS VE DEPARTMANLAR --}}
        <div class="card glass-card admin-card-half"
            style="border-top: 4px solid var(--primary-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="card-header flex-between"
                style="background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                <h4
                    style="margin: 0; font-size: 1.1rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="building-2" style="width: 20px; color: var(--primary-color);"></i>
                    {{ __('Tesis ve Departmanlar') }}
                </h4>
            </div>
            <div class="card-body p-0 d-flex flex-column" style="height: 100%;">
                <div style="padding: 20px; background: #fafaf9; border-bottom: 1px solid var(--border-color);">
                    <form action="{{ route('settings.departments.store') }}" method="POST"
                        style="display: flex; gap: 12px; align-items: stretch;">
                        @csrf
                        <input type="text" name="unit" class="form-control"
                            placeholder="{{ __('Tesis (Örn: Preform)') }}" required style="flex: 1; border-radius: 6px;"
                            list="unit-list">
                        <datalist id="unit-list">
                            <option value="{{ __('Merkez') }}">
                            <option value="Preform">
                            <option value="Levha">
                        </datalist>
                        <input type="text" name="name" class="form-control"
                            placeholder="{{ __('Departman Adı') }}" required style="flex: 1.5; border-radius: 6px;">
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center"
                            style="padding: 0 20px; font-weight: 500;">{{ __('Ekle') }}</button>
                    </form>
                </div>
                <div class="table-responsive custom-scrollbar flex-grow-1" style="max-height: 350px;">
                    <table class="table modern-table" style="margin: 0; font-size: 0.9rem;">
                        <thead
                            style="background: #fff; position: sticky; top: 0; z-index: 5; box-shadow: 0 1px 0 var(--border-color);">
                            <tr>
                                <th style="padding: 12px 20px;">{{ __('Tesis') }}</th>
                                <th style="padding: 12px 20px;">{{ __('Departman') }}</th>
                                <th class="text-right" style="padding: 12px 20px; width: 80px;">{{ __('İşlem') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($departments->sortBy('unit') as $dept)
                                <tr class="hover-row">
                                    <td style="padding: 12px 20px; vertical-align: middle;">
                                        <span class="badge"
                                            style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 5px 8px;">{{ $dept->unit ?? __('Merkez') }}</span>
                                    </td>
                                    <td style="padding: 12px 20px; font-weight: 500; vertical-align: middle;">
                                        {{ $dept->name }}</td>
                                    <td style="padding: 12px 20px; vertical-align: middle;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button type="button" class="btn btn-sm btn-outline-primary action-btn"
                                                title="{{ __('Düzenle') }}"
                                                onclick="openEditDeptModal('{{ route('settings.departments.update', $dept->id) }}', '{{ $dept->unit ?? '' }}', '{{ $dept->name }}')">
                                                <i data-lucide="edit-2" style="width: 16px;"></i>
                                            </button>
                                            <form action="{{ route('settings.departments.destroy', $dept->id) }}"
                                                method="POST" onsubmit="return confirm('{{ __('Emin misiniz?') }}')"
                                                style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn"
                                                    title="{{ __('Sil') }}">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
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

        {{-- 3. KART: KULLANICI ROLLERİ --}}
        <div class="card glass-card admin-card-half"
            style="border-top: 4px solid var(--success-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="card-header flex-between"
                style="background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                <h4
                    style="margin: 0; font-size: 1.1rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="users" style="width: 20px; color: var(--success-color);"></i>
                    {{ __('Kullanıcı Rolleri') }}
                </h4>
            </div>
            <div class="card-body p-0 d-flex flex-column" style="height: 100%;">
                <div style="padding: 20px; background: #f0fdf4; border-bottom: 1px solid var(--border-color);">
                    <form action="{{ route('settings.roles.store') }}" method="POST"
                        style="display: flex; gap: 12px; align-items: stretch;">
                        @csrf
                        <input type="text" name="name" class="form-control"
                            placeholder="{{ __('Yeni Rol Adı (Örn: Finans)') }}" required
                            style="flex: 1; border-radius: 6px;">
                        <input type="number" name="hierarchy_level" class="form-control"
                            placeholder="{{ __('Hiyerarşi Seviyesi (Örn: 10)') }}" min="0" required
                            style="flex: 1; border-radius: 6px;"
                            title="{{ __('Bu rolün onay ve bildirim zincirindeki gücünü belirler (Büyük sayı = Üst düzey)') }}">
                        <button type="submit" class="btn btn-success d-flex align-items-center justify-content-center"
                            style="padding: 0 20px; font-weight: 500;">{{ __('Rol Ekle') }}</button>
                    </form>
                </div>
                <div class="table-responsive custom-scrollbar flex-grow-1" style="max-height: 350px;">
                    <table class="table modern-table" style="margin: 0; font-size: 0.9rem;">
                        <thead
                            style="background: #fff; position: sticky; top: 0; z-index: 5; box-shadow: 0 1px 0 var(--border-color);">
                            <tr>
                                <th style="padding: 12px 20px;">{{ __('Rol Adı') }}</th>
                                {{-- YENİ EKLENEN SÜTUN BAŞLIĞI --}}
                                <th style="padding: 12px 20px; text-align: center;">{{ __('Hiyerarşi') }}</th>
                                <th class="text-right" style="padding: 12px 20px; width: 80px;">{{ __('İşlem') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr class="hover-row">
                                    <td
                                        style="padding: 15px 20px; font-weight: 600; color: var(--text-color); vertical-align: middle;">
                                        {{ $role->name }}
                                        @if (in_array($role->name, ['Super Admin', 'Admin']))
                                            <span class="badge badge-warning"
                                                style="margin-left: 8px; font-size: 0.7rem; padding: 4px 6px; border-radius: 4px; background: #fef08a; color: #854d0e;">{{ __('Sistem') }}
                                                🔒</span>
                                        @endif
                                    </td>

                                    {{-- YENİ EKLENEN SÜTUN VERİSİ (Rozet tasarımıyla) --}}
                                    <td style="padding: 12px 20px; text-align: center; vertical-align: middle;">
                                        <span class="badge"
                                            style="background: #e2e8f0; color: #475569; padding: 5px 10px; font-size: 0.85rem; border-radius: 6px; font-weight: 700;">
                                            {{ $role->hierarchy_level ?? 0 }}
                                        </span>
                                    </td>

                                    <td style="padding: 12px 20px; vertical-align: middle;">
                                        @if (!in_array($role->name, ['Super Admin', 'Admin']))
                                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                <button type="button" class="btn btn-sm btn-outline-primary action-btn"
                                                    title="{{ __('Düzenle') }}"
                                                    onclick="openEditRoleModal('{{ route('settings.roles.update', $role->id) }}', '{{ $role->name }}', '{{ $role->hierarchy_level ?? 0 }}')">
                                                    <i data-lucide="edit-2" style="width: 16px;"></i>
                                                </button>
                                                <form action="{{ route('settings.roles.destroy', $role->id) }}"
                                                    method="POST" onsubmit="return confirm('{{ __('Emin misiniz?') }}')"
                                                    style="margin: 0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger action-btn"
                                                        title="{{ __('Sil') }}">
                                                        <i data-lucide="trash-2"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div style="text-align: right;">
                                                <span class="text-muted"
                                                    style="font-size: 0.8rem; font-style: italic;">{{ __('Silinemez') }}</span>
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

        {{-- 4. KART: GİZLİLİK SEVİYELERİ --}}
        <div class="card glass-card admin-card-half"
            style="border-top: 4px solid var(--warning-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="card-header flex-between"
                style="background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                <h4
                    style="margin: 0; font-size: 1.1rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="shield" style="width: 20px; color: var(--warning-color);"></i>
                    {{ __('Gizlilik Seviyeleri (Kalkanlar)') }}
                </h4>
            </div>
            <div class="card-body p-0 d-flex flex-column" style="height: 100%;">
                <div style="padding: 20px; background: #fffbeb; border-bottom: 1px solid var(--border-color);">
                    <form action="{{ route('settings.privacy-levels.store') }}" method="POST"
                        style="display: flex; gap: 12px; align-items: stretch; flex-wrap: wrap;">
                        @csrf
                        <input type="text" name="key" class="form-control"
                            placeholder="{{ __('Sistem Kodu (Örn: board_only)') }}" required
                            style="flex: 1; min-width: 150px; border-radius: 6px; font-family: monospace; font-size: 0.85rem;">
                        <input type="text" name="label" class="form-control"
                            placeholder="{{ __('Görünen Ad (Örn: Sadece Yönetim Kurulu)') }}" required
                            style="flex: 1.5; min-width: 200px; border-radius: 6px;">
                        <button type="submit" class="btn btn-warning d-flex align-items-center justify-content-center"
                            style="padding: 0 20px; font-weight: 600; color: #92400e;">{{ __('Ekle') }}</button>
                    </form>
                </div>
                <div class="table-responsive custom-scrollbar flex-grow-1" style="max-height: 350px;">
                    <table class="table modern-table" style="margin: 0; font-size: 0.9rem;">
                        <thead
                            style="background: #fff; position: sticky; top: 0; z-index: 5; box-shadow: 0 1px 0 var(--border-color);">
                            <tr>
                                <th style="padding: 12px 20px;">{{ __('Görünen Ad') }}</th>
                                <th style="padding: 12px 20px;">{{ __('Sistem Kodu') }}</th>
                                <th class="text-right" style="padding: 12px 20px; width: 80px;">{{ __('İşlem') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($privacyLevels as $key => $label)
                                <tr class="hover-row">
                                    <td
                                        style="padding: 12px 20px; font-weight: 600; color: var(--text-color); vertical-align: middle;">
                                        {{ __($label) }}
                                        @if (in_array($key, ['public', 'confidential', 'strictly_confidential']))
                                            <span class="badge badge-secondary"
                                                style="margin-left: 8px; font-size: 0.7rem; padding: 4px 6px; border-radius: 4px;">{{ __('Sistem') }}
                                                🔒</span>
                                        @endif
                                    </td>
                                    <td
                                        style="padding: 12px 20px; font-family: monospace; color: var(--text-muted); vertical-align: middle;">
                                        {{ $key }}
                                    </td>
                                    <td style="padding: 12px 20px; vertical-align: middle;">
                                        @if (!in_array($key, ['public', 'confidential', 'strictly_confidential']))
                                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                <form action="{{ route('settings.privacy-levels.destroy', $key) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('{{ __('Bu gizlilik seviyesini silmek istediğinize emin misiniz?') }}')"
                                                    style="margin: 0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger action-btn"
                                                        title="{{ __('Sil') }}">
                                                        <i data-lucide="trash-2"></i>
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

    {{-- MATRİSLER BÖLÜMÜ --}}
    <div class="form-section-divider mb-20" style="border-top: 2px solid #e2e8f0; padding-top: 20px;">
        <h3
            style="color: var(--secondary-color); font-size: 1.2rem; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="shield-check" style="width: 22px;"></i> 2. {{ __('Güvenlik ve Erişim Matrisleri') }}
        </h3>
    </div>

    {{-- KLASÖR YETKİ MATRİSİ --}}
    <div class="card glass-card mb-30"
        style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
        <div class="card-header" style="background: #f0fdfa; border-bottom: 2px solid #14b8a6; padding: 20px;">
            <h2 style="margin: 0; font-size: 1.2rem; color: #0f766e; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="folder-lock"></i> {{ __('Dinamik Klasör Yetki Matrisi') }}
            </h2>
            <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #0d9488;">
                {{ __('Hangi rolün hangi klasörü görebileceğini veya seçilen klasörün içine belge yükleyebileceğini seçin.') }}
            </p>
        </div>
        <div class="card-body" style="padding: 25px; background: #fff;">
            <div class="form-group mb-20">
                <label
                    style="font-weight: 600; margin-bottom: 8px; display: block; color: var(--text-color);">{{ __('İşlem Yapılacak Klasörü Seçin') }}</label>
                <select id="folderMatrixSelect" class="form-control"
                    style="width: 100%; max-width: 400px; padding: 12px; border-radius: 8px; border: 2px solid var(--border-color); font-weight: 500;">
                    <option value="">{{ __('-- Lütfen Bir Klasör Seçin --') }}</option>
                    @foreach (\App\Models\Folder::orderBy('name')->get() as $folder)
                        <option value="{{ $folder->id }}">
                            {{ $folder->prefix ? "[$folder->prefix] " : '' }}{{ $folder->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="folderMatrixLoader"
                style="display: none; color: var(--primary-color); font-weight: 500; margin-bottom: 15px;">
                <i data-lucide="loader" class="spin" style="width: 18px; vertical-align: text-bottom;"></i>
                {{ __('Yetkiler Çekiliyor...') }}
            </div>
            <form id="folderMatrixForm" method="POST" action=""
                style="display: none; animation: fadeIn 0.3s ease;">
                @csrf
                <div class="table-responsive"
                    style="border-radius: 8px; border: 1px solid var(--border-color); overflow: hidden;">
                    <table class="table modern-table" style="width: 100%; margin: 0;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 15px; font-weight: 600;">{{ __('Sistem Rolleri') }}</th>
                                <th class="text-center" style="padding: 15px;"><i data-lucide="eye"
                                        style="width: 16px;"></i> {{ __('Görüntüle') }}</th>
                                <th class="text-center" style="padding: 15px;"><i data-lucide="upload"
                                        style="width: 16px;"></i> {{ __('Belge Yükle') }}</th>
                                <th class="text-center" style="padding: 15px;"><i data-lucide="folder-plus"
                                        style="width: 16px;"></i> {{ __('Alt Klasör Aç') }}</th>
                                <th class="text-center" style="padding: 15px;"><i data-lucide="settings"
                                        style="width: 16px;"></i> {{ __('Yönet (Sil/Düz)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\Spatie\Permission\Models\Role::where('name', '!=', 'Super Admin')->get() as $role)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 15px; font-weight: 600; color: var(--text-color);">
                                        {{ $role->name }}</td>
                                    <td class="text-center"><input type="checkbox"
                                            name="permissions[{{ $role->id }}][can_view]"
                                            id="can_view_{{ $role->id }}" class="folder-matrix-cb"
                                            style="width: 18px; height: 18px; accent-color: #14b8a6;"></td>
                                    <td class="text-center"><input type="checkbox"
                                            name="permissions[{{ $role->id }}][can_upload]"
                                            id="can_upload_{{ $role->id }}" class="folder-matrix-cb"
                                            style="width: 18px; height: 18px; accent-color: #14b8a6;"></td>
                                    <td class="text-center"><input type="checkbox"
                                            name="permissions[{{ $role->id }}][can_create_subfolder]"
                                            id="can_subfolder_{{ $role->id }}" class="folder-matrix-cb"
                                            style="width: 18px; height: 18px; accent-color: #14b8a6;"></td>
                                    <td class="text-center"><input type="checkbox"
                                            name="permissions[{{ $role->id }}][can_manage]"
                                            id="can_manage_{{ $role->id }}" class="folder-matrix-cb"
                                            style="width: 18px; height: 18px; accent-color: var(--danger-color);"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn btn-success"
                        style="padding: 10px 25px; font-size: 1rem; display: inline-flex; align-items: center; gap: 8px;">
                        <i data-lucide="save" style="width: 18px;"></i> {{ __('Seçili Klasörün Yetkilerini Kaydet') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- DOKÜMAN TİPİ VE GLOBAL YETKİLER --}}
    <form action="{{ route('settings.permissions.update') }}" method="POST" id="globalPermissionsForm">
        @csrf
        <div class="card glass-card mb-30"
            style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
            <div class="card-header flex-between"
                style="background: #eff6ff; border-bottom: 2px solid #3b82f6; padding: 20px;">
                <div>
                    <h2
                        style="margin: 0; font-size: 1.2rem; color: #1d4ed8; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="layers"></i> {{ __('Doküman Tipi Matrisi ve Global Yetkiler') }}
                    </h2>
                    <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #1e40af;">
                        {{ __('Rollerin sistemdeki genel sınırlarını ve doküman tiplerine göre yetkilerini belirleyin.') }}
                    </p>
                </div>
                <button type="submit" class="btn btn-primary"
                    style="padding: 10px 25px; font-size: 1rem; font-weight: 600; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);">
                    <i data-lucide="save" style="width: 18px; margin-right: 5px; vertical-align: text-bottom;"></i>
                    {{ __('Tüm Matrisi Kaydet') }}
                </button>
            </div>

            <div class="card-body p-0">
                {{-- KILAVUZ BÖLÜMÜ --}}
                <div
                    style="padding: 20px 25px; background: #f0fdf4; border-bottom: 1px solid #ccfbf1; display: flex; gap: 15px; align-items: flex-start;">
                    <div style="background: #ccfbf1; color: #0f766e; padding: 12px; border-radius: 10px; flex-shrink: 0;">
                        <i data-lucide="help-circle" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 8px 0; color: #115e59; font-size: 1.05rem;">💡
                            {{ __('Kılavuz: Hangi Tablo Ne İşe Yarıyor?') }}</h4>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <p style="margin: 0; font-size: 0.9rem; color: #0f766e; line-height: 1.5;">
                                <strong style="color: #b91c1c;">{{ __('1. Kırmızı Çizgi :') }}</strong>
                                {{ __('Bu tablo sistemdeki süper yetkileri belirler. Buradan verilen bir yetki, her türlü kuralı ezer geçer! Sadece çok üst düzey yöneticilere verilmelidir.') }}
                            </p>
                            <p style="margin: 0; font-size: 0.9rem; color: #0f766e; line-height: 1.5;">
                                <strong style="color: #1d4ed8;">{{ __('2. Doküman Tipine Özel Yetkiler:') }}</strong>
                                {{ __('Bu tablo günlük işleyişi belirler. Sadece belirli bir belgede kimin ne yapabileceğini ayarlarsınız.') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- 1. KIRMIZI ÇİZGİ: GLOBAL KALKANLAR --}}
                <div style="padding: 20px; background: #fff5f5; border-bottom: 1px solid #fecaca;">
                    <h4 style="margin: 0 0 10px 0; color: #b91c1c; font-size: 1rem;"><i data-lucide="alert-octagon"
                            style="width: 16px;"></i> {{ __('1. Kırmızı Çizgi: Global Kalkanlar') }}</h4>
                    <div class="table-responsive" style="border: 1px solid #fca5a5; border-radius: 8px;">
                        <table class="table modern-table" style="margin: 0;">
                            <thead style="background: #fef2f2; border-bottom: 2px solid #fecaca;">
                                <tr>
                                    <th style="width: 180px; padding: 15px; vertical-align: top;">
                                        <div style="font-weight: 700; color: #991b1b;">{{ __('Sistem Rolü') }}</div>
                                    </th>
                                    @foreach ($specialPermissions as $sp)
                                        <th class="text-center" style="padding: 15px; vertical-align: top; width: 20%;">
                                            @if ($sp->name == 'document.view_strictly_confidential')
                                                <div
                                                    style="font-weight: 700; color: #b91c1c; margin-bottom: 6px; font-size: 0.9rem;">
                                                    🕵️ {{ __('"Çok Gizli" Erişimi') }}
                                                </div>
                                            @elseif($sp->name == 'document.view_all')
                                                <div
                                                    style="font-weight: 700; color: #1d4ed8; margin-bottom: 6px; font-size: 0.9rem;">
                                                    🌍 {{ __('Tüm Belgeleri Görüntüleme') }}
                                                </div>
                                            @elseif($sp->name == 'document.manage_all')
                                                <div
                                                    style="font-weight: 700; color: #047857; margin-bottom: 6px; font-size: 0.9rem;">
                                                    👑 {{ __('Tüm Belgeleri Yönetme') }}
                                                </div>
                                            @elseif($sp->name == 'document.force_unlock')
                                                <div
                                                    style="font-weight: 700; color: #b45309; margin-bottom: 6px; font-size: 0.9rem;">
                                                    ⚠️ {{ __('Kilit Açma Yetkisi') }}
                                                </div>

                                                {{-- YENİ EKLENEN DİNAMİK UI BLOĞU --}}
                                            @elseif(str_starts_with($sp->name, 'document.view_'))
                                                @php
                                                    // Yetki adından (örn: document.view_board_only) -> 'board_only' kısmını ayır
                                                    $privacyKey = str_replace('document.view_', '', $sp->name);

                                                    // Controller'dan gelen $privacyLevels dizisinden gerçek adını bul, bulamazsa key'i yaz
                                                    $privacyLabel =
                                                        isset($privacyLevels) && isset($privacyLevels[$privacyKey])
                                                            ? $privacyLevels[$privacyKey]
                                                            : $privacyKey;
                                                @endphp
                                                <div
                                                    style="font-weight: 700; color: #6d28d9; margin-bottom: 6px; font-size: 0.9rem;">
                                                    🛡️ {{ __($privacyLabel) }} {{ __('Erişimi') }}
                                                </div>

                                                {{-- EĞER SİSTEME BAŞKA BİR YETKİ EKLENİRSE (FALLBACK) --}}
                                            @elseif($sp->name == 'notify.global')
                                                <div
                                                    style="font-weight: 700; color: #0284c7; margin-bottom: 6px; font-size: 0.9rem;">
                                                    🌐 {{ __('Küresel Bildirim Yetkisi') }}
                                                </div>
                                            @else
                                                <div
                                                    style="font-weight: 700; color: #475569; margin-bottom: 6px; font-size: 0.85rem; font-family: monospace;">
                                                    {{ $sp->name }}
                                                </div>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody style="background: #fff;">
                                @foreach ($roles as $role)
                                    @if ($role->name !== 'Super Admin')
                                        <tr>
                                            <td style="font-weight: bold; color: var(--primary-color); padding: 12px;">
                                                {{ $role->name }}</td>
                                            @foreach ($specialPermissions as $sp)
                                                <td class="text-center">
                                                    <input type="checkbox"
                                                        name="special_permissions[{{ $role->id }}][]"
                                                        value="{{ $sp->name }}"
                                                        {{ $role->hasPermissionTo($sp->name) ? 'checked' : '' }}
                                                        style="accent-color: var(--danger-color); width: 18px; height: 18px; cursor: pointer;">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 2. DOKÜMAN TİPİ MATRİSİ --}}
                <div style="padding: 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 10px 0; color: #1d4ed8; font-size: 1rem;"><i data-lucide="file-text"
                            style="width: 16px;"></i> {{ __('2. Doküman Tipine Özel Yetkiler') }}</h4>
                    <label
                        style="font-weight: 600; margin-bottom: 8px; display: block; color: var(--text-muted); font-size: 0.85rem;">{{ __('Yetkilerini Düzenlemek İstediğiniz Doküman Tipini Seçin') }}</label>
                    <select id="docTypeMatrixSelect" class="form-control"
                        style="width: 100%; max-width: 400px; padding: 10px; border-radius: 8px; border: 2px solid var(--border-color); font-weight: 600; cursor: pointer; color: var(--primary-color);">
                        <option value="">{{ __('-- Lütfen Bir Doküman Tipi Seçin --') }}</option>
                        @foreach ($documentTypes as $type)
                            <option value="matrix-{{ $type->slug }}">📂 {{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="docTypeMatrixLoader"
                    style="display: none; padding: 30px; text-align: center; color: #3b82f6; font-weight: 500;">
                    <i data-lucide="loader" class="spin"
                        style="width: 24px; vertical-align: text-bottom; margin-right: 8px;"></i>
                    {{ __('Yetkiler Çekiliyor...') }}
                </div>

                @php
                    $allMatrixRules = \Illuminate\Support\Facades\DB::table('role_category_permissions')->get();
                @endphp

                @foreach ($documentTypes as $type)
                    <div class="matrix-content" id="matrix-{{ $type->slug }}"
                        style="display: none; padding: 20px; background: #fff; animation: fadeIn 0.3s ease;">
                        <div
                            style="margin-bottom: 15px; padding: 10px 15px; background: #eff6ff; border-radius: 6px; border-left: 4px solid #3b82f6;">
                            <strong style="color: #1e40af;">{{ __('Seçili Kategori:') }} {{ $type->name }}</strong> -
                            <i>{{ __('Aşağıdaki yetkiler sadece bu tipteki belgeler için geçerli olacaktır.') }}</i>
                        </div>
                        <table class="table modern-table matrix-table">
                            <thead style="background: #f1f5f9;">
                                <tr>
                                    <th style="padding: 12px;">{{ __('Sistem Rolü') }}</th>
                                    <th class="text-center" style="padding: 12px;">👁️ {{ __('Görüntüleme') }}</th>
                                    <th class="text-center" style="padding: 12px;">📤 {{ __('Yükleme') }}</th>
                                    <th class="text-center" style="padding: 12px;">📝 {{ __('Revize Etme') }}</th>
                                    <th class="text-center" style="padding: 12px;">🗑️ {{ __('İmha (Silme)') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    @if (!in_array($role->name, ['Super Admin', 'Admin']))
                                        <tr>
                                            <td class="font-bold" style="padding: 12px; color: var(--text-color);">
                                                {{ $role->name }}</td>
                                            @php
                                                $rule = $allMatrixRules
                                                    ->filter(function ($item) use ($role, $type) {
                                                        return $item->role_id == $role->id &&
                                                            $item->category == $type->name;
                                                    })
                                                    ->first();
                                            @endphp
                                            <td class="text-center" style="padding: 12px;">
                                                <input type="checkbox"
                                                    name="permissions[{{ $role->id }}][{{ $type->name }}][can_view]"
                                                    value="1" {{ $rule && $rule->can_view ? 'checked' : '' }}
                                                    style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                                            </td>
                                            <td class="text-center" style="padding: 12px;">
                                                <input type="checkbox"
                                                    name="permissions[{{ $role->id }}][{{ $type->name }}][can_create]"
                                                    value="1" {{ $rule && $rule->can_create ? 'checked' : '' }}
                                                    style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                                            </td>
                                            <td class="text-center" style="padding: 12px;">
                                                <input type="checkbox"
                                                    name="permissions[{{ $role->id }}][{{ $type->name }}][can_edit]"
                                                    value="1" {{ $rule && $rule->can_edit ? 'checked' : '' }}
                                                    style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                                            </td>
                                            <td class="text-center" style="padding: 12px;">
                                                <input type="checkbox"
                                                    name="permissions[{{ $role->id }}][{{ $type->name }}][can_delete]"
                                                    value="1" {{ $rule && $rule->can_delete ? 'checked' : '' }}
                                                    style="width: 18px; height: 18px; accent-color: var(--danger-color);">
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            {{-- NAVBAR VE MENÜ MATRİSİ --}}
            <div class="card glass-card mt-30" style="border-top: 4px solid var(--primary-color);">
                <div class="card-header"
                    style="background: #f8fafc; padding: 20px 25px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="compass" style="color: var(--primary-color);"></i>
                    <h2 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--text-color);">Navbar ve Menü
                        Erişim Matrisi</h2>
                </div>

                <div class="card-body" style="padding: 0; overflow-x: auto;">
                    <table class="table modern-table"
                        style="width: 100%; min-width: 800px; margin: 0; border-collapse: collapse;">
                        <thead
                            style="background: var(--bg-color); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid var(--border-color);">
                            <tr>
                                <th
                                    style="padding: 15px 25px; text-align: left; position: sticky; left: 0; background: var(--bg-color); z-index: 10;">
                                    Roller</th>
                                @foreach ($menuPermissions as $menuPerm)
                                    <th style="padding: 15px; text-align: center;">
                                        {{ ucfirst(str_replace('menu.', '', $menuPerm->name)) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                @if ($role->name === 'Super Admin')
                                    @continue
                                @endif
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;">
                                    <td
                                        style="padding: 15px 25px; font-weight: 600; color: var(--text-color); position: sticky; left: 0; background: #fff; z-index: 10; border-right: 1px solid var(--border-color);">
                                        {{ $role->name }}
                                    </td>
                                    @foreach ($menuPermissions as $menuPerm)
                                        <td style="padding: 15px; text-align: center;">
                                            <input type="checkbox" name="menu_permissions[{{ $role->id }}][]"
                                                value="{{ $menuPerm->name }}"
                                                {{ $role->hasPermissionTo($menuPerm->name) ? 'checked' : '' }}
                                                style="width: 18px; height: 18px; accent-color: var(--primary-color); cursor: pointer;">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div
                        style="padding: 15px 25px; background: #f8fafc; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
                        <i data-lucide="info"
                            style="width: 16px; display: inline-block; vertical-align: text-bottom;"></i>
                        <strong>Super Admin</strong> rolü Gate kuralları gereği tüm menülere sınırsız erişime sahip olduğu
                        için bu matriste gizlenmiştir.
                    </div>
                </div>
            </div>
        </div>
        {{-- DEPARTMAN DÜZENLEME MODALI --}}
        <div id="editDeptModal" class="modal-overlay"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
            <div class="modal-content"
                style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 450px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="margin:0; font-size: 1.2rem;">{{ __('Departman Düzenle') }}</h3>
                    <button type="button" onclick="closeSettingsModal('editDeptModal')"
                        style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <form id="editDeptForm" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label
                            style="font-weight: 500; font-size: 0.9rem; margin-bottom: 5px; display:block;">{{ __('Tesis Adı') }}</label>
                        <input type="text" name="unit" id="editDeptUnit" class="form-control" list="unit-list"
                            required style="border-radius: 6px; padding: 10px; width: 100%;">
                    </div>
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label
                            style="font-weight: 500; font-size: 0.9rem; margin-bottom: 5px; display:block;">{{ __('Departman Adı') }}</label>
                        <input type="text" name="name" id="editDeptName" class="form-control" required
                            style="border-radius: 6px; padding: 10px; width: 100%;">
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; padding: 12px;">{{ __('Değişiklikleri Kaydet') }}</button>
                </form>
            </div>
        </div>

        {{-- ROL DÜZENLEME MODALI --}}
        <div id="editRoleModal" class="modal-overlay"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
            <div class="modal-content"
                style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 450px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="margin:0; font-size: 1.2rem;">{{ __('Rol Düzenle') }}</h3>
                    <button type="button" onclick="closeSettingsModal('editRoleModal')"
                        style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <form id="editRoleForm" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label
                            style="font-weight: 500; font-size: 0.9rem; margin-bottom: 5px; display:block;">{{ __('Rol Adı') }}</label>
                        <input type="text" name="name" id="editRoleName" class="form-control" required
                            style="border-radius: 6px; padding: 10px; width: 100%;">
                    </div>
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label
                            style="font-weight: 500; font-size: 0.9rem; margin-bottom: 5px; display:block;">{{ __('Hiyerarşi Seviyesi') }}</label>
                        <input type="number" name="hierarchy_level" id="editRoleLevel" class="form-control" required
                            min="0" style="border-radius: 6px; padding: 10px; width: 100%;">
                    </div>
                    <button type="submit" class="btn btn-success"
                        style="width: 100%; padding: 12px;">{{ __('Değişiklikleri Kaydet') }}</button>
                </form>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // --- 1. DİNAMİK FORM ALANLARI (SLUGIFIER DAHİL) ---
            const wrapper = document.getElementById('customFieldsWrapper');
            const addBtn = document.getElementById('addCustomFieldBtn');
            let fieldIndex = 0;

            function addFieldRow(data = null) {
                const row = document.createElement('div');
                row.className = 'custom-field-row';
                row.style.cssText =
                    'display: grid; grid-template-columns: 2fr 2fr 1.5fr auto auto; gap: 10px; align-items: center; background: #f1f5f9; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 5px;';

                const labelValue = data ? data.label : '';
                const nameValue = data ? data.name : '';
                const typeValue = data ? data.type : 'text';
                const isRequired = (data && data.required) ? 'checked' : '';
                const autoSlug = data ? 'false' : 'true';

                row.innerHTML = `
            <div>
                <input type="text" name="custom_fields[${fieldIndex}][label]" class="form-control form-control-sm label-input" value="${labelValue}" placeholder="{{ __('Label') }}" required style="width: 100%; border-radius: 6px;">
            </div>
            <div>
                <input type="text" name="custom_fields[${fieldIndex}][name]" class="form-control form-control-sm key-input" value="${nameValue}" placeholder="{{ __('Key') }}" required data-auto="${autoSlug}" style="width: 100%; border-radius: 6px; font-family: monospace;">
            </div>
            <div>
                <select name="custom_fields[${fieldIndex}][type]" class="form-control form-control-sm" style="width: 100%; border-radius: 6px;">
                    <option value="text" ${typeValue === 'text' ? 'selected' : ''}>{{ __('Kısa Metin') }}</option>
                    <option value="number" ${typeValue === 'number' ? 'selected' : ''}>{{ __('Sayısal') }}</option>
                    <option value="date" ${typeValue === 'date' ? 'selected' : ''}>{{ __('Tarih') }}</option>
                    <option value="textarea" ${typeValue === 'textarea' ? 'selected' : ''}>{{ __('Uzun Metin') }}</option>
                </select>
            </div>
            <div style="text-align: center;">
                <input type="checkbox" name="custom_fields[${fieldIndex}][required]" value="1" ${isRequired} style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
            </div>
            <div style="text-align: right;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn"><i data-lucide="trash-2" style="width: 16px;"></i></button>
            </div>
        `;

                wrapper.appendChild(row);
                lucide.createIcons();

                const currentLabel = row.querySelector('.label-input');
                const currentKey = row.querySelector('.key-input');

                // OTOMATİK KEY VE "_" EKLEME MANTIĞI
                currentLabel.addEventListener('keyup', function() {
                    if (currentKey.getAttribute('data-auto') === 'true') {
                        currentKey.value = this.value.toLowerCase()
                            .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
                            .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
                            .replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                    }
                });

                currentKey.addEventListener('input', () => currentKey.setAttribute('data-auto', 'false'));
                row.querySelector('.remove-field-btn').addEventListener('click', () => row.remove());
                fieldIndex++;
            }

            if (addBtn && wrapper) {
                addBtn.addEventListener('click', () => addFieldRow());
            }

            window.editDocType = function(id, name, customFieldsJson, requiresExp) {
                document.getElementById('dtName').value = name || '';
                document.getElementById('dtRequiresExp').checked = requiresExp;
                const form = document.getElementById('docTypeForm');
                form.action = `{{ url('/settings/document-types') }}/${id}`;
                document.getElementById('methodSpoofer').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';
                document.getElementById('formTitle').innerHTML = `✏️ ${name} {{ __('Düzenleniyor') }}`;
                const submitBtn = document.getElementById('submitDocTypeBtn');
                submitBtn.innerHTML = '<i data-lucide="refresh-cw"></i> {{ __('Güncelle') }}';
                submitBtn.classList.replace('btn-primary', 'btn-warning');
                document.getElementById('cancelEditBtn').style.display = 'inline-block';
                wrapper.innerHTML = '';
                fieldIndex = 0;
                if (customFieldsJson && Array.isArray(customFieldsJson)) {
                    customFieldsJson.forEach(field => addFieldRow(field));
                }
                lucide.createIcons();
            };

            const cancelBtn = document.getElementById('cancelEditBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    document.getElementById('dtName').value = '';
                    document.getElementById('dtRequiresExp').checked = false;
                    wrapper.innerHTML = '';
                    fieldIndex = 0;
                    document.getElementById('docTypeForm').action =
                        "{{ route('settings.document-types.store') }}";
                    document.getElementById('methodSpoofer').innerHTML = '';
                    document.getElementById('formTitle').innerHTML =
                        '✨ {{ __('Yeni Doküman Tipi Ekle') }}';
                    const submitBtn = document.getElementById('submitDocTypeBtn');
                    submitBtn.innerHTML = '<i data-lucide="save"></i> {{ __('Kaydet') }}';
                    submitBtn.classList.replace('btn-warning', 'btn-primary');
                    this.style.display = 'none';
                    lucide.createIcons();
                });
            }

            // --- MATRİS SEÇİCİLER ---
            const docTypeSelect = document.getElementById('docTypeMatrixSelect');
            if (docTypeSelect) {
                docTypeSelect.addEventListener('change', function() {
                    document.querySelectorAll('.matrix-content').forEach(c => c.style.display = 'none');
                    const target = document.getElementById(this.value);
                    if (target) {
                        document.getElementById('docTypeMatrixLoader').style.display = 'block';
                        setTimeout(() => {
                            document.getElementById('docTypeMatrixLoader').style.display = 'none';
                            target.style.display = 'block';
                        }, 400);
                    }
                });
            }

            const folderSelect = document.getElementById('folderMatrixSelect');
            if (folderSelect) {
                folderSelect.addEventListener('change', function() {
                    const id = this.value;
                    const form = document.getElementById('folderMatrixForm');
                    const loader = document.getElementById('folderMatrixLoader');
                    if (!id) {
                        form.style.display = 'none';
                        return;
                    }
                    loader.style.display = 'block';
                    form.style.display = 'none';

                    // Laravel'in .env dosyasından tam adresi alıp sonuna hedefimizi ekliyoruz
                    let targetUrl = "{{ rtrim(config('app.url'), '/') }}/settings/folders/" + id +
                        "/permissions";

                    // Hem formu göndereceğimiz POST adresi, hem de veriyi çekeceğimiz GET adresi aynı olmalı!
                    form.action = targetUrl;
                    let fetchUrl = targetUrl;

                    console.log("TAM İSABET ADRES: ", fetchUrl);

                    fetch(fetchUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            loader.style.display = 'none';
                            form.style.display = 'block';
                            document.querySelectorAll('.folder-matrix-cb').forEach(cb => cb.checked =
                                false);
                            for (let roleId in data) {
                                if (data[roleId].can_view) document.getElementById(`can_view_${roleId}`)
                                    .checked = true;
                                if (data[roleId].can_upload) document.getElementById(
                                    `can_upload_${roleId}`).checked = true;
                                if (data[roleId].can_create_subfolder) document.getElementById(
                                    `can_subfolder_${roleId}`).checked = true;
                                if (data[roleId].can_manage) document.getElementById(
                                    `can_manage_${roleId}`).checked = true;
                            }
                        });
                });
            }
            // Departman Modalı Açıcı (Global Scope'a taşındı)
            window.openEditDeptModal = function(actionUrl, unit, name) {
                document.getElementById('editDeptForm').action = actionUrl;
                document.getElementById('editDeptUnit').value = unit;
                document.getElementById('editDeptName').value = name;
                document.getElementById('editDeptModal').style.display = 'flex';
            };

            // Rol Modalı Açıcı (Global Scope'a taşındı)
            window.openEditRoleModal = function(actionUrl, name, level) {
                document.getElementById('editRoleForm').action = actionUrl;
                document.getElementById('editRoleName').value = name;
                document.getElementById('editRoleLevel').value = level;
                document.getElementById('editRoleModal').style.display = 'flex';
            };

            // Modalları Kapatıcı (Global Scope'a taşındı)
            window.closeSettingsModal = function(modalId) {
                document.getElementById(modalId).style.display = 'none';
            };
        });
    </script>
    <style>
        .admin-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
        }

        .admin-card-full {
            grid-column: span 12;
        }

        .admin-card-half {
            grid-column: span 6;
        }

        @media (max-width: 992px) {
            .admin-card-half {
                grid-column: span 12;
            }
        }

        .hover-row:hover {
            background-color: #f8fafc;
            transition: 0.2s;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush
