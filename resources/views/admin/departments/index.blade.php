@extends('layouts.app')

@section('content')
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="building-2"></i> {{ __('Tesis ve Departman Yönetimi') }}
                </h1>
                <p class="text-muted mt-1">
                    {{ __('Şirketinizin fiziksel tesislerini ve bu tesislere bağlı departmanları buradan yönetebilirsiniz.') }}
                </p>
            </div>
            
            <div class="d-flex gap-2">
                <form action="{{ route('settings.departments.sync') }}" method="POST"
                    onsubmit="const btn = this.querySelector('button'); btn.disabled = true; btn.innerHTML = '<i data-lucide=\'refresh-cw\' class=\'spin\' style=\'width: 16px;\'></i> {{ __('Bekleyin...') }}';">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary d-flex align-items-center gap-2">
                        <i data-lucide="refresh-cw" style="width: 16px;"></i>
                        {{ __('MYS\'den Senkronize Et') }}
                    </button>
                </form>

                <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openAddModal()">
                    <i data-lucide="plus" style="width: 18px;"></i> {{ __('Yeni Departman Ekle') }}
                </button>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius: 8px; padding: 15px; font-weight: 500;">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2" style="border-radius: 8px; padding: 15px; font-weight: 500;">
            <i data-lucide="alert-triangle" style="width: 20px;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- DEPARTMAN TABLOSU VE ARAMA ÇUBUĞU --}}
    <div class="card glass-card" style="border-top: 4px solid var(--primary-color); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        
        {{-- YENİ EKLENEN ARAMA (SEARCH) KISMI --}}
        <div class="card-header d-flex justify-content-between align-items-center" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 15px 20px;">
            <div class="search-wrapper position-relative" style="width: 100%; max-width: 350px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; color: #94a3b8;"></i>
                <input type="text" id="departmentSearch" class="form-control" placeholder="{{ __('Tesis veya departman adı ara...') }}" 
                       style="padding-left: 40px; border-radius: 8px; border: 1px solid #e2e8f0; width: 100%; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
            </div>
            <div class="text-muted" style="font-size: 0.85rem; font-weight: 500;">
                <span id="rowCount">{{ count($departments) }}</span> {{ __('kayıt listeleniyor') }}
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table modern-table mb-0" style="font-size: 0.95rem;">
                    <thead style="background: #fff; border-bottom: 2px solid var(--border-color);">
                        <tr>
                            <th style="padding: 15px 20px; font-weight: 600;">{{ __('Tesis (Ünite)') }}</th>
                            <th style="padding: 15px 20px; font-weight: 600;">{{ __('Departman Adı') }}</th>
                            <th class="text-center" style="padding: 15px 20px; font-weight: 600;">{{ __('Zorunlu Belge Onayı') }}</th>
                            <th class="text-right" style="padding: 15px 20px; width: 120px; font-weight: 600;">{{ __('İşlem') }}</th>
                        </tr>
                    </thead>
                    <tbody id="departmentTableBody">
                        @forelse ($departments as $dept)
                            <tr class="hover-row" style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    <span class="badge" style="font-size: 0.85rem; background: #e0e7ff; color: #3730a3; padding: 6px 10px; border-radius: 6px;">
                                        <i data-lucide="map-pin" style="width: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;"></i>
                                        <span class="searchable-text">{{ $dept->unit ?? __('Merkez') }}</span>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px; font-weight: 500; color: var(--text-color); vertical-align: middle;" class="searchable-text">
                                    {{ $dept->name }}
                                </td>
                                <td class="text-center" style="padding: 15px 20px; vertical-align: middle;">
                                    <label class="switch-custom" title="{{ __('Departmana yüklenen belgeler onaya düşsün mü?') }}">
                                        <input type="checkbox" onchange="toggleApproval({{ $dept->id }}, this.checked)" {{ $dept->requires_approval_on_upload ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="openEditModal({{ $dept->id }}, '{{ addslashes($dept->unit) }}', '{{ addslashes($dept->name) }}')" title="{{ __('Düzenle') }}">
                                            <i data-lucide="edit-2" style="width: 16px;"></i>
                                        </button>
                                        <form action="{{ route('admin.departments.destroy', $dept->id) }}" method="POST" onsubmit="return confirm('{{ __('Bu departmanı silmek istediğinize emin misiniz?') }}')" style="margin: 0;">
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
                                    <p>{{ __('Henüz hiç departman eklenmemiş.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                {{-- Arama Sonucu Bulunamadı Mesajı --}}
                <div id="noResultRow" class="text-center text-muted" style="padding: 40px; display: none;">
                    <i data-lucide="search-x" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                    <p>{{ __('Aradığınız kritere uygun departman bulunamadı.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- DEPARTMAN EKLE/DÜZENLE MODAL (Tek Modal ile SPA Hissiyatı) --}}
    <div id="deptModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1050; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
        <div class="modal-content" style="background: #fff; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: modalSlideUp 0.3s ease;">
            
            <div style="padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 12px 12px 0 0;">
                <h3 id="modalTitle" style="margin:0; font-size: 1.25rem; font-weight: 600; color: var(--text-color);">
                    {{ __('Yeni Departman Ekle') }}
                </h3>
                <button type="button" onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; color: #64748b; cursor:pointer; padding: 0;">&times;</button>
            </div>

            <form id="deptForm" method="POST" action="{{ route('admin.departments.store') }}" style="padding: 25px;">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="form-group mb-3">
                    <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display:block; color: #475569;">
                        {{ __('Tesis (Ünite) Adı') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="unit" id="deptUnit" class="form-control" list="unit-list" required placeholder="{{ __('Örn: Merkez, Preform, Levha') }}" style="border-radius: 8px; padding: 12px;">
                    <datalist id="unit-list">
                        <option value="{{ __('Merkez') }}">
                        <option value="Preform">
                        <option value="Levha">
                    </datalist>
                </div>

                <div class="form-group mb-4">
                    <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display:block; color: #475569;">
                        {{ __('Departman Adı') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="deptName" class="form-control" required placeholder="{{ __('Örn: İnsan Kaynakları') }}" style="border-radius: 8px; padding: 12px;">
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

        // YENİ EKLENEN: Arama (Filtreleme) Scripti
        const searchInput = document.getElementById('departmentSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLocaleLowerCase('tr-TR');
                const rows = document.querySelectorAll('#departmentTableBody tr.hover-row');
                const noResultMsg = document.getElementById('noResultRow');
                let visibleCount = 0;

                rows.forEach(row => {
                    // Sadece Tesis ve Departman adı sütunlarındaki texti alıyoruz
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

                // Sonuç sayısı güncelleme
                document.getElementById('rowCount').innerText = visibleCount;

                // Hiç sonuç yoksa uyarı gösterme
                if (visibleCount === 0 && rows.length > 0) {
                    noResultMsg.style.display = 'block';
                } else {
                    noResultMsg.style.display = 'none';
                }
            });
        }
    });

    // Modal İşlemleri
    const modal = document.getElementById('deptModal');
    const form = document.getElementById('deptForm');
    const methodInput = document.getElementById('formMethod');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    window.openAddModal = function() {
        form.reset();
        form.action = "{{ route('admin.departments.store') }}";
        methodInput.value = "POST";
        modalTitle.innerText = "{{ __('Yeni Departman Ekle') }}";
        submitBtn.innerHTML = '<i data-lucide="plus" style="width: 18px;"></i> {{ __('Ekle') }}';
        modal.style.display = 'flex';
        lucide.createIcons();
    };

    window.openEditModal = function(id, unit, name) {
        form.action = `/admin/departments/${id}`;
        methodInput.value = "PUT";
        document.getElementById('deptUnit').value = unit;
        document.getElementById('deptName').value = name;
        
        modalTitle.innerText = "{{ __('Departman Düzenle') }}";
        submitBtn.innerHTML = '<i data-lucide="refresh-cw" style="width: 18px;"></i> {{ __('Güncelle') }}';
        submitBtn.classList.replace('btn-primary', 'btn-warning');
        
        modal.style.display = 'flex';
        lucide.createIcons();
    };

    window.closeModal = function() {
        modal.style.display = 'none';
        submitBtn.classList.replace('btn-warning', 'btn-primary');
    };

    // Modal dışına tıklayınca kapatma
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    // AJAX Toggle Onay İşlemi
    window.toggleApproval = async function(id, isActive) {
        try {
            const response = await fetch(`/admin/departments/${id}/toggle-approval`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: isActive })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log(data.message);
            } else {
                alert("{{ __('Bir hata oluştu!') }}");
            }
        } catch (error) {
            console.error('Error:', error);
            alert("{{ __('Sunucu ile bağlantı kurulamadı.') }}");
        }
    };
</script>

<style>
    /* Modern Switch Tasarımı */
    .switch-custom {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch-custom input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
    }
    input:checked + .slider {
        background-color: #10b981;
    }
    input:checked + .slider:before {
        transform: translateX(20px);
    }
    .slider.round {
        border-radius: 34px;
    }
    .slider.round:before {
        border-radius: 50%;
    }

    .hover-row:hover {
        background-color: #f1f5f9 !important;
        transition: 0.2s;
    }

    @keyframes modalSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endpush