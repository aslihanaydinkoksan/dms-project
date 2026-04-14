@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20" style="flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">📂
                {{ __('Tüm Belgeler ve Arşiv') }}</h1>
            <p class="text-muted" style="font-size: 0.95rem;">
                {{ __('Sistemdeki yetkili olduğunuz tüm dokümanlarda arama yapın, filtreleyin ve yönetin.') }}</p>
        </div>

        <div class="header-actions">
            <a href="{{ route('documents.create') }}" class="btn btn-primary"
                style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; font-weight: 600; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);">
                <i data-lucide="upload-cloud" style="width: 18px;"></i> {{ __('Yeni Belge Yükle') }}
            </a>
        </div>
    </div>

    <div class="stats-grid mb-30"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">

        <a href="{{ route('documents.index', array_merge(request()->query(), ['status' => 'published'])) }}"
            class="filter-card theme-success">
            <div class="icon-box"><i data-lucide="check-circle" style="width: 24px; height: 24px;"></i></div>
            <div>
                <div class="card-value">{{ $stats->approved ?? 0 }}</div>
                <div class="card-label">{{ __('ONAYLI BELGELER') }}</div>
            </div>
        </a>

        <a href="{{ route('documents.index', array_merge(request()->query(), ['status' => 'rejected'])) }}"
            class="filter-card theme-danger">
            <div class="icon-box"><i data-lucide="x-circle" style="width: 24px; height: 24px;"></i></div>
            <div>
                <div class="card-value">{{ $stats->rejected ?? 0 }}</div>
                <div class="card-label">{{ __('REDDEDİLENLER') }}</div>
            </div>
        </a>

        <a href="{{ route('documents.index', array_merge(request()->query(), ['privacy' => 'public'])) }}"
            class="filter-card theme-info">
            <div class="icon-box"><i data-lucide="globe" style="width: 24px; height: 24px;"></i></div>
            <div>
                <div class="card-value">{{ $stats->public ?? 0 }}</div>
                <div class="card-label">{{ __('HERKESE AÇIK') }}</div>
            </div>
        </a>

        <a href="{{ route('documents.index', array_merge(request()->query(), ['privacy' => 'secret'])) }}"
            class="filter-card theme-warning">
            <div class="icon-box"><i data-lucide="shield-alert" style="width: 24px; height: 24px;"></i></div>
            <div>
                <div class="card-value">{{ $stats->secret ?? 0 }}</div>
                <div class="card-label">{{ __('GİZLİ BELGELER') }}</div>
            </div>
        </a>

    </div>

    <div class="filter-panel glass-card mb-30"
        style="padding: 20px; border-radius: 12px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border-color);">

        <form id="searchForm"
            style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr 1.2fr; gap: 15px; align-items: flex-end;">

            <div style="position: relative;">
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block;">{{ __('Kelime ile Ara') }}</label>
                <i data-lucide="search"
                    style="position: absolute; left: 15px; bottom: 12px; color: var(--text-muted); width: 18px;"></i>
                <input type="text" id="liveSearchInput" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="{{ __('Belge Adı, No veya İçerik...') }}"
                    style="width: 100%; padding: 10px 15px 10px 42px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.95rem;">
                <i data-lucide="loader" id="searchSpinner" class="spin"
                    style="position: absolute; right: 15px; bottom: 12px; color: var(--primary-color); width: 18px; display: none;"></i>
            </div>

            <div>
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block;">{{ __('Başlangıç Tarihi') }}</label>
                <input type="date" name="start_date" id="startDate" class="form-control"
                    value="{{ request('start_date') }}"
                    style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-color);">
            </div>

            <div>
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block;">{{ __('Bitiş Tarihi') }}</label>
                <input type="date" name="end_date" id="endDate" class="form-control" value="{{ request('end_date') }}"
                    style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-color);">
            </div>

            <div style="position: relative;">
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block;">{{ __('Statü Durumu') }}</label>
                <i data-lucide="activity"
                    style="position: absolute; left: 12px; bottom: 12px; color: var(--text-muted); width: 16px; pointer-events: none;"></i>
                <select id="statusFilter" name="status" class="form-control"
                    style="width: 100%; padding: 10px 15px 10px 36px; border-radius: 8px; cursor: pointer; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-color);">
                    <option value="">{{ __('Tüm Statüler') }}</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>📝 {{ __('Taslak') }}
                    </option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳
                        {{ __('Onay Bekleyenler') }}
                    </option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>✅
                        {{ __('Yayında Olanlar') }}
                    </option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>❌
                        {{ __('Reddedilenler') }}
                    </option>
                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>🗄️
                        {{ __('Arşivlenenler') }}
                    </option>
                </select>
            </div>

            <div style="position: relative;">
                <label
                    style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: block;">{{ __('Gizlilik Seviyesi') }}</label>
                <i data-lucide="shield"
                    style="position: absolute; left: 12px; bottom: 12px; color: var(--text-muted); width: 16px; pointer-events: none;"></i>
                <select id="privacyFilter" name="privacy" class="form-control"
                    style="width: 100%; padding: 10px 15px 10px 36px; border-radius: 8px; cursor: pointer; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-color);">
                    <option value="">{{ __('Tüm Gizlilikler') }}</option>
                    <option value="public" {{ request('privacy') == 'public' ? 'selected' : '' }}>🌍
                        {{ __('Herkese Açık') }}</option>
                    <option value="confidential" {{ request('privacy') == 'confidential' ? 'selected' : '' }}>🔒
                        {{ __('Hizmete Özel') }}</option>
                    <option value="strictly_confidential"
                        {{ request('privacy') == 'strictly_confidential' ? 'selected' : '' }}>🕵️ {{ __('Çok Gizli') }}
                    </option>
                </select>
            </div>
        </form>
    </div>

    <div class="card glass-card p-20" style="min-height: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div id="document-list-container" style="transition: opacity 0.2s ease;">
            @include('documents.partials.list')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const searchInput = document.getElementById('liveSearchInput');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const statusFilter = document.getElementById('statusFilter');
            const privacyFilter = document.getElementById('privacyFilter');
            const listContainer = document.getElementById('document-list-container');
            const spinner = document.getElementById('searchSpinner');

            if (!searchInput || !listContainer) return;

            let debounceTimer;

            // Ortak Fetch Fonksiyonu (Hem yazıya hem tarihe hem seçimlere bakar)
            const triggerSearch = () => {
                clearTimeout(debounceTimer);

                if (spinner) spinner.style.display = 'block';
                listContainer.style.opacity = '0.5'; // Yükleniyor hissi (Fade)

                debounceTimer = setTimeout(() => {
                    const query = searchInput.value;
                    const start = startDate ? startDate.value : '';
                    const end = endDate ? endDate.value : '';
                    const status = statusFilter ? statusFilter.value : '';
                    const privacy = privacyFilter ? privacyFilter.value : '';

                    fetchDocuments(query, start, end, status, privacy);
                }, 400); // Kullanıcı yazmayı bitirdikten 400ms sonra isteği atar
            };

            // Event Listeners (Tüm Input ve Select'ler değiştiğinde tetiklenir)
            searchInput.addEventListener('input', triggerSearch);
            if (startDate) startDate.addEventListener('change', triggerSearch);
            if (endDate) endDate.addEventListener('change', triggerSearch);
            if (statusFilter) statusFilter.addEventListener('change', triggerSearch);
            if (privacyFilter) privacyFilter.addEventListener('change', triggerSearch);

            function fetchDocuments(query, start, end, status, privacy) {
                const url = new URL(window.location.href);

                // URL Parametrelerini Dinamik Oluştur
                if (query) url.searchParams.set('q', query);
                else url.searchParams.delete('q');

                if (start) url.searchParams.set('start_date', start);
                else url.searchParams.delete('start_date');

                if (end) url.searchParams.set('end_date', end);
                else url.searchParams.delete('end_date');

                if (status) url.searchParams.set('status', status);
                else url.searchParams.delete('status');

                if (privacy) url.searchParams.set('privacy', privacy);
                else url.searchParams.delete('privacy');

                url.searchParams.delete('page'); // Yeni aramada sayfayı daima 1'e sıfırla

                window.history.pushState({}, '', url); // Kopyala/Yapıştır uyumlu kalması için linki güncelle

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Sunucu Hatası');
                        return response.text();
                    })
                    .then(html => {
                        listContainer.innerHTML = html;
                        lucide.createIcons(); // Yeni gelen tablodaki ikonları çiz (Çok Önemli!)
                    })
                    .catch(error => {
                        console.error('Arama Hatası:', error);
                    })
                    .finally(() => {
                        if (spinner) spinner.style.display = 'none';
                        listContainer.style.opacity = '1';
                    });
            }
        });
    </script>
    <style>
        /* Lucide ikonu dönme efekti için minik bir CSS */
        @keyframes spin {
            from {
                transform: translateY(-50%) rotate(0deg);
            }

            to {
                transform: translateY(-50%) rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        /* Arama barı için responsive grid ayarı */
        @media (max-width: 1024px) {
            #searchForm {
                grid-template-columns: 1fr 1fr;
            }

            #searchForm>div:first-child {
                grid-column: span 2;
            }
        }

        @media (max-width: 640px) {
            #searchForm {
                grid-template-columns: 1fr;
            }

            #searchForm>div:first-child {
                grid-column: span 1;
            }
        }

        /* YENİ: Tıklanabilir İstatistik Kartları CSS'i */
        .stat-card {
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            text-decoration: none;
            /* Link alt çizgisini kaldırır */
            color: inherit;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            /* Kartı hafifçe yukarı kaldırır */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            /* Gölgeleri derinleştirir */
        }

        .stat-card:active {
            transform: translateY(-1px);
            /* Tıklanma hissi verir */
        }

        /* --- DİNAMİK RENK TEMALARI (Model ile Senkronize) --- */
        .theme-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .theme-success .icon-box {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .theme-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .theme-danger .icon-box {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .theme-info {
            background-color: #f0f9ff;
            color: #1e3a8a;
            border: 1px solid #bfdbfe;
        }

        .theme-info .icon-box {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .theme-warning {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .theme-warning .icon-box {
            background-color: #fef3c7;
            color: #d97706;
        }

        .theme-primary {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .theme-primary .icon-box {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .theme-secondary {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .theme-secondary .icon-box {
            background-color: #f1f5f9;
            color: #64748b;
        }

        /* Ortak Rozet Sınıfı Eklentisi */
        .badge.theme-success,
        .badge.theme-danger,
        .badge.theme-info,
        .badge.theme-warning,
        .badge.theme-primary,
        .badge.theme-secondary {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
        }

        /* --- TIKLANABİLİR KART (WIDGET) ANİMASYONLARI --- */
        .filter-card {
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .filter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        .filter-card:active {
            transform: translateY(-1px);
        }

        .filter-card .icon-box {
            padding: 12px;
            border-radius: 10px;
        }

        .filter-card .card-value {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
        }

        .filter-card .card-label {
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 4px;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }
    </style>
@endpush
