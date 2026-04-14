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
                                                        onclick="editDocType({{ $type->id }}, '{{ $type->name }}', {{ json_encode($type->custom_fields ?? []) }})"
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
                                    <td style="padding: 12px 20px; text-align: right; vertical-align: middle;">
                                        <form action="{{ route('settings.departments.destroy', $dept->id) }}"
                                            method="POST" onsubmit="return confirm('{{ __('Emin misiniz?') }}')"
                                            style="margin: 0;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger action-btn"
                                                title="{{ __('Sil') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                                    <td style="padding: 12px 20px; text-align: right; vertical-align: middle;">
                                        @if (!in_array($role->name, ['Super Admin', 'Admin']))
                                            <form action="{{ route('settings.roles.destroy', $role->id) }}"
                                                method="POST" onsubmit="return confirm('{{ __('Emin misiniz?') }}')"
                                                style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn"
                                                    title="{{ __('Sil') }}">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted"
                                                style="font-size: 0.8rem; font-style: italic;">{{ __('Silinemez') }}</span>
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
    <div class="form-section-divider mb-20" style="border-top: 2px solid #e2e8f0; padding-top: 20px;">
        <h3
            style="color: var(--secondary-color); font-size: 1.2rem; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="shield-check" style="width: 22px;"></i> 2. {{ __('Güvenlik ve Erişim Matrisleri') }}
        </h3>
    </div>

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
                                                    🕵️ {{ __('"Çok Gizli" Erişimi') }}</div>
                                            @elseif($sp->name == 'document.view_all')
                                                <div
                                                    style="font-weight: 700; color: #1d4ed8; margin-bottom: 6px; font-size: 0.9rem;">
                                                    🌍 {{ __('Tüm Belgeleri Görüntüleme') }}</div>
                                            @elseif($sp->name == 'document.manage_all')
                                                <div
                                                    style="font-weight: 700; color: #047857; margin-bottom: 6px; font-size: 0.9rem;">
                                                    👑 {{ __('Tüm Belgeleri Yönetme') }}</div>
                                            @elseif($sp->name == 'document.force_unlock')
                                                <div
                                                    style="font-weight: 700; color: #b45309; margin-bottom: 6px; font-size: 0.9rem;">
                                                    ⚠️ {{ __('Kilit Açma Yetkisi') }}</div>
                                            @else
                                                <div style="font-weight: 700; margin-bottom: 6px;">{{ $sp->name }}
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

                <div style="padding: 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 10px 0; color: #1d4ed8; font-size: 1rem;"><i data-lucide="file-text"
                            style="width: 16px;"></i> {{ __('2. Doküman Tipine Özel Yetkiler') }}</h4>
                    <label
                        style="font-weight: 600; margin-bottom: 8px; display: block; color: var(--text-muted); font-size: 0.85rem;">{{ __('Yetkilerini Düzenlemek İstediğiniz Doküman Tipini Seçin') }}</label>
                    <select id="docTypeMatrixSelect" class="form-control"
                        style="width: 100%; max-width: 400px; padding: 10px; border-radius: 8px; border: 2px solid var(--border-color); font-weight: 600; cursor: pointer; color: var(--primary-color);">
                        <option value="">{{ __('-- Lütfen Bir Doküman Tipi Seçin --') }}</option>
                        @foreach ($documentTypes as $type)
                            <option value="matrix-{{ $type->slug }}">📂 {{ $type->name }} {{ __('Kategorisi') }}
                            </option>
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
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // --- 1. DİNAMİK FORM ALANLARI (CUSTOM FIELDS) MOTORU ---
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

                // Blade translates strings securely before JS engine runs
                row.innerHTML = `
            <div>
                <input type="text" name="custom_fields[${fieldIndex}][label]" class="form-control form-control-sm" value="${labelValue}" placeholder="{{ __('Label (Ekranda Görünür)') }}" required style="width: 100%; border-radius: 6px;">
            </div>
            <div>
                <input type="text" name="custom_fields[${fieldIndex}][name]" class="form-control form-control-sm key-input" value="${nameValue}" placeholder="{{ __('Veritabanı Anahtarı') }}" required data-auto="${autoSlug}" style="width: 100%; border-radius: 6px; font-family: monospace;">
            </div>
            <div>
                <select name="custom_fields[${fieldIndex}][type]" class="form-control form-control-sm" style="width: 100%; border-radius: 6px;">
                    <option value="text" ${typeValue === 'text' ? 'selected' : ''}>{{ __('Kısa Metin') }}</option>
                    <option value="number" ${typeValue === 'number' ? 'selected' : ''}>{{ __('Sayısal') }}</option>
                    <option value="date" ${typeValue === 'date' ? 'selected' : ''}>{{ __('Tarih') }}</option>
                    <option value="textarea" ${typeValue === 'textarea' ? 'selected' : ''}>{{ __('Uzun Metin') }}</option>
                </select>
            </div>
            <div style="text-align: center;" title="{{ __('Zorunlu Alan') }}">
                <input type="checkbox" name="custom_fields[${fieldIndex}][required]" value="1" ${isRequired} style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
            </div>
            <div style="text-align: right;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn d-flex align-items-center justify-content-center" style="padding: 6px; border-radius: 6px;">
                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        `;

                wrapper.appendChild(row);
                lucide.createIcons();
                fieldIndex++;

                const labelInput = row.querySelector('input[name$="[label]"]');
                const nameInput = row.querySelector('.key-input');

                labelInput.addEventListener('keyup', function() {
                    if (!nameInput.value || nameInput.getAttribute('data-auto') === 'true') {
                        nameInput.setAttribute('data-auto', 'true');
                        nameInput.value = this.value.toLowerCase()
                            .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
                            .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
                            .replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                    }
                });
                nameInput.addEventListener('input', function() {
                    this.setAttribute('data-auto', 'false');
                });
                row.querySelector('.remove-field-btn').addEventListener('click', function() {
                    row.remove();
                });
            }

            if (addBtn && wrapper) {
                addBtn.addEventListener('click', () => addFieldRow());
            }

            window.editDocType = function(id, name, customFieldsJson) {
                document.getElementById('dtName').value = name || '';

                const form = document.getElementById('docTypeForm');
                form.action = `/settings/document-types/${id}`;
                document.getElementById('methodSpoofer').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';

                document.getElementById('formTitle').innerHTML = `✏️ ${name} {{ __('Düzenleniyor') }}`;
                const submitBtn = document.getElementById('submitDocTypeBtn');
                submitBtn.innerHTML =
                    '<i data-lucide="refresh-cw" style="width: 18px;"></i> {{ __('Güncelle') }}';
                submitBtn.classList.replace('btn-primary', 'btn-warning');
                document.getElementById('cancelEditBtn').style.display = 'inline-block';
                lucide.createIcons();

                wrapper.innerHTML = '';
                fieldIndex = 0;
                if (customFieldsJson && Array.isArray(customFieldsJson)) {
                    customFieldsJson.forEach(field => addFieldRow(field));
                }
            };

            const cancelBtn = document.getElementById('cancelEditBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    document.getElementById('dtName').value = '';
                    wrapper.innerHTML = '';
                    fieldIndex = 0;

                    const form = document.getElementById('docTypeForm');
                    form.action = "{{ route('settings.document-types.store') }}";
                    document.getElementById('methodSpoofer').innerHTML = '';

                    document.getElementById('formTitle').innerHTML =
                        '✨ {{ __('Yeni Doküman Tipi Ekle') }}';
                    const submitBtn = document.getElementById('submitDocTypeBtn');
                    submitBtn.innerHTML =
                        '<i data-lucide="save" style="width: 18px;"></i> {{ __('Kaydet') }}';
                    submitBtn.classList.replace('btn-warning', 'btn-primary');
                    this.style.display = 'none';
                    lucide.createIcons();
                });
            }

            // --- 2. DOKÜMAN TİPİ MATRİSİ ---
            const docTypeSelect = document.getElementById('docTypeMatrixSelect');
            const matrixContents = document.querySelectorAll('.matrix-content');
            const docLoader = document.getElementById('docTypeMatrixLoader');

            if (docTypeSelect) {
                docTypeSelect.addEventListener('change', function() {
                    const targetId = this.value;
                    matrixContents.forEach(c => {
                        c.style.display = 'none';
                        c.classList.remove('active');
                    });
                    if (!targetId) {
                        docLoader.style.display = 'none';
                        return;
                    }
                    docLoader.style.display = 'block';
                    setTimeout(() => {
                        docLoader.style.display = 'none';
                        const targetContent = document.getElementById(targetId);
                        if (targetContent) {
                            targetContent.style.display = 'block';
                            targetContent.classList.add('active');
                        }
                    }, 400);
                });
            }

            // --- 3. KLASÖR MATRİSİ ---
            const folderSelect = document.getElementById('folderMatrixSelect');
            const matrixForm = document.getElementById('folderMatrixForm');
            const loader = document.getElementById('folderMatrixLoader');

            if (folderSelect) {
                folderSelect.addEventListener('change', function() {
                    const folderId = this.value;
                    if (!folderId) {
                        matrixForm.style.display = 'none';
                        loader.style.display = 'none';
                        return;
                    }

                    matrixForm.style.display = 'none';
                    loader.style.display = 'block';
                    matrixForm.action = `/settings/folders/${folderId}/permissions`;
                    document.querySelectorAll('.folder-matrix-cb').forEach(cb => cb.checked = false);

                    fetch(`/settings/folders/${folderId}/permissions`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Hata');
                            return response.json();
                        })
                        .then(data => {
                            loader.style.display = 'none';
                            matrixForm.style.display = 'block';
                            for (let roleId in data) {
                                const perms = data[roleId];
                                if (perms.can_view && document.getElementById(`can_view_${roleId}`))
                                    document.getElementById(`can_view_${roleId}`).checked = true;
                                if (perms.can_upload && document.getElementById(`can_upload_${roleId}`))
                                    document.getElementById(`can_upload_${roleId}`).checked = true;
                                if (perms.can_create_subfolder && document.getElementById(
                                        `can_subfolder_${roleId}`))
                                    document.getElementById(`can_subfolder_${roleId}`).checked = true;
                                if (perms.can_manage && document.getElementById(`can_manage_${roleId}`))
                                    document.getElementById(`can_manage_${roleId}`).checked = true;
                            }
                        })
                        .catch(error => {
                            loader.style.display = 'none';
                            console.error(error);
                        });
                });
            }
        });
    </script>
    <style>
        /* Eklenen Düzen (Layout) Stilleri */
        .admin-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
        }

        .admin-card-full {
            grid-column: span 12;
        }

        .admin-card-half {
            grid-column: span 12;
            /* Mobilde tam genişlik */
        }

        @media (min-width: 992px) {
            .admin-card-half {
                grid-column: span 6;
                /* PC'de yan yana */
            }
        }

        /* 1. Kart İç Yapısı (PC'de yan yana, mobilde alt alta) */
        .document-types-layout {
            display: flex;
            flex-direction: column;
            gap: 24px;
            padding: 24px;
        }

        @media (min-width: 992px) {
            .document-types-layout {
                flex-direction: row;
            }

            .document-types-table-wrapper {
                flex: 1 1 45%;
                border-right: 1px solid var(--border-color);
                padding-right: 24px;
            }

            .document-types-form-wrapper {
                flex: 1 1 55%;
            }
        }

        /* Ortak Tablo Hover ve Buton Tasarımları */
        .hover-row:hover {
            background-color: #f8fafc;
            transition: background-color 0.2s ease;
        }

        .action-btn {
            padding: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .action-btn i {
            width: 16px;
            height: 16px;
        }

        /* Scrollbar Tasarımı (Custom Scrollbar) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }
    </style>
@endpush
