@extends('layouts.app')

@section('content')
    <div class="page-header mb-30">
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">
            <i data-lucide="scan-line" style="width: 28px; height: 28px; color: var(--accent-color);"></i>
            {{ __('Kullanıcı Yetki Röntgeni') }}
        </h1>
        <p class="text-muted">
            {{ __('Sistemdeki tüm kullanıcıların Rol, Departman, Vekalet ve Granular Klasör erişimlerini saniyeler içinde analiz edin.') }}
        </p>
    </div>

    <div class="explorer-layout" style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px; align-items: start;">

        {{-- SOL PANEL: KULLANICI LİSTESİ VE ARAMA --}}
        <div class="card glass-card"
            style="border-radius: 12px; box-shadow: var(--card-shadow); height: 75vh; display: flex; flex-direction: column;">
            <div class="card-header"
                style="padding: 20px; border-bottom: 1px solid var(--border-color); background: #f8fafc;">
                <input type="text" id="userSearchInput" class="form-control" placeholder="Kullanıcı Ara..."
                    style="border-radius: 8px; padding: 12px;">
                <select id="deptFilterInput" class="form-control mt-10"
                    style="border-radius: 8px; padding: 10px; margin-top: 10px;">
                    <option value="">{{ __('Tüm Departmanlar') }}</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="card-body custom-scrollbar" style="padding: 0; overflow-y: auto; flex-grow: 1;">
                <ul class="user-list" id="userList" style="list-style: none; padding: 0; margin: 0;">
                    @foreach ($users as $usr)
                        <li class="user-list-item" data-id="{{ $usr->id }}" data-name="{{ strtolower($usr->name) }}"
                            data-dept="{{ $usr->department ? $usr->department->name : '' }}"
                            style="padding: 15px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: 0.2s;">
                            <div style="font-weight: 600; color: var(--primary-color);">{{ $usr->name }}</div>
                            <div
                                style="font-size: 0.8rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                                <span>{{ $usr->department ? $usr->department->name : 'Dept. Yok' }}</span>
                                <span class="badge"
                                    style="background: #e2e8f0; color: #475569;">{{ $usr->roles->count() }} Rol</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- SAĞ PANEL: RÖNTGEN / ANALİZ SONUCU --}}
        <div class="card glass-card" style="border-radius: 12px; box-shadow: var(--card-shadow); min-height: 75vh;">

            {{-- Boş Durum (İlk Açılış) --}}
            <div id="emptyState"
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); padding: 50px;">
                <i data-lucide="microscope" style="width: 64px; height: 64px; opacity: 0.2; margin-bottom: 15px;"></i>
                <h3>Kullanıcı Seçin</h3>
                <p>Analiz etmek istediğiniz kullanıcıyı sol panelden seçin.</p>
            </div>

            {{-- Yükleniyor --}}
            <div id="loaderState"
                style="display: none; align-items: center; justify-content: center; height: 100%; color: var(--primary-color);">
                <i data-lucide="loader" class="spin" style="width: 40px; height: 40px;"></i>
            </div>

            {{-- Veri Gösterim Alanı --}}
            <div id="dataState" style="display: none; padding: 30px; animation: fadeIn 0.4s ease;">

                {{-- Başlık ve Temel Bilgiler --}}
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 25px;">
                    <div>
                        <h2 id="rName" style="margin: 0; color: var(--primary-color); font-size: 1.6rem;">İsim Soyisim
                        </h2>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">
                            <i data-lucide="mail" style="width: 14px; vertical-align: middle;"></i> <span
                                id="rEmail">email@example.com</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span id="rDept" class="badge"
                            style="background: #e0e7ff; color: #3730a3; padding: 8px 12px; font-size: 0.9rem;">Departman
                            Adı</span>
                        <div style="margin-top: 8px; font-size: 0.8rem; color: var(--text-muted);">
                            Hiyerarşi Seviyesi: <strong id="rHierarchy"
                                style="color: var(--secondary-color); font-size: 1.1rem;">0</strong>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    {{-- Rol ve Kalkanlar --}}
                    <div style="background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        <h4 style="margin: 0 0 15px 0; color: var(--secondary-color);"><i data-lucide="shield-check"
                                style="width:18px;"></i> Sahip Olduğu Roller</h4>
                        <div id="rRoles" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>

                        <h4 style="margin: 25px 0 10px 0; color: #b91c1c;"><i data-lucide="alert-triangle"
                                style="width:18px;"></i> Tehlikeli Kalkanlar (God-Mode)</h4>
                        <div id="rDangerous" style="font-size: 0.85rem;"></div>
                    </div>

                    {{-- Vekalet Durumu --}}
                    <div style="background: #fffbeb; padding: 20px; border-radius: 10px; border: 1px solid #fde68a;">
                        <h4 style="margin: 0 0 15px 0; color: #92400e;"><i data-lucide="users" style="width:18px;"></i>
                            Aktif Vekaletler (Devralınan Güç)</h4>
                        <p style="font-size: 0.8rem; color: #b45309; margin-top: -10px;">Bu kullanıcı şu an aşağıdaki
                            kişilerin departman ve rol yetkilerini kullanmaktadır.</p>
                        <div id="rDelegators"></div>
                    </div>
                </div>

                {{-- İstisnai Klasör Erişimleri --}}
                <div style="background: #f0fdf4; padding: 20px; border-radius: 10px; border: 1px solid #bbf7d0;">
                    <h4 style="margin: 0 0 10px 0; color: #166534;"><i data-lucide="folder-lock" style="width:18px;"></i>
                        İstisnai (VIP) Klasör Erişimleri</h4>
                    <p style="font-size: 0.85rem; color: #15803d;">Kendi departmanı DIŞINDA, özel yetki ile eklendiği
                        klasörler.</p>
                    <div class="table-responsive mt-15">
                        <table class="table modern-table" style="font-size: 0.85rem; background: #fff;">
                            <thead style="background: #dcfce7;">
                                <tr>
                                    <th>Klasör Adı</th>
                                    <th>Erişim Seviyesi</th>
                                </tr>
                            </thead>
                            <tbody id="rExternalFolders">
                                <!-- JS ile doldurulacak -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .user-list-item:hover,
        .user-list-item.active {
            background: #e0e7ff !important;
            border-left: 4px solid #4f46e5;
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

        .badge-red {
            background: #fee2e2;
            color: #b91c1c;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: bold;
            border: 1px solid #fca5a5;
            display: inline-block;
            margin-bottom: 5px;
        }

        .badge-role {
            background: #f1f5f9;
            color: #334155;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // 1. Sol Panel Filtreleme (JS ile hızlı arama)
            const searchInput = document.getElementById('userSearchInput');
            const deptInput = document.getElementById('deptFilterInput');
            const listItems = document.querySelectorAll('.user-list-item');

            function filterUsers() {
                const term = searchInput.value.toLowerCase();
                const dept = deptInput.value;

                listItems.forEach(item => {
                    const matchesName = item.getAttribute('data-name').includes(term);
                    const matchesDept = dept === '' || item.getAttribute('data-dept') === dept;

                    if (matchesName && matchesDept) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('input', filterUsers);
            deptInput.addEventListener('change', filterUsers);

            // 2. AJAX İstekleri ve Veri Doldurma
            const emptyState = document.getElementById('emptyState');
            const loaderState = document.getElementById('loaderState');
            const dataState = document.getElementById('dataState');

            listItems.forEach(item => {
                item.addEventListener('click', function() {
                    // UI Aktif Sınıfı Değişimi
                    listItems.forEach(li => li.classList.remove('active'));
                    this.classList.add('active');

                    const userId = this.getAttribute('data-id');

                    // Ekranları Değiştir
                    emptyState.style.display = 'none';
                    dataState.style.display = 'none';
                    loaderState.style.display = 'flex';

                    // Veriyi Çek (Hard-coded URL yerine Laravel URL Helper )
                    const targetUrl = `{{ url('/settings/users') }}/${userId}/permission-details`;

                    fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Temel Bilgiler
                            document.getElementById('rName').textContent = data.basic_info.name;
                            document.getElementById('rEmail').textContent = data.basic_info
                                .email;
                            document.getElementById('rDept').textContent = data.basic_info
                                .department;
                            document.getElementById('rHierarchy').textContent = data
                                .hierarchy_level;

                            // Roller
                            const rolesHtml = data.roles.length > 0 ?
                                data.roles.map(r => `<span class="badge-role">${r}</span>`)
                                .join('') :
                                '<span class="text-muted">Atanmış rol yok.</span>';
                            document.getElementById('rRoles').innerHTML = rolesHtml;

                            // Tehlikeli Kalkanlar
                            const dangerHtml = data.dangerous_permissions.length > 0 ?
                                data.dangerous_permissions.map(p =>
                                    `<div class="badge-red">⚠️ ${p}</div>`).join('') :
                                '<span style="color:#15803d;"><i data-lucide="check-circle" style="width:14px; vertical-align:middle;"></i> Güvenli. Mutlak güç yok.</span>';
                            document.getElementById('rDangerous').innerHTML = dangerHtml;

                            // Vekaletler
                            const delHtml = data.delegators.length > 0 ?
                                data.delegators.map(d =>
                                    `<div style="padding:8px; background:#fff; border-radius:6px; margin-bottom:5px; border:1px solid #fde047;"><strong>${d.name}</strong> (${d.department})<br><small class="text-muted">Roller: ${d.roles}</small></div>`
                                ).join('') :
                                '<span class="text-muted" style="font-size:0.85rem;">Şu an aktif devralınan bir yetki yok.</span>';
                            document.getElementById('rDelegators').innerHTML = delHtml;

                            // Harici VIP Klasörler
                            const extHtml = data.external_folders.length > 0 ?
                                data.external_folders.map(f => `<tr>
                        <td><strong>${f.prefix ? '['+f.prefix+'] ' : ''}${f.name}</strong></td>
                        <td><span class="badge badge-${f.access_level === 'manage' ? 'danger' : (f.access_level === 'upload' ? 'warning' : 'info')}">${f.access_level.toUpperCase()}</span></td>
                      </tr>`).join('') :
                                '<tr><td colspan="2" class="text-center text-muted" style="padding:20px;">Harici/İstisnai klasör erişimi bulunmuyor. Sistem izolasyonu tam.</td></tr>';
                            document.getElementById('rExternalFolders').innerHTML = extHtml;

                            lucide.createIcons();

                            // Ekranları Değiştir
                            loaderState.style.display = 'none';
                            dataState.style.display = 'block';
                        })
                        .catch(error => {
                            console.error("Hata:", error);
                            alert("Veriler çekilirken bir hata oluştu.");
                            loaderState.style.display = 'none';
                            emptyState.style.display = 'flex';
                        });
                });
            });
        });
    </script>
@endpush
