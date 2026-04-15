@extends('layouts.app')

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

@section('content')
    <div class="dashboard-welcome mb-20 flex-between"
        style="background: linear-gradient(135deg, var(--primary-color) 0%, #0f172a 100%); padding: 30px 40px; border-radius: 16px; color: #fff; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2); margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #f8fafc; margin-bottom: 8px; letter-spacing: -0.5px;">
                {{ __('Hoş Geldin,') }} {{ auth()->user()->name }} 👋
            </h1>
            <p style="font-size: 1rem; color: #94a3b8; margin: 0;">
                {!! __(
                    'Bugün sizi bekleyen <strong style="color: #f87171; font-size: 1.1rem; padding: 0 4px;">:count</strong> adet acil işlem var.',
                    ['count' => $totalPendingTasks],
                ) !!}
            </p>
        </div>
        <div class="date-badge"
            style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); padding: 12px 24px; border-radius: 30px; font-weight: 600; color: #f8fafc; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="calendar" style="color: #38bdf8; width: 20px; height: 20px;"></i>
            {{ \Carbon\Carbon::now()->locale(app()->getLocale())->translatedFormat('d F Y') }}
        </div>
    </div>

    <div class="quick-actions mb-30" style="display: flex; gap: 15px; flex-wrap: wrap;">
        <a href="{{ route('documents.create') }}" class="btn btn-primary"
            style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
            <i data-lucide="upload-cloud" style="width: 18px;"></i> {{ __('Yeni Belge Yükle') }}
        </a>
        <a href="{{ route('documents.index') }}" class="btn"
            style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; background: #fff; color: var(--text-color); border: 1px solid var(--border-color); font-weight: 600; box-shadow: var(--card-shadow);">
            <i data-lucide="search" style="width: 18px; color: var(--accent-color);"></i> {{ __('Gelişmiş Arama') }}
        </a>
        <a href="{{ route('folders.index') }}" class="btn"
            style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; background: #fff; color: var(--text-color); border: 1px solid var(--border-color); font-weight: 600; box-shadow: var(--card-shadow);">
            <i data-lucide="folder-tree" style="width: 18px; color: var(--warning-color);"></i> {{ __('Klasörlere Git') }}
        </a>
    </div>

    <div class="dashboard-grid">

        <div class="widget-card urgent-widget">
            <div class="widget-header">
                <h3 style="color: var(--danger-color);">
                    <i data-lucide="alert-circle" style="width: 20px; margin-right: 8px;"></i> {{ __('Acil Aksiyonlar') }}
                </h3>
                @if ($totalPendingTasks > 0)
                    <span class="pulse-badge" style="background: var(--danger-color);">
                        {{ $totalPendingTasks }} {{ __('Görev') }}
                    </span>
                @endif
            </div>
            <div class="widget-body" style="background: #f8fafc; display: flex; flex-direction: column;">
                @if ($totalPendingTasks == 0)
                    <div class="empty-state">
                        <i data-lucide="check-circle-2"
                            style="color: var(--success-color); width: 48px; height: 48px; margin: 0 auto 10px; opacity: 1;"></i>
                        <p>{{ __('Harika! Bekleyen acil bir göreviniz yok.') }}</p>
                    </div>
                @else
                    <ul class="action-list">
                        @foreach ($displayPendingApprovals as $approval)
                            <a href="{{ route('documents.show', $approval->document_id) }}"
                                class="action-item workflow-item">
                                <div class="action-icon" style="background: #fef3c7; color: #d97706;">
                                    <i data-lucide="zap" style="width: 18px;"></i>
                                </div>
                                <div class="action-content">
                                    <strong>{{ $approval->document->document_number }}</strong>
                                    @if ($approval->user_id !== auth()->id())
                                        <span style="color: var(--danger-color); font-weight: bold; margin-top: 3px;">
                                            🤝 {{ __('Vekaleten (:name adına)', ['name' => $approval->user->name]) }}
                                        </span>
                                    @else
                                        <span>{{ __('Onayınızı Bekliyor') }}</span>
                                    @endif
                                </div>
                                <div class="action-arrow"><i data-lucide="chevron-right"></i></div>
                            </a>
                        @endforeach

                        @foreach ($displayPhysicalReceipts as $doc)
                            <a href="{{ route('documents.show', $doc->id) }}" class="action-item physical-item">
                                <div class="action-icon" style="background: #fee2e2; color: #b91c1c;">
                                    <i data-lucide="inbox" style="width: 18px;"></i>
                                </div>
                                <div class="action-content">
                                    <strong>{{ $doc->document_number }}</strong>
                                    <span>{{ __('Islak İmzalı Kopyayı Teslim Alın') }}</span>
                                </div>
                                <div class="action-arrow"><i data-lucide="chevron-right"></i></div>
                            </a>
                        @endforeach
                    </ul>

                    @if ($totalPendingTasks > $displayPendingApprovals->count() + $displayPhysicalReceipts->count())
                        <div
                            style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); text-align: center;">
                            <a href="{{ route('documents.index', ['status' => 'pending']) }}"
                                class="btn btn-sm btn-outline-danger" style="background: #fff; width: 100%; padding: 8px;">
                                {{ __('Tüm Acil Görevleri Gör (:count)', ['count' => $totalPendingTasks]) }}
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="widget-card">
            <div class="widget-header">
                <h3 style="color: var(--primary-color);">
                    <i data-lucide="lock" style="width: 20px; margin-right: 8px; color: var(--text-muted);"></i>
                    {{ __('Üzerimdeki Belgeler') }}
                </h3>
            </div>
            <div class="widget-body" style="background: #f8fafc; display: flex; flex-direction: column;">
                @if ($totalLockedCount == 0)
                    <div class="empty-state">
                        <i data-lucide="folder-open"
                            style="width: 48px; height: 48px; margin: 0 auto 10px; opacity: 0.3;"></i>
                        <p>{{ __('Revize etmek için kilitlediğiniz belge yok.') }}</p>
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
                                    <span class="text-muted">v{{ $doc->currentVersion?->version_number }}
                                        ({{ __('Kilidi Aç veya Yükle') }})
                                    </span>
                                </div>
                                <div class="action-arrow" style="color: #0ea5e9; font-size: 0.85rem;">{{ __('İşlem') }}
                                    <i data-lucide="chevron-right" style="width: 14px; vertical-align: middle;"></i>
                                </div>
                            </a>
                        @endforeach
                    </ul>

                    @if ($totalLockedCount > 5)
                        <div
                            style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); text-align: center;">
                            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary"
                                style="background: #fff; width: 100%; padding: 8px;">
                                {{ __('Tüm Kilitli Belgeleri Gör (:count)', ['count' => $totalLockedCount]) }}
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        @hasanyrole('Super Admin|Admin|Hukuk')
            <div class="widget-card">
                <div class="widget-header flex-between">
                    <h3 style="color: #b45309;">
                        <i data-lucide="hourglass" style="width: 20px; margin-right: 8px; color: #d97706;"></i>
                        {{ __('Yaklaşan Sözleşmeler') }}
                    </h3>
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary"
                        style="background: #fff;">{{ __('Tümünü Gör') }}</a>
                </div>
                <div class="widget-body" style="background: #f8fafc;">
                    @if ($expiringContracts->count() == 0)
                        <div class="empty-state">
                            <i data-lucide="shield-check"
                                style="width: 48px; height: 48px; margin: 0 auto 10px; color: var(--success-color); opacity: 0.8;"></i>
                            <p>{{ __('Önümüzdeki 30 gün içinde süresi dolacak aktif sözleşme yok.') }}</p>
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
                                            {{ __('Bitiş:') }} {{ \Carbon\Carbon::parse($doc->expire_at)->format('d.m.Y') }}
                                            ({{ __(':count gün kaldı', ['count' => \Carbon\Carbon::parse($doc->expire_at)->diffInDays(now())]) }})
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
                    {{ __('Hesap Özeti') }}
                </h3>
            </div>
            <div class="widget-body stats-grid">
                <div class="stat-box"
                    style="background: #f0fdfa; border-color: #ccfbf1; position: relative; overflow: hidden;">
                    <i data-lucide="folder-check"
                        style="position: absolute; right: -15px; bottom: -15px; width: 80px; height: 80px; color: #14b8a6; opacity: 0.1;"></i>
                    <div class="stat-value" style="color: #0f766e; z-index: 1;">{{ $totalAccessible }}</div>
                    <div class="stat-label" style="color: #0d9488; z-index: 1;">{{ __('Erişilebilir Belge') }}</div>
                </div>
                <div class="stat-box"
                    style="background: #fef2f2; border-color: #fee2e2; position: relative; overflow: hidden;">
                    <i data-lucide="archive"
                        style="position: absolute; right: -15px; bottom: -15px; width: 80px; height: 80px; color: #ef4444; opacity: 0.1;"></i>
                    <div class="stat-value" style="color: #b91c1c; z-index: 1;">{{ $totalArchived }}</div>
                    <div class="stat-label" style="color: #ef4444; z-index: 1;">{{ __('Sistem Arşivi') }}</div>
                </div>
                <div class="stat-box"
                    style="grid-column: span 2; background: #fffbeb; border-color: #fef3c7; position: relative; overflow: hidden; flex-direction: row; justify-content: flex-start; align-items: center; gap: 20px;">
                    <i data-lucide="file-edit"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 60px; height: 60px; color: #f59e0b; opacity: 0.1;"></i>
                    <div class="stat-value" style="color: #b45309; z-index: 1; margin: 0;">{{ $myDrafts }}</div>
                    <div class="stat-label" style="color: #d97706; z-index: 1; text-align: left;">
                        {{ __('Taslak ve Reddedilmiş Belgelerim') }}</div>
                </div>
            </div>
        </div>

        <div class="card glass-card mt-30" style="border-top: 4px solid var(--warning-color);">
            <div class="card-header flex-between" style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.2);">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 8px; color: var(--text-color);">
                    <i data-lucide="star" style="fill: var(--warning-color); color: var(--warning-color);"></i>
                    {{ __('Favori Belgelerim') }}
                </h3>

                <form id="favSearchForm" onsubmit="return false;" style="display: flex; gap: 10px;">
                    <div class="search-box" style="position: relative;">
                        <i data-lucide="search"
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-muted);"></i>
                        <input type="text" id="liveFavSearchInput" name="fav_search" value="{{ $keyword ?? '' }}"
                            placeholder="{{ __('Favorilerde ara...') }}"
                            style="width: 200px; padding: 8px 30px 8px 32px; border-radius: 20px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); outline: none;">
                        <i data-lucide="loader" id="favSearchSpinner" class="spin"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--warning-color); display: none;"></i>
                    </div>
                </form>
            </div>

            <div class="card-body p-0" id="favorites-list-container" style="transition: opacity 0.2s ease;">
                @include('dashboard.partials.favorites-list')
            </div>
        </div>

        <div class="widget-card" style="grid-column: 1 / -1;">
            <div class="widget-header flex-between">
                <h3>
                    <i data-lucide="activity" style="width: 20px; margin-right: 8px; color: var(--text-muted);"></i>
                    {{ __('Son Yüklediğim Belgeler') }}
                </h3>
                <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary"
                    style="background: #fff;">{{ __('Tümünü Gör') }}</a>
            </div>
            <div class="widget-body" style="background: #f8fafc;">
                @if ($myRecentUploads->count() == 0)
                    <div class="empty-state">
                        <i data-lucide="file-x" style="width: 48px; height: 48px; margin: 0 auto 10px; opacity: 0.3;"></i>
                        <p>{{ __('Henüz sisteme yüklediğiniz bir belge bulunmuyor.') }}</p>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const favButtons = document.querySelectorAll('.toggle-fav-btn');

        favButtons.forEach(btn => {
            btn.addEventListener('click', async function() {
                const docId = this.getAttribute('data-id');
                const icon = this.querySelector('.fav-icon');

                // Optimistic UI Update (Kullanıcıya anında tepki ver)
                const isCurrentlyFav = icon.style.fill !== 'none';
                icon.style.fill = isCurrentlyFav ? 'none' : 'var(--warning-color)';
                this.style.transform = "scale(1.2)";
                setTimeout(() => this.style.transform = "scale(1)", 200);

                try {
                    const response = await fetch(`/documents/${docId}/favorite`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) throw new Error(data.message || 'Hata oluştu');

                    // Backend sonucuyla UI'ı senkronize et
                    icon.style.fill = data.is_favorited ? 'var(--warning-color)' : 'none';

                    // Dashboarddaysa ve favoriden çıkarıldıysa satırı gizle (UX detayı)
                    if (!data.is_favorited && window.location.pathname.includes(
                            'dashboard')) {
                        this.closest('li').style.display = 'none';
                    }

                } catch (error) {
                    // Hata olursa UI'ı eski haline al
                    icon.style.fill = isCurrentlyFav ? 'var(--warning-color)' : 'none';
                    alert('İşlem başarısız: ' + error.message);
                }
            });
        });
        // --- FAVORİLER CANLI ARAMA (LIVE SEARCH) ---
        const favSearchInput = document.getElementById('liveFavSearchInput');
        const favListContainer = document.getElementById('favorites-list-container');
        const favSpinner = document.getElementById('favSearchSpinner');
        let favDebounceTimer;

        if (favSearchInput && favListContainer) {
            favSearchInput.addEventListener('input', function() {
                clearTimeout(favDebounceTimer);

                if (favSpinner) favSpinner.style.display = 'block';
                favListContainer.style.opacity = '0.5';

                // Kullanıcı yazmayı bıraktıktan 400ms sonra arama yapar (Sunucuyu yormaz)
                favDebounceTimer = setTimeout(() => {
                    const query = this.value;
                    const url = new URL(window.location.href);

                    if (query) url.searchParams.set('fav_search', query);
                    else url.searchParams.delete('fav_search');

                    window.history.pushState({}, '', url);

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest', // Controller'a AJAX olduğunu söyler
                                'Accept': 'text/html'
                            }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Arama hatası');
                            return res.text();
                        })
                        .then(html => {
                            favListContainer.innerHTML = html;
                            lucide.createIcons(); // Gelen yeni HTML'deki ikonları canlandır
                        })
                        .finally(() => {
                            if (favSpinner) favSpinner.style.display = 'none';
                            favListContainer.style.opacity = '1';
                        });
                }, 400);
            });

            // --- FAVORİDEN ÇIKARMA (EVENT DELEGATION) ---
            // Tablo AJAX ile yenilense bile yıldız butonlarının çalışmasını sağlar
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            favListContainer.addEventListener('click', async function(e) {
                const btn = e.target.closest('.toggle-fav-btn');
                if (!btn) return;

                e.preventDefault();
                const docId = btn.getAttribute('data-id');
                const icon = btn.querySelector('.fav-icon');

                // Görseli anında güncelle (Optimistic UI)
                icon.style.fill = 'none';
                btn.style.transform = "scale(1.2)";
                setTimeout(() => btn.style.transform = "scale(1)", 200);

                try {
                    const response = await fetch(`/documents/${docId}/favorite`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message);

                    // Dashboard'da favoriden çıkarılan öğeyi DOM'dan silerek gizle
                    if (!data.is_favorited) {
                        btn.closest('li').style.display = 'none';
                    }

                } catch (error) {
                    icon.style.fill = 'var(--warning-color)';
                    console.error(error);
                }
            });
        }
    });
</script>

@push('styles')
    <style>
        /* CSS GRID MİMARİSİ */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .widget-card {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--surface-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .widget-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            font-weight: 600;
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
            padding: 16px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
        }

        .action-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .workflow-item {
            border-left: 4px solid var(--warning-color);
        }

        .physical-item {
            border-left: 4px solid var(--danger-color);
        }

        .locked-item {
            border-left: 4px solid var(--accent-color);
        }

        .action-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
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
            margin-top: 4px;
        }

        .action-arrow {
            color: #94a3b8;
            display: flex;
            align-items: center;
            transition: transform 0.2s ease;
        }

        .action-item:hover .action-arrow {
            transform: translateX(4px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-box {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .stat-box:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            color: var(--text-muted);
            padding: 40px 20px;
        }

        .pulse-badge {
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: translateY(-50%) rotate(360deg);
            }
        }
    </style>
@endpush
