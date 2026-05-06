@extends('layouts.app')

@section('content')
    <div class="page-header"
        style="background: var(--surface-color); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
        <div style="background: #fef2f2; color: var(--danger-color); padding: 15px; border-radius: 12px;">
            <i data-lucide="shield-alert" style="width: 32px; height: 32px;"></i>
        </div>
        <div>
            <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.5rem; color: var(--danger-color);">
                {{ __('Global Kanıt ve Log Merkezi') }}</h1>
            <p class="text-muted" style="margin: 0;">
                {{ __('Bu alanda sistemdeki tüm doküman erişimleri ve bilgilendirme kanıtları değiştirilemez olarak tutulmaktadır.') }}
            </p>
        </div>
    </div>

    {{-- FİLTRELEME BARI --}}
    <div class="card glass-card"
        style="margin-bottom: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); padding: 20px;">
        <form action="{{ route('system.logs.index') }}" method="GET"
            style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block;">{{ __('Kullanıcı Adı') }}</label>
                <input type="text" name="user_name" value="{{ $userName }}" class="form-control"
                    placeholder="Örn: Aslıhan"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block;">{{ __('Belge Adı') }}</label>
                <input type="text" name="document_name" value="{{ $docName }}" class="form-control"
                    placeholder="Örn: Bütçe"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block;">{{ __('Başlangıç Tarihi') }}</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block;">{{ __('Bitiş Tarihi') }}</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
            </div>
            <div>
                <button type="submit" class="btn btn-primary"
                    style="padding: 10px 25px; height: 42px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="filter" style="width: 16px;"></i> Filtrele
                </button>
            </div>
            @if (request()->hasAny(['user_name', 'document_name', 'start_date', 'end_date']))
                <div>
                    <a href="{{ route('system.logs.index') }}" class="btn btn-outline-secondary"
                        style="padding: 10px 15px; height: 42px;">Temizle</a>
                </div>
            @endif
        </form>
    </div>

    {{-- TAB MENÜSÜ --}}
    <div class="tabs-wrapper"
        style="margin-bottom: 20px; display: flex; gap: 10px; border-bottom: 2px solid var(--border-color);">
        <button class="sys-tab-btn active" data-target="audit-logs"
            style="padding: 12px 25px; background: none; border: none; font-weight: 600; color: var(--primary-color); border-bottom: 3px solid var(--primary-color); cursor: pointer; font-size: 1.05rem;">
            <i data-lucide="fingerprint" style="width: 18px; vertical-align: middle; margin-right: 5px;"></i> Belge İşlem
            İzleri
        </button>
        <button class="sys-tab-btn" data-target="dispatch-logs"
            style="padding: 12px 25px; background: none; border: none; font-weight: 600; color: var(--text-muted); border-bottom: 3px solid transparent; cursor: pointer; font-size: 1.05rem;">
            <i data-lucide="send" style="width: 18px; vertical-align: middle; margin-right: 5px;"></i> Sistem Bildirim
            Geçmişi
        </button>
    </div>

    <div class="sys-tab-contents">
        {{-- TAB 1: AUDIT LOGS --}}
        <div id="audit-logs" class="sys-tab-pane" style="display: block; opacity: 1;">
            <div class="card glass-card"
                style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: #fff; overflow: hidden;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead
                            style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                            <tr>
                                <th style="padding: 15px;">Tarih</th>
                                <th style="padding: 15px;">Kullanıcı</th>
                                <th style="padding: 15px;">İşlem Tipi</th>
                                <th style="padding: 15px;">Belge Adı</th>
                                <th style="padding: 15px;">IP Adresi</th>
                                <th style="padding: 15px; text-align: right;">Kanıt Merkezi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 15px; color: var(--text-muted);">
                                        {{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                    <td style="padding: 15px; font-weight: 600; color: var(--text-color);">
                                        {{ $log->user?->name ?? 'Sistem' }}</td>
                                    <td style="padding: 15px;">
                                        <span class="badge badge-secondary"
                                            style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe;">
                                            {{ strtoupper($log->event) }}
                                        </span>
                                    </td>
                                    <td style="padding: 15px; font-weight: 500;">
                                        {{ $log->document_title ?? 'Silinmiş / Bilinmeyen Belge' }}</td>
                                    <td style="padding: 15px; font-family: monospace; color: var(--text-muted);">
                                        {{ $log->ip_address }}</td>
                                    <td style="padding: 15px; text-align: right;">
                                        <button class="btn btn-sm btn-outline-danger btn-proof"
                                            data-user="{{ $log->user?->name ?? 'Bilinmeyen Kullanıcı' }}"
                                            data-date="{{ $log->created_at->format('d.m.Y H:i:s') }}"
                                            data-ip="{{ $log->ip_address }}"
                                            data-doc="{{ $log->document_title ?? 'Silinmiş Belge' }}"
                                            data-event="{{ $log->event }}" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i data-lucide="file-search" style="width: 14px;"></i> Kanıt Oluştur
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        Kriterlere uygun kayıt bulunamadı.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="padding: 15px; background: #f8fafc; border-top: 1px solid var(--border-color);">
                    {{ $auditLogs->links() }}
                </div>
            </div>
        </div>

        {{-- TAB 2: DISPATCH LOGS --}}
        <div id="dispatch-logs" class="sys-tab-pane" style="display: none; opacity: 1;">
            <div class="card glass-card"
                style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: #fff; overflow: hidden;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead
                            style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                            <tr>
                                <th style="padding: 15px;">Gönderim Tarihi</th>
                                <th style="padding: 15px;">Alıcı (Hedef Kullanıcı)</th>
                                <th style="padding: 15px;">İletilen Mesaj / Belge</th>
                                <th style="padding: 15px;">Görülme (Okunma) Durumu</th>
                                <th style="padding: 15px; text-align: right;">İletim Makbuzu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dispatchLogs as $notify)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 15px; color: var(--text-muted);">
                                        {{ \Carbon\Carbon::parse($notify->created_at)->format('d.m.Y H:i:s') }}</td>
                                    <td style="padding: 15px; font-weight: 600; color: var(--text-color);">
                                        {{ $notify->receiver_name }}
                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">
                                            {{ $notify->receiver_email }}</div>
                                    </td>
                                    <td style="padding: 15px;">
                                        <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                            title="{{ $notify->data['message'] ?? 'Bilinmeyen İçerik' }}">
                                            {{ $notify->data['message'] ?? 'Bilinmeyen İçerik' }}
                                        </div>
                                    </td>
                                    <td style="padding: 15px;">
                                        @if ($notify->read_at)
                                            <span class="badge badge-success"
                                                style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 4px;">
                                                <i data-lucide="check-check" style="width: 14px;"></i>
                                                {{ \Carbon\Carbon::parse($notify->read_at)->format('d.m.Y H:i') }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning"
                                                style="background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; display: inline-flex; align-items: center; gap: 4px;">
                                                <i data-lucide="clock" style="width: 14px;"></i> Okunmadı
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 15px; text-align: right;">
                                        <button class="btn btn-sm btn-outline-primary btn-receipt"
                                            data-receiver="{{ $notify->receiver_name }}"
                                            data-date="{{ \Carbon\Carbon::parse($notify->created_at)->format('d.m.Y H:i:s') }}"
                                            data-doc="{{ $notify->data['title'] ?? ($notify->data['folder_name'] ?? 'İçerik') }}"
                                            data-status="{{ $notify->read_at ? 'Okundu (' . \Carbon\Carbon::parse($notify->read_at)->format('d.m.Y H:i:s') . ')' : 'İletildi (Henüz Okunmadı)' }}"
                                            style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i data-lucide="receipt" style="width: 14px;"></i> Makbuz
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        style="padding: 30px; text-align: center; color: var(--text-muted);">Kriterlere
                                        uygun kayıt bulunamadı.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="padding: 15px; background: #f8fafc; border-top: 1px solid var(--border-color);">
                    {{ $dispatchLogs->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- KANIT VE MAKBUZ MODALI (Ortak) --}}
    <div id="proofModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
        <div class="modal-content"
            style="background: #fff; padding: 0; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
            <div
                style="background: var(--surface-color); border-bottom: 1px solid var(--border-color); padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                <h2
                    style="font-size: 1.15rem; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="stamp" style="color: var(--danger-color);"></i> Dijital Sistem Makbuzu
                </h2>
                <button type="button" class="close-modal"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <div style="padding: 30px; line-height: 1.6;">
                <div id="proof-text"
                    style="font-size: 1rem; color: var(--text-color); background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid var(--danger-color); border: 1px solid var(--border-color);">
                    <!-- JS ile Doldurulacak -->
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='none' stroke='%23e2e8f0' stroke-width='2'/><path d='M30 50 L45 65 L70 35' fill='none' stroke='%2316a34a' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/></svg>"
                        style="width: 50px; opacity: 0.8;" alt="Doğrulandı">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 10px; font-family: monospace;">
                        UUID: {{ Str::uuid() }}<br>Tarih Damgası: {{ now()->format('Y-m-d H:i:s T') }}</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // 1. Çatışmayı Önleyen İzole Sekme (Tab) Yönetimi
            const tabBtns = document.querySelectorAll('.sys-tab-btn');
            const tabPanes = document.querySelectorAll('.sys-tab-pane');

            // Sayfa yenilendiğinde kalınan sekmeyi hatırlama
            let activeTab = localStorage.getItem('activeAuditTab') || 'audit-logs';

            // Eğer URL'de dispatch_page parametresi varsa direkt 2. sekmeyi aç
            if (window.location.search.includes('dispatch_page')) {
                activeTab = 'dispatch-logs';
            }

            const activateTab = (targetId) => {
                tabBtns.forEach(b => {
                    b.classList.remove('active');
                    b.style.color = 'var(--text-muted)';
                    b.style.borderBottomColor = 'transparent';
                });
                tabPanes.forEach(p => {
                    p.style.display = 'none';
                });

                const activeBtn = document.querySelector(`.sys-tab-btn[data-target="${targetId}"]`);
                const activePane = document.getElementById(targetId);

                if (activeBtn && activePane) {
                    activeBtn.classList.add('active');
                    activeBtn.style.color = 'var(--primary-color)';
                    activeBtn.style.borderBottomColor = 'var(--primary-color)';
                    activePane.style.display = 'block';
                    localStorage.setItem('activeAuditTab', targetId);
                }
            };

            // Sayfa ilk yüklendiğinde aktif sekmeyi çalıştır
            activateTab(activeTab);

            // Tıklama olaylarını dinle
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    activateTab(btn.getAttribute('data-target'));
                });
            });

            // 2. Audit Log Kanıt Modalı (Sekme 1)
            const proofModal = document.getElementById('proofModal');
            const proofText = document.getElementById('proof-text');

            document.querySelectorAll('.btn-proof').forEach(btn => {
                btn.addEventListener('click', function() {
                    const u = this.dataset.user;
                    const d = this.dataset.date;
                    const ip = this.dataset.ip || 'Sistem İçi';
                    const doc = this.dataset.doc || 'Bilinmeyen Belge';
                    const evt = this.dataset.event;

                    proofText.innerHTML =
                        `<strong>${u}</strong> adlı personel, <strong>${d}</strong> tarihinde <strong>${ip}</strong> IP adresi üzerinden <strong>${doc}</strong> isimli sistem verisi üzerinde <strong>[${evt.toUpperCase()}]</strong> işlemi gerçekleştirmiştir.<br><br><span style="color:var(--success-color); font-size:0.85rem;"><i data-lucide="shield-check" style="width:16px; vertical-align:middle;"></i> Sistem tarafından doğrulanmış, değiştirilemez denetim (audit) kaydıdır.</span>`;

                    proofModal.style.display = 'flex';
                    lucide.createIcons();
                });
            });

            // 3. İletim Makbuzu Modalı (Sekme 2)
            document.querySelectorAll('.btn-receipt').forEach(btn => {
                btn.addEventListener('click', function() {
                    const receiver = this.dataset.receiver;
                    const date = this.dataset.date;
                    const doc = this.dataset.doc;
                    const status = this.dataset.status;

                    proofText.innerHTML =
                        `<strong>${receiver}</strong> adlı kullanıcıya, <strong>${date}</strong> tarihinde <strong>${doc}</strong> isimli kayıt ile ilgili sistem bilgilendirme mesajı gönderilmiştir.<br><br>Sistem İletim Durumu: <strong style="color:var(--primary-color);">${status}</strong><br><br><span style="color:var(--success-color); font-size:0.85rem;"><i data-lucide="shield-check" style="width:16px; vertical-align:middle;"></i> Bu bildirim logu değiştirilemez sistem makbuzudur.</span>`;

                    proofModal.style.display = 'flex';
                    lucide.createIcons();
                });
            });

            // 4. Modal Kapatma Olayları
            document.querySelector('.close-modal').addEventListener('click', () => proofModal.style.display =
                'none');
            proofModal.addEventListener('click', (e) => {
                if (e.target === proofModal) proofModal.style.display = 'none';
            });
        });
    </script>
@endpush
