@extends('layouts.app')

@section('content')
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="file-type"></i> {{ __('Doküman Tipleri ve Formlar') }}
                </h1>
                <p class="text-muted mt-1">
                    {{ __('Sistemdeki tüm belge tiplerini, izolasyon kurallarını ve akıllı form şablonlarını yönetin.') }}
                </p>
            </div>
            
            <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openAddModal()">
                <i data-lucide="plus" style="width: 18px;"></i> {{ __('Yeni Doküman Tipi Ekle') }}
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius: 8px; padding: 15px; font-weight: 500;">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- DOKÜMAN TİPLERİ TABLOSU VE ARAMA ÇUBUĞU --}}
    <div class="card glass-card" style="border-top: 4px solid var(--accent-color); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 15px 20px;">
            <div class="search-wrapper position-relative" style="width: 100%; max-width: 350px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; color: #94a3b8;"></i>
                <input type="text" id="docTypeSearch" class="form-control" placeholder="{{ __('Doküman tipi ara...') }}" 
                       style="padding-left: 40px; border-radius: 8px; border: 1px solid #e2e8f0; width: 100%;">
            </div>
            <div class="text-muted" style="font-size: 0.85rem; font-weight: 500;">
                <span id="rowCount">{{ count($documentTypes) }}</span> {{ __('kayıt listeleniyor') }}
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table modern-table mb-0" style="font-size: 0.95rem;">
                    <thead style="background: #fff; border-bottom: 2px solid var(--border-color);">
                        <tr>
                            <th style="padding: 15px 20px; font-weight: 600;">{{ __('Doküman Tipi Adı') }}</th>
                            <th style="padding: 15px 20px; font-weight: 600;">{{ __('Tür') }}</th>
                            <th style="padding: 15px 20px; font-weight: 600;">{{ __('Erişim & İzolasyon') }}</th>
                            <th class="text-right" style="padding: 15px 20px; width: 120px; font-weight: 600;">{{ __('İşlem') }}</th>
                        </tr>
                    </thead>
                    <tbody id="docTypeTableBody">
                        @forelse ($documentTypes as $type)
                            <tr class="hover-row" style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color); vertical-align: middle;" class="searchable-text">
                                    {{ $type->name }}
                                </td>
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    @if ($type->is_form_based)
                                        <span class="badge" style="background: #ede9fe; color: #7c3aed; padding: 6px 10px; border-radius: 6px;">
                                            <i data-lucide="zap" style="width: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom; fill: #7c3aed;"></i>
                                            {{ __('Akıllı Form') }}
                                        </span>
                                    @else
                                        <span class="badge" style="background: #f1f5f9; color: #64748b; padding: 6px 10px; border-radius: 6px;">
                                            <i data-lucide="file-up" style="width: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;"></i>
                                            {{ __('Fiziksel / PDF Yükleme') }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    @if ($type->department_id)
                                        <span class="badge searchable-text" style="background: #fee2e2; color: #b91c1c; padding: 6px 10px; border-radius: 6px;">
                                            <i data-lucide="shield" style="width: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;"></i>
                                            {{ $type->department->name }} {{ __('Özel') }}
                                        </span>
                                    @else
                                        <span class="badge searchable-text" style="background: #dcfce7; color: #15803d; padding: 6px 10px; border-radius: 6px;">
                                            <i data-lucide="globe" style="width: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;"></i>
                                            {{ __('Global (Herkese Açık)') }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick='openEditModal({{ $type->id }}, "{{ addslashes($type->name) }}", {{ $type->department_id ?? "null" }}, @json($type->custom_fields ?? []), {{ $type->requires_expiration_date ? "true" : "false" }}, {{ $type->is_form_based ? "true" : "false" }})' 
                                            title="{{ __('Düzenle') }}">
                                            <i data-lucide="edit-2" style="width: 16px;"></i>
                                        </button>
                                        <form action="{{ route('admin.document-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('{{ __('Silmek istediğinize emin misiniz? Bu işlem geri alınamaz!') }}')" style="margin: 0;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Sil') }}">
                                                <i data-lucide="trash-2" style="width: 16px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="4" class="text-center text-muted" style="padding: 40px;">
                                    <i data-lucide="inbox" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                                    <p>{{ __('Henüz hiç doküman tipi eklenmemiş.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="noResultRow" class="text-center text-muted" style="padding: 40px; display: none;">
                    <i data-lucide="search-x" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                    <p>{{ __('Aradığınız kritere uygun doküman tipi bulunamadı.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- DOKÜMAN TİPİ EKLE/DÜZENLE MODAL --}}
    <div id="docTypeModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1050; justify-content: center; align-items: flex-start; padding-top: 5vh; backdrop-filter: blur(4px); overflow-y: auto;">
        <div class="modal-content" style="background: #fff; border-radius: 12px; width: 100%; max-width: 650px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: modalSlideUp 0.3s ease; margin-bottom: 5vh;">
            
            <div style="padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 12px 12px 0 0;">
                <h3 id="modalTitle" style="margin:0; font-size: 1.25rem; font-weight: 600; color: var(--text-color);">
                    {{ __('Yeni Doküman Tipi Ekle') }}
                </h3>
                <button type="button" onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; color: #64748b; cursor:pointer; padding: 0;">&times;</button>
            </div>

            <form id="docTypeForm" method="POST" action="{{ route('admin.document-types.store') }}" style="padding: 25px;">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display:block; color: #475569;">
                            {{ __('Doküman Tipi Adı') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="dtName" class="form-control" required placeholder="{{ __('Örn: Sözleşme') }}" style="border-radius: 8px; padding: 10px;">
                    </div>
                    
                    <div class="col-md-6 form-group mb-3">
                        <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display:block; color: #475569;">
                            {{ __('Yetkili Departman (İzolasyon)') }}
                        </label>
                        <select name="department_id" id="dtDepartment" class="form-select" style="border-radius: 8px; padding: 10px;">
                            <option value="">{{ __('-- Global Şablon (Tüm Şirket) --') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->unit ? "[$dept->unit] " : '' }}{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group p-3 mb-3" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #7c3aed; margin-bottom: 0;">
                        <input type="checkbox" name="is_form_based" id="dtIsFormBased" value="1" style="width: 18px; height: 18px; accent-color: #8b5cf6;">
                        <i data-lucide="form-input" style="width: 18px;"></i>
                        {{ __('Akıllı Form Şablonu (Smart Form)') }}
                    </label>
                    <p class="text-muted mb-0 mt-1" style="font-size: 0.8rem; margin-left: 26px;">
                        Kullanıcılar dışarıdan PDF yükleyemez, aşağıdaki dinamik alanları doldurarak sistemin PDF üretmesini sağlarlar.
                    </p>
                </div>

                <div class="form-group mb-4">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; color: var(--text-color); cursor: pointer;">
                        <input type="checkbox" name="requires_expiration_date" id="dtRequiresExp" value="1" style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                        {{ __('Belge Yüklenirken Geçerlilik Tarihi Girmek Zorunlu Olsun') }}
                    </label>
                </div>

                <hr style="border-color: #e2e8f0; margin: 20px 0;">

                {{-- DİNAMİK ALANLAR (CUSTOM FIELDS) --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label style="font-size: 0.95rem; font-weight: 600; color: var(--secondary-color); margin: 0;">
                            <i data-lucide="layout-list" style="width: 18px; vertical-align: text-bottom;"></i> {{ __('Dinamik Form Alanları (Opsiyonel)') }}
                        </label>
                        <button type="button" id="addCustomFieldBtn" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                            <i data-lucide="plus" style="width: 14px;"></i> {{ __('Alan Ekle') }}
                        </button>
                    </div>
                    
                    <div id="customFieldsWrapper" style="display: flex; flex-direction: column; gap: 10px;">
                        {{-- JS İle Satırlar Buraya Gelecek --}}
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" onclick="closeModal()" class="btn btn-light" style="padding: 10px 20px; font-weight: 500;">{{ __('İptal') }}</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary d-flex align-items-center gap-2" style="padding: 10px 20px; font-weight: 500;">
                        <i data-lucide="save" style="width: 18px;"></i> {{ __('Kaydet') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();

        // --- ARAMA İŞLEMİ ---
        const searchInput = document.getElementById('docTypeSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLocaleLowerCase('tr-TR');
                const rows = document.querySelectorAll('#docTypeTableBody tr.hover-row');
                const noResultMsg = document.getElementById('noResultRow');
                let visibleCount = 0;

                rows.forEach(row => {
                    const textElements = row.querySelectorAll('.searchable-text');
                    let rowText = '';
                    textElements.forEach(el => rowText += el.textContent.toLocaleLowerCase('tr-TR') + ' ');

                    if (rowText.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                document.getElementById('rowCount').innerText = visibleCount;
                if (visibleCount === 0 && rows.length > 0) {
                    noResultMsg.style.display = 'block';
                } else {
                    noResultMsg.style.display = 'none';
                }
            });
        }

        // --- DİNAMİK FORM ALANLARI MANTIĞI ---
        const wrapper = document.getElementById('customFieldsWrapper');
        const addBtn = document.getElementById('addCustomFieldBtn');
        let fieldIndex = 0;

        window.addFieldRow = function(data = null) {
            const row = document.createElement('div');
            row.className = 'custom-field-row';
            row.style.cssText = 'display: grid; grid-template-columns: 2fr 2fr 1.5fr auto auto; gap: 10px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;';

            const labelValue = data ? data.label : '';
            const nameValue = data ? data.name : '';
            const typeValue = data ? data.type : 'text';
            const isRequired = (data && data.required) ? 'checked' : '';
            const autoSlug = data ? 'false' : 'true'; // Edit modundaysa auto-slug yapma

            row.innerHTML = `
                <div>
                    <input type="text" name="custom_fields[${fieldIndex}][label]" class="form-control form-control-sm label-input" value="${labelValue}" placeholder="{{ __('Görünen Ad (Örn: Tutar)') }}" required style="border-radius: 6px;">
                </div>
                <div>
                    <input type="text" name="custom_fields[${fieldIndex}][name]" class="form-control form-control-sm key-input" value="${nameValue}" placeholder="{{ __('Sistem Anahtarı') }}" required data-auto="${autoSlug}" style="border-radius: 6px; font-family: monospace; background: #f1f5f9;">
                </div>
                <div>
                    <select name="custom_fields[${fieldIndex}][type]" class="form-select form-select-sm" style="border-radius: 6px;">
                        <option value="text" ${typeValue === 'text' ? 'selected' : ''}>{{ __('Kısa Metin') }}</option>
                        <option value="number" ${typeValue === 'number' ? 'selected' : ''}>{{ __('Sayısal') }}</option>
                        <option value="date" ${typeValue === 'date' ? 'selected' : ''}>{{ __('Tarih') }}</option>
                        <option value="textarea" ${typeValue === 'textarea' ? 'selected' : ''}>{{ __('Uzun Metin') }}</option>
                    </select>
                </div>
                <div class="text-center" title="Zorunlu Alan">
                    <input type="checkbox" name="custom_fields[${fieldIndex}][required]" value="1" ${isRequired} style="width: 18px; height: 18px; accent-color: var(--primary-color); cursor: pointer;">
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn"><i data-lucide="trash-2" style="width: 14px;"></i></button>
                </div>
            `;

            wrapper.appendChild(row);
            lucide.createIcons();

            const currentLabel = row.querySelector('.label-input');
            const currentKey = row.querySelector('.key-input');

            // Türkçe Karakterleri temizleme ve slug üretme (Sadece ilk yazılırken)
            currentLabel.addEventListener('keyup', function() {
                if (currentKey.getAttribute('data-auto') === 'true') {
                    currentKey.value = this.value.toLowerCase()
                        .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
                        .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
                        .replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                }
            });

            // Kullanıcı key'i elinle değiştirirse, otomatik oluşturmayı kapat
            currentKey.addEventListener('input', () => currentKey.setAttribute('data-auto', 'false'));
            
            // Silme butonu olayı
            row.querySelector('.remove-field-btn').addEventListener('click', () => row.remove());
            
            fieldIndex++;
        };

        if (addBtn && wrapper) {
            addBtn.addEventListener('click', () => addFieldRow());
        }
    });

    // --- MODAL İŞLEMLERİ ---
    const modal = document.getElementById('docTypeModal');
    const form = document.getElementById('docTypeForm');
    const methodInput = document.getElementById('formMethod');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const wrapper = document.getElementById('customFieldsWrapper');

    window.openAddModal = function() {
        form.reset();
        wrapper.innerHTML = ''; // Eski form alanlarını temizle
        form.action = "{{ route('admin.document-types.store') }}";
        methodInput.value = "POST";
        modalTitle.innerText = "{{ __('Yeni Doküman Tipi Ekle') }}";
        submitBtn.innerHTML = '<i data-lucide="plus" style="width: 18px;"></i> {{ __('Ekle') }}';
        modal.style.display = 'flex';
        lucide.createIcons();
    };

    window.openEditModal = function(id, name, departmentId, customFieldsJson, requiresExp, isFormBased) {
        form.action = `/admin/document-types/${id}`;
        methodInput.value = "PUT";
        
        document.getElementById('dtName').value = name;
        document.getElementById('dtDepartment').value = departmentId || '';
        document.getElementById('dtRequiresExp').checked = requiresExp;
        document.getElementById('dtIsFormBased').checked = isFormBased;
        
        // Dinamik alanları doldur
        wrapper.innerHTML = '';
        if (customFieldsJson && Array.isArray(customFieldsJson)) {
            customFieldsJson.forEach(field => window.addFieldRow(field));
        }

        modalTitle.innerText = "{{ __('Doküman Tipi Düzenle') }}";
        submitBtn.innerHTML = '<i data-lucide="refresh-cw" style="width: 18px;"></i> {{ __('Güncelle') }}';
        submitBtn.classList.replace('btn-primary', 'btn-warning');
        
        modal.style.display = 'flex';
        lucide.createIcons();
    };

    window.closeModal = function() {
        modal.style.display = 'none';
        submitBtn.classList.replace('btn-warning', 'btn-primary');
    };

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
</script>

<style>
    .hover-row:hover {
        background-color: #f8fafc !important;
        transition: 0.2s;
    }
    @keyframes modalSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush