@extends('layouts.app')

@section('content')
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title" style="font-size: 1.8rem; color: var(--success-color); display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="users"></i> {{ __('Rol ve Hiyerarşi Yönetimi') }}
                </h1>
                <p class="text-muted mt-1">{{ __('Sistemdeki rolleri oluşturun, hiyerarşilerini belirleyin ve yetki matrislerini yönetin.') }}</p>
            </div>
            <button class="btn btn-success d-flex align-items-center gap-2" onclick="openAddModal()">
                <i data-lucide="plus" style="width: 18px;"></i> {{ __('Yeni Rol Ekle') }}
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius: 8px; padding: 15px; font-weight: 500;">
            <i data-lucide="check-circle" style="width: 20px;"></i> <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card glass-card" style="border-top: 4px solid var(--success-color); border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table modern-table mb-0">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 15px 20px;">{{ __('Rol Adı') }}</th>
                            <th class="text-center" style="padding: 15px 20px;">{{ __('Hiyerarşi Seviyesi') }}</th>
                            <th class="text-right" style="padding: 15px 20px; width: 250px;">{{ __('İşlem') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr class="hover-row" style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 15px 20px; font-weight: 600;">
                                    {{ $role->name }}
                                    @if (in_array($role->name, ['Super Admin', 'Admin']))
                                        <span class="badge" style="background: #fef08a; color: #854d0e; margin-left: 8px;">🔒 Sistem</span>
                                    @endif
                                </td>
                                <td class="text-center" style="padding: 15px 20px;">
                                    <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 0.9rem;">{{ $role->hierarchy_level ?? 0 }}</span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        {{-- YETKİ MATRİSİ BUTONU (YENİ!) --}}
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                                            <i data-lucide="shield-check" style="width: 14px;"></i> {{ __('Matris') }}
                                        </a>

                                        @if (!in_array($role->name, ['Super Admin', 'Admin']))
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', {{ $role->hierarchy_level ?? 0 }})">
                                                <i data-lucide="edit-2" style="width: 14px;"></i>
                                            </button>
                                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Emin misiniz?')" style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2" style="width: 14px;"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ROL EKLE/DÜZENLE MODAL --}}
    <div id="roleModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1050; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: #fff; border-radius: 12px; width: 100%; max-width: 400px; padding: 25px;">
            <h3 id="modalTitle" style="margin-top:0; font-size: 1.25rem;">{{ __('Yeni Rol Ekle') }}</h3>
            <form id="roleForm" method="POST" action="{{ route('admin.roles.store') }}">
                @csrf <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="form-group mb-3 mt-3">
                    <label style="font-weight: 600; font-size: 0.9rem;">{{ __('Rol Adı') }}</label>
                    <input type="text" name="name" id="roleName" class="form-control" required style="border-radius: 8px;">
                </div>
                <div class="form-group mb-4">
                    <label style="font-weight: 600; font-size: 0.9rem;">{{ __('Hiyerarşi Seviyesi (Örn: 10)') }}</label>
                    <input type="number" name="hierarchy_level" id="roleLevel" class="form-control" required min="0" style="border-radius: 8px;">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" onclick="closeModal()" class="btn btn-light">{{ __('İptal') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Kaydet') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
    const modal = document.getElementById('roleModal'), form = document.getElementById('roleForm'), methodInput = document.getElementById('formMethod'), modalTitle = document.getElementById('modalTitle');
    window.openAddModal = () => { form.reset(); form.action = "{{ route('admin.roles.store') }}"; methodInput.value = "POST"; modalTitle.innerText = "{{ __('Yeni Rol Ekle') }}"; modal.style.display = 'flex'; };
    window.openEditModal = (id, name, level) => { form.action = `/admin/roles/${id}`; methodInput.value = "PUT"; document.getElementById('roleName').value = name; document.getElementById('roleLevel').value = level; modalTitle.innerText = "{{ __('Rol Düzenle') }}"; modal.style.display = 'flex'; };
    window.closeModal = () => modal.style.display = 'none';
</script>
<style>.hover-row:hover{background-color: #f8fafc; transition: 0.2s;}</style>
@endpush