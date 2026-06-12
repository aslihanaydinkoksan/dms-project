@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20" style="flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="page-title"
                style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                <div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 12px; display:flex;">
                    <i data-lucide="shield-alert" style="width: 28px; height: 28px;"></i>
                </div>
                {{ __('Zorunlu Çekirdek Ekipler (Gruplar)') }}
            </h1>
            <p class="text-muted" style="font-size: 0.95rem; margin-top: 5px;">
                {{ __('Süreç başlatılırken atlanması (bypass edilmesi) yasak olan zorunlu personel gruplarını buradan yönetebilirsiniz.') }}
            </p>
        </div>

        <div class="header-actions">
            <button type="button" onclick="openGroupModal()" class="btn btn-primary"
                style="display: flex; align-items: center; gap: 8px; font-weight: 600; padding: 10px 20px;">
                <i data-lucide="plus-circle" style="width: 18px;"></i> {{ __('Yeni Grup Oluştur') }}
            </button>
        </div>
    </div>

    @include('partials.alerts')

    {{-- ANA LİSTE TABLOSU --}}
    <div class="card glass-card"
        style="border-radius: 12px; border-top: 4px solid var(--accent-color); overflow: hidden; box-shadow: var(--card-shadow);">
        <div class="table-responsive">
            <table class="table modern-table" style="width: 100%; margin: 0;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 15px;">{{ __('Grup Adı') }}</th>
                        <th style="padding: 15px;">{{ __('Açıklama') }}</th>
                        <th style="padding: 15px; text-align: center;">{{ __('Durum') }}</th>
                        <th style="padding: 15px; text-align: center;">{{ __('Üye Sayısı') }}</th>
                        <th style="padding: 15px; text-align: right;">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;">
                            <td style="padding: 15px; vertical-align: middle;">
                                <div style="font-weight: 700; color: var(--text-color);">🛡️ {{ $group->name }}</div>
                            </td>
                            <td
                                style="padding: 15px; vertical-align: middle; color: var(--text-muted); font-size: 0.85rem;">
                                {{ $group->description ?? '-' }}
                            </td>
                            <td style="padding: 15px; vertical-align: middle; text-align: center;">
                                @if ($group->is_active)
                                    <span class="badge"
                                        style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 6px; border: 1px solid #bbf7d0;">Aktif</span>
                                @else
                                    <span class="badge"
                                        style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 6px; border: 1px solid #fecaca;">Pasif</span>
                                @endif
                            </td>
                            <td style="padding: 15px; vertical-align: middle; text-align: center;">
                                <span
                                    style="font-size: 1.1rem; font-weight: 700; color: var(--primary-color);">{{ $group->members_count }}</span>
                            </td>
                            <td style="padding: 15px; vertical-align: middle; text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 6px;">

                                    {{-- Üyeleri Yönet Butonu (JSON Data Yollar) --}}
                                    @php
                                        $membersJson = json_encode(
                                            $group->members->map(function ($m) {
                                                return [
                                                    'id' => $m->id,
                                                    'name' => $m->name,
                                                    'department' => $m->department->name ?? 'Birim Yok',
                                                    'role' => $m->pivot->role,
                                                ];
                                            }),
                                        );
                                    @endphp
                                    <button type="button"
                                        onclick="openMembersModal({{ $group->id }}, '{{ htmlspecialchars($group->name, ENT_QUOTES) }}', {{ $membersJson }})"
                                        class="btn btn-sm btn-outline-primary" title="Üyeleri (Kurmayları) Yönet">
                                        <i data-lucide="users" style="width: 16px;"></i> Üyeler
                                    </button>

                                    {{-- Düzenle Butonu --}}
                                    <button type="button"
                                        onclick="openGroupModal({ id: {{ $group->id }}, name: '{{ htmlspecialchars($group->name, ENT_QUOTES) }}', desc: '{{ htmlspecialchars($group->description ?? '', ENT_QUOTES) }}', is_active: {{ $group->is_active ? 'true' : 'false' }} })"
                                        class="btn btn-sm btn-outline-secondary" title="Grubu Düzenle">
                                        <i data-lucide="edit" style="width: 16px;"></i>
                                    </button>

                                    {{-- Sil Butonu --}}
                                    <form action="{{ route('settings.user-groups.destroy', $group->id) }}" method="POST"
                                        onsubmit="return confirm('Bu grubu silmek istediğinize emin misiniz?');"
                                        style="margin: 0;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Grubu Sil">
                                            <i data-lucide="trash-2" style="width: 16px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 40px;">
                                <i data-lucide="shield-off"
                                    style="width: 48px; height: 48px; opacity: 0.3; margin-bottom: 10px; display: block; margin: 0 auto;"></i>
                                Henüz zorunlu bir ekip/grup oluşturulmamış.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- MODAL 1: GRUP EKLE / DÜZENLE --}}
    {{-- ========================================== --}}
    <div id="groupModal" class="modal-overlay custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="groupModalTitle"><i data-lucide="shield" style="color: var(--accent-color);"></i> Yeni Grup Oluştur
                </h2>
                <button type="button" class="close-modal" onclick="closeModal('groupModal')">&times;</button>
            </div>

            <form id="groupForm" method="POST" action="{{ route('settings.user-groups.store') }}">
                @csrf
                <div id="methodSpoofing"></div> {{-- Düzenleme için PUT buraya eklenecek --}}

                <div class="modal-body">
                    <div class="form-group mb-15">
                        <label class="form-label">Grup Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="groupName" class="form-control custom-input" required
                            placeholder="Örn: Hukuk Müşavirleri Kurulu">
                    </div>
                    <div class="form-group mb-15">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" id="groupDesc" class="form-control custom-input" rows="3"
                            placeholder="Grup amacı..."></textarea>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="is_active" id="groupActive" value="1" checked
                                style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                            Grup Aktif Olarak Kullanılsın
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="closeModal('groupModal')">İptal</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save"
                            style="width: 16px; margin-right: 5px;"></i> Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- MODAL 2: GRUP ÜYELERİNİ YÖNET (TOM SELECT) --}}
    {{-- ========================================== --}}
    <div id="membersModal" class="modal-overlay custom-modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header" style="background: #f8fafc;">
                <h2 style="font-size: 1.25rem;"><i data-lucide="users" style="color: var(--primary-color);"></i> <span
                        id="membersModalTitle">Üyeleri Yönet</span></h2>
                <button type="button" class="close-modal" onclick="closeModal('membersModal')">&times;</button>
            </div>

            <form id="membersForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">

                    {{-- Yeni Üye Ekleme Alanı --}}
                    <div
                        style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <label
                            style="font-size: 0.85rem; font-weight: 700; color: #1e40af; margin-bottom: 10px; display: block;">SİSTEMDEN
                            PERSONEL EKLE</label>
                        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; align-items: end;">
                            <div>
                                <select id="userSelector" placeholder="Personel Ara...">
                                    <option value="">Personel Ara...</option>
                                </select>
                            </div>
                            <div>
                                <select id="roleSelector" class="form-control"
                                    style="border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <option value="member">👤 Sadece Üye</option>
                                    <option value="manager">👑 Yönetici (Kapatıcı)</option>
                                </select>
                            </div>
                            <div>
                                <button type="button" onclick="addMemberToList()" class="btn btn-success"
                                    style="padding: 8px 15px; font-weight: 600;">Ekle</button>
                            </div>
                        </div>
                    </div>

                    {{-- Mevcut Üyeler Listesi (Dinamik HTML) --}}
                    <label
                        style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 10px; display: block; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">BU
                        GRUBUN MEVCUT ÜYELERİ (KURMAY LİSTESİ)</label>
                    <div id="membersListContainer"
                        style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding-right: 5px;"
                        class="custom-scrollbar">
                    </div>

                </div>

                <div class="modal-footer" style="background: #f8fafc;">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="closeModal('membersModal')">İptal</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="check-check"
                            style="width: 16px; margin-right: 5px;"></i> Kurmay Listesini Kaydet</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
        /* Modal ve Genel CSS Sınıfları */
        .custom-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 1050;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }

        .custom-modal .modal-content {
            background: #fff;
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s;
        }

        .custom-modal .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .custom-modal .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary-color);
        }

        .custom-modal .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .custom-modal .modal-body {
            padding: 25px;
        }

        .custom-modal .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .custom-input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        /* Tom Select Özelleştirme */
        .ts-control {
            border-radius: 6px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 8px 12px !important;
        }

        /* Üye Satırı Animasyonu */
        .member-row {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>

    <script>
        let tomSelectInstance = null;
        let memberIndexCounter = 0; // Her satıra unique isim vermek için

        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // Modal Dışı Tıklama Kapatması
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', e => {
                    if (e.target === modal) closeModal(modal.id);
                });
            });
        });

        // ==========================================
        // MODAL 1: GRUP EKLE / DÜZENLE YÖNETİMİ
        // ==========================================
        function openGroupModal(group = null) {
            const modal = document.getElementById('groupModal');
            const form = document.getElementById('groupForm');
            const title = document.getElementById('groupModalTitle');
            const spoofing = document.getElementById('methodSpoofing');

            if (group) {
                // Düzenleme Modu
                title.innerHTML = `<i data-lucide="edit" style="color: var(--primary-color);"></i> Grubu Düzenle`;
                form.action = `{{ url('/settings/user-groups') }}/${group.id}`;
                spoofing.innerHTML = '@method('PUT')';

                document.getElementById('groupName').value = group.name;
                document.getElementById('groupDesc').value = group.desc;
                document.getElementById('groupActive').checked = group.is_active;
            } else {
                // Yeni Ekleme Modu
                title.innerHTML = `<i data-lucide="shield" style="color: var(--accent-color);"></i> Yeni Grup Oluştur`;
                form.action = `{{ route('settings.user-groups.store') }}`;
                spoofing.innerHTML = '';

                form.reset();
                document.getElementById('groupActive').checked = true;
            }

            modal.style.display = 'flex';
            lucide.createIcons();
        }

        @php
            // Blade motorunu yormamak için PHP işlemini burada yapıyoruz
            $mappedUsers = $users->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'department' => $u->department->name ?? 'Birim Yok',
                ];
            });
        @endphp

        // Laravel'den gelen tertemiz diziyi JS objesine çeviriyoruz
        const usersData = @json($mappedUsers);

        // ==========================================
        // MODAL 2: GRUP ÜYELERİ YÖNETİMİ (TOM SELECT)
        // ==========================================
        function openMembersModal(groupId, groupName, members) {
            const modal = document.getElementById('membersModal');
            document.getElementById('membersModalTitle').innerText = groupName + ' Üyeleri';

            // Form Action'ı Güncelle
            document.getElementById('membersForm').action = `{{ url('/settings/user-groups') }}/${groupId}/sync`;

            // Listeyi Temizle
            const listContainer = document.getElementById('membersListContainer');
            listContainer.innerHTML = '';
            memberIndexCounter = 0;

            // Mevcut Üyeleri Ekrana Çiz
            if (members && members.length > 0) {
                members.forEach(member => {
                    renderMemberRow(member.id, member.name, member.department, member.role);
                });
            } else {
                listContainer.innerHTML =
                    '<div id="emptyMemberWarning" style="text-align:center; padding:15px; color:var(--text-muted); font-style:italic;">Bu gruba atanmış kimse yok.</div>';
            }

            // TomSelect'i Doğrudan JSON Verisiyle Başlat (Hatayı Çözen Kısım)
            if (!tomSelectInstance) {
                tomSelectInstance = new TomSelect('#userSelector', {
                    options: usersData, // HTML yerine doğrudan saf JSON datası!
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'department'],
                    render: {
                        option: function(item, escape) {
                            return `<div><span style="font-weight:600;">${escape(item.name)}</span> <span style="font-size:0.75rem; color:#64748b;">(${escape(item.department)})</span></div>`;
                        },
                        item: function(item, escape) {
                            return `<div>${escape(item.name)}</div>`;
                        }
                    }
                });
            }

            // Önceki seçimi sıfırla
            tomSelectInstance.clear();
            document.getElementById('roleSelector').value = 'member';

            modal.style.display = 'flex';
        }

        function addMemberToList() {
            if (!tomSelectInstance) return;

            const userId = tomSelectInstance.getValue();
            if (!userId) {
                alert('Lütfen eklenecek personeli seçin.');
                return;
            }

            // Çift Kayıt Kalkanı
            if (document.querySelector(`input[name$="[user_id]"][value="${userId}"]`)) {
                alert('Bu personel zaten gruba eklenmiş durumda!');
                return;
            }

            const optionObj = tomSelectInstance.options[userId];
            const userName = optionObj.name;
            const userDept = optionObj.department; // Artık dataset yerine doğrudan obje key'i!
            const role = document.getElementById('roleSelector').value;

            // "Kimse yok" uyarısını sil
            const warning = document.getElementById('emptyMemberWarning');
            if (warning) warning.remove();

            renderMemberRow(userId, userName, userDept, role);
            tomSelectInstance.clear();
        }

        function renderMemberRow(userId, userName, userDept, role) {
            const listContainer = document.getElementById('membersListContainer');
            const isManager = role === 'manager';

            const rowHtml = `
            <div class="member-row" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; background: ${isManager ? '#fffbeb' : '#f8fafc'}; border: 1px solid ${isManager ? '#fde68a' : 'var(--border-color)'}; border-radius: 8px;">
                
                <input type="hidden" name="members[${memberIndexCounter}][user_id]" value="${userId}">
                <input type="hidden" name="members[${memberIndexCounter}][role]" value="${role}">

                <div style="display:flex; align-items:center; gap: 10px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:${isManager ? '#eab308' : '#3b82f6'}; color:#fff; display:flex; justify-content:center; align-items:center; font-weight:bold; font-size:0.8rem;">
                        ${userName.substring(0,2).toUpperCase()}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--text-color);">${userName}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${userDept}</div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="badge" style="background: ${isManager ? '#fef3c7' : '#e2e8f0'}; color: ${isManager ? '#b45309' : '#475569'}; border: 1px solid ${isManager ? '#fcd34d' : '#cbd5e1'}; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">
                        ${isManager ? '👑 YÖNETİCİ' : 'ÜYE'}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.member-row').remove();" style="padding: 4px 8px; border:none; background:#fee2e2;">
                        <i data-lucide="x" style="width: 14px;"></i>
                    </button>
                </div>
            </div>
        `;

            listContainer.insertAdjacentHTML('beforeend', rowHtml);
            lucide.createIcons();
            memberIndexCounter++;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
@endpush
