@extends('layouts.app')

@section('content')
    <div class="dashboard-welcome mb-30 flex-between">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--primary-color); margin-bottom: 5px;">
                Hoş Geldin, {{ auth()->user()->name }}
            </h1>
            <p class="text-muted" style="font-size: 0.95rem;">
                Bugün sizi bekleyen <strong style="color: var(--danger-color);">{{ $totalPendingTasks }}</strong> adet acil
                işlem var.
            </p>
        </div>
        <div class="date-badge"
            style="background: #fff; border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 30px; font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 8px; box-shadow: var(--card-shadow);">
            <i data-lucide="calendar" style="color: var(--accent-color); width: 18px; height: 18px;"></i>
            {{ $currentDate }}
        </div>
    </div>

    <div class="dashboard-grid">

        <div class="widget-card urgent-widget">
            <div class="widget-header">
                <h3 style="color: var(--danger-color);">
                    <i data-lucide="alert-circle" style="width: 20px; margin-right: 8px;"></i> Acil Aksiyonlar
                </h3>
                @if ($totalPendingTasks > 0)
                    <span class="pulse-badge" style="background: var(--danger-color);">
                        {{ $totalPendingTasks }} Görev
                    </span>
                @endif
            </div>
            <div class="widget-body">
                @if ($totalPendingTasks == 0)
                    <div class="empty-state">
                        <i data-lucide="check-circle-2"
                            style="color: var(--success-color); width: 48px; height: 48px; margin: 0 auto 10px; opacity: 1;"></i>
                        <p>Harika! Bekleyen acil bir göreviniz yok.</p>
                    </div>
                @else
                    <ul class="action-list">
                        @foreach ($pendingApprovals as $approval)
                            <a href="{{ route('documents.show', $approval->document_id) }}"
                                class="action-item workflow-item">
                                <div class="action-icon" style="background: #fef3c7; color: #d97706;">
                                    <i data-lucide="zap" style="width: 18px;"></i>
                                </div>
                                <div class="action-content">
                                    <strong>{{ $approval->document->document_number }}</strong>
                                    @if ($approval->user_id !== auth()->id())
                                        <span style="color: var(--danger-color); font-weight: bold; margin-top: 3px;">
                                            🤝 Vekaleten ({{ $approval->user->name }} adına)
                                        </span>
                                    @else
                                        <span>Onayınızı Bekliyor</span>
                                    @endif
                                </div>
                                <div class="action-arrow"><i data-lucide="chevron-right"></i></div>
                            </a>
                        @endforeach

                        @foreach ($pendingPhysicalReceipts as $doc)
                            <a href="{{ route('documents.show', $doc->id) }}" class="action-item physical-item">
                                <div class="action-icon" style="background: #fee2e2; color: #b91c1c;">
                                    <i data-lucide="inbox" style="width: 18px;"></i>
                                </div>
                                <div class="action-content">
                                    <strong>{{ $doc->document_number }}</strong>
                                    <span>Islak İmzalı Kopyayı Teslim Alın</span>
                                </div>
                                <div class="action-arrow"><i data-lucide="chevron-right"></i></div>
                            </a>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <h3 style="color: var(--primary-color);">
                    <i data-lucide="lock" style="width: 20px; margin-right: 8px; color: var(--text-muted);"></i> Üzerimdeki
                    Belgeler
                </h3>
            </div>
            <div class="widget-body">
                @if ($myLockedDocuments->count() == 0)
                    <div class="empty-state">
                        <i data-lucide="folder-open"
                            style="width: 48px; height: 48px; margin: 0 auto 10px; opacity: 0.3;"></i>
                        <p>Revize etmek için kilitlediğiniz belge yok.</p>
                    </div>
                @else
                    <ul class="action-list">
                        @foreach ($myLockedDocuments as $doc)
                            <a href="{{ route('documents.show', $doc->id) }}" class="action-item locked-item">
                                <div class="action-icon" style="background: #e0f2fe; color: #0284c7;">
                                    <i data-lucide="key" style="width: 18px;"></i>
                                </div>
                                <div class="action-content">
                                    <strong>{{ $doc->title }}</strong>
                                    <span class="text-muted">v{{ $doc->currentVersion?->version_number }} (Kilidi Aç veya
                                        Yükle)</span>
                                </div>
                                <div class="action-arrow" style="color: #0ea5e9; font-size: 0.85rem;">İşlem <i
                                        data-lucide="chevron-right" style="width: 14px; vertical-align: middle;"></i></div>
                            </a>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        @hasanyrole('Super Admin|Admin|Hukuk')
            <div class="widget-card">
                <div class="widget-header">
                    <h3 style="color: #b45309;">
                        <i data-lucide="hourglass" style="width: 20px; margin-right: 8px; color: #d97706;"></i> Yaklaşan
                        Sözleşmeler
                    </h3>
                </div>
                <div class="widget-body">
                    @if ($expiringContracts->count() == 0)
                        <div class="empty-state">
                            <i data-lucide="shield-check"
                                style="width: 48px; height: 48px; margin: 0 auto 10px; color: var(--success-color); opacity: 0.8;"></i>
                            <p>Önümüzdeki 30 gün içinde süresi dolacak aktif sözleşme yok.</p>
                        </div>
                    @else
                        <ul class="action-list">
                            @foreach ($expiringContracts as $doc)
                                <a href="{{ route('documents.show', $doc->id) }}" class="action-item"
                                    style="border-left: 4px solid #f59e0b;">
                                    <div class="action-icon" style="background: #fef3c7; color: #b45309;">
                                        <i data-lucide="calendar-off" style="width: 18px;"></i>
                                    </div>
                                    <div class="action-content">
                                        <strong>{{ $doc->document_number }}</strong>
                                        <span style="color: var(--danger-color); font-weight: bold;">
                                            Bitiş: {{ \Carbon\Carbon::parse($doc->expire_at)->format('d.m.Y') }}
                                            ({{ \Carbon\Carbon::parse($doc->expire_at)->diffInDays(now()) }} gün kaldı)
                                        </span>
                                    </div>
                                    <div class="action-arrow"><i data-lucide="chevron-right"></i></div>
                                </a>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endhasanyrole

        <div class="widget-card">
            <div class="widget-header">
                <h3>
                    <i data-lucide="pie-chart" style="width: 20px; margin-right: 8px; color: var(--accent-color);"></i>
                    Hesap Özeti
                </h3>
            </div>
            <div class="widget-body stats-grid">
                <div class="stat-box" style="background: #f0fdfa; border-color: #ccfbf1;">
                    <div class="stat-value" style="color: #0f766e;">{{ $totalAccessible }}</div>
                    <div class="stat-label" style="color: #0d9488;">Erişilebilir Belge</div>
                </div>
                <div class="stat-box" style="background: #fef2f2; border-color: #fee2e2;">
                    <div class="stat-value" style="color: #b91c1c;">{{ $totalArchived }}</div>
                    <div class="stat-label" style="color: #ef4444;">Sistem Arşivi</div>
                </div>
                <div class="stat-box" style="grid-column: span 2; background: #fffbeb; border-color: #fef3c7;">
                    <div class="stat-value" style="color: #b45309;">{{ $myDrafts }}</div>
                    <div class="stat-label" style="color: #d97706;">Taslak ve Reddedilmiş Belgelerim</div>
                </div>
            </div>
        </div>

        <div class="widget-card" style="grid-column: 1 / -1;">
            <div class="widget-header flex-between">
                <h3>
                    <i data-lucide="activity" style="width: 20px; margin-right: 8px; color: var(--text-muted);"></i> Son
                    Yüklediğim Belgeler
                </h3>
                <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary">Tümünü Gör</a>
            </div>
            <div class="widget-body">
                @if ($myRecentUploads->count() == 0)
                    <div class="empty-state">
                        <i data-lucide="file-x" style="width: 48px; height: 48px; margin: 0 auto 10px; opacity: 0.3;"></i>
                        <p>Henüz sisteme yüklediğiniz bir belge bulunmuyor.</p>
                    </div>
                @else
                    <ul class="action-list"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; flex-direction: row;">
                        @foreach ($myRecentUploads as $doc)
                            <a href="{{ route('documents.show', $doc->id) }}" class="action-item"
                                style="border-left: 4px solid var(--accent-color);">
                                <div class="action-icon" style="background: #eef2ff; color: #4f46e5;">
                                    <i data-lucide="file-text" style="width: 18px;"></i>
                                </div>
                                <div class="action-content">
                                    <strong>{{ $doc->document_number }} - {{ $doc->title }}</strong>
                                    <div style="display: flex; gap: 8px; margin-top: 4px;">
                                        <span class="badge badge-secondary"
                                            style="font-size: 0.65rem; padding: 2px 6px;">{{ mb_strtoupper($doc->status_text) }}</span>
                                        <span class="text-muted"
                                            style="font-size: 0.75rem;">{{ $doc->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <style>
        /* CSS GRID MİMARİSİ (Aynı şık stil korunuyor) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .widget-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .widget-header h3 {
            margin: 0;
            font-size: 1.05rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
        }

        .widget-body {
            padding: 24px;
            flex: 1;
        }

        .action-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .action-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        .action-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .workflow-item {
            border-left: 4px solid var(--warning-color);
        }

        .workflow-item:hover {
            background: #fffbeb;
            border-color: #fcd34d;
        }

        .physical-item {
            border-left: 4px solid var(--danger-color);
        }

        .physical-item:hover {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .locked-item {
            border-left: 4px solid var(--accent-color);
        }

        .locked-item:hover {
            background: #f5f3ff;
            border-color: #c7d2fe;
        }

        .action-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .action-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .action-content strong {
            font-size: 0.95rem;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-content span {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .action-arrow {
            color: #94a3b8;
            display: flex;
            align-items: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-box {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: var(--transition);
        }

        .stat-box:hover {
            transform: scale(1.02);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            color: var(--text-muted);
            padding: 30px 10px;
        }

        .pulse-badge {
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
    </style>
@endpush
