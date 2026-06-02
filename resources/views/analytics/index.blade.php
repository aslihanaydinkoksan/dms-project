@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20" style="flex-wrap: wrap; gap: 20px; animation: fadeIn 0.5s ease;">
        <div>
            <h1 class="page-title"
                style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                <div
                    style="background: #eef2ff; color: var(--accent-color); padding: 10px; border-radius: 12px; display:flex;">
                    <i data-lucide="bar-chart-2" style="width: 28px; height: 28px;"></i>
                </div>
                {{ __('Sistem Analitiği ve Performans Özeti') }}
            </h1>
            <p class="text-muted" style="font-size: 0.95rem; margin-top: 5px;">
                {{ __('Sistemin genel sağlığını, doküman dağılımlarını ve kullanıcı aktivitelerini anlık olarak inceleyin.') }}
            </p>
        </div>

        {{-- TARİH FİLTRESİ (Date Filter Bar) --}}
        <form method="GET" action="{{ route('analytics.index') }}" class="header-actions"
            style="display: flex; gap: 10px; align-items: center; background: var(--surface-color); padding: 10px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <div>
                <label
                    style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px; display: block;">{{ __('Başlangıç') }}</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm"
                    style="border-radius: 6px;">
            </div>
            <div>
                <label
                    style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px; display: block;">{{ __('Bitiş') }}</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm"
                    style="border-radius: 6px;">
            </div>
            <div style="padding-top: 18px;">
                <button type="submit" class="btn btn-primary btn-sm"
                    style="height: 32px; display: flex; align-items: center; gap: 5px; font-weight: 600;">
                    <i data-lucide="filter" style="width: 14px;"></i> {{ __('Uygula') }}
                </button>
            </div>
            @if ($startDate || $endDate)
                <div style="padding-top: 18px;">
                    <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary btn-sm"
                        style="height: 32px; display: flex; align-items: center;" title="Filtreyi Temizle">
                        <i data-lucide="x" style="width: 14px;"></i>
                    </a>
                </div>
            @endif
        </form>
    </div>

    {{-- 1. SATIR: ÖZET KARTLARI (KPI WIDGETS) --}}
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; animation: fadeIn 0.6s ease;">

        <div class="card glass-card"
            style="padding: 20px; border-radius: 12px; border-left: 4px solid var(--primary-color); display: flex; align-items: center; gap: 15px;">
            <div style="background: #eef2ff; color: var(--primary-color); padding: 15px; border-radius: 10px;"><i
                    data-lucide="layers" style="width: 28px; height: 28px;"></i></div>
            <div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-color); line-height: 1;">
                    {{ number_format($analyticsData['summary']['total']) }}</div>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-top: 5px;">
                    {{ __('TOPLAM BELGE') }}</div>
            </div>
        </div>

        <div class="card glass-card"
            style="padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; display: flex; align-items: center; gap: 15px;">
            <div style="background: #dcfce7; color: #10b981; padding: 15px; border-radius: 10px;"><i
                    data-lucide="check-circle" style="width: 28px; height: 28px;"></i></div>
            <div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-color); line-height: 1;">
                    {{ number_format($analyticsData['summary']['approved']) }}</div>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-top: 5px;">
                    {{ __('ONAYLI / YAYINDA') }}</div>
            </div>
        </div>

        <div class="card glass-card"
            style="padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b; display: flex; align-items: center; gap: 15px;">
            <div style="background: #fef3c7; color: #d97706; padding: 15px; border-radius: 10px;"><i data-lucide="clock"
                    style="width: 28px; height: 28px;"></i></div>
            <div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-color); line-height: 1;">
                    {{ number_format($analyticsData['summary']['pending']) }}</div>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-top: 5px;">
                    {{ __('BEKLEYEN İŞLEM') }}</div>
            </div>
        </div>

        <div class="card glass-card"
            style="padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444; display: flex; align-items: center; gap: 15px;">
            <div style="background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 10px;"><i data-lucide="x-circle"
                    style="width: 28px; height: 28px;"></i></div>
            <div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-color); line-height: 1;">
                    {{ number_format($analyticsData['summary']['rejected']) }}</div>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-top: 5px;">
                    {{ __('REDDEDİLENLER') }}</div>
            </div>
        </div>
    </div>

    {{-- 2. SATIR: GRAFİK GRID SİSTEMİ --}}
    <div class="analytics-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">

        {{-- A. STATÜ DAĞILIMI (DONUT) --}}
        <div class="card glass-card" style="border-radius: 12px; padding: 25px; animation: fadeIn 0.7s ease;">
            <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                <i data-lucide="pie-chart" style="width: 20px; color: var(--accent-color);"></i> {{ __('Statü Dağılımı') }}
            </h4>
            <div id="chart-status" style="min-height: 300px; display: flex; justify-content: center;"></div>
        </div>

        {{-- B. BELGE TİPİ DAĞILIMI (PIE/POLAR) --}}
        <div class="card glass-card" style="border-radius: 12px; padding: 25px; animation: fadeIn 0.8s ease;">
            <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                <i data-lucide="file-type" style="width: 20px; color: var(--accent-color);"></i>
                {{ __('Belge Tipi Dağılımı') }}
            </h4>
            <div id="chart-types" style="min-height: 300px; display: flex; justify-content: center;"></div>
        </div>

        {{-- C. KULLANICI LİDERLİK TABLOSU (HORIZONTAL BAR) --}}
        <div class="card glass-card" style="border-radius: 12px; padding: 25px; animation: fadeIn 0.9s ease;">
            <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                <i data-lucide="award" style="width: 20px; color: #eab308;"></i> {{ __('En Aktif Kullanıcılar (Top 7)') }}
            </h4>
            <div id="chart-users" style="min-height: 300px;"></div>
        </div>

        {{-- D. DEPARTMAN AKTİVİTELERİ (COLUMN BAR) --}}
        <div class="card glass-card" style="border-radius: 12px; padding: 25px; animation: fadeIn 1s ease;">
            <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                <i data-lucide="building-2" style="width: 20px; color: var(--primary-color);"></i>
                {{ __('Departman Aktiviteleri') }}
            </h4>
            <div id="chart-departments" style="min-height: 300px;"></div>
        </div>

    </div>

    {{-- 3. SATIR: GENİŞ TREND GRAFİĞİ (SMOOTH AREA) --}}
    <div class="card glass-card"
        style="border-radius: 12px; padding: 25px; margin-bottom: 40px; animation: fadeIn 1.1s ease;">
        <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
            <i data-lucide="trending-up" style="width: 20px; color: var(--accent-color);"></i>
            {{ __('Sistem Yükleme Trendi (Gelişim)') }}
        </h4>
        <div id="chart-trend" style="min-height: 350px;"></div>
    </div>
@endsection

@push('scripts')
    {{-- APEX CHARTS CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* ApexCharts Tooltip Özelleştirmesi (Glassmorphism uyumlu) */
        .apexcharts-tooltip {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid var(--border-color) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            border-radius: 8px !important;
            color: var(--text-color) !important;
            font-family: inherit !important;
        }

        .apexcharts-tooltip-title {
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            font-weight: 700 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // Backend'den gelen saf veriler
            const data = @json($analyticsData);

            // Ortak Font Ailesi
            const fontFamily = "'Inter', 'Segoe UI', sans-serif";

            // 1. STATÜ DAĞILIMI (DONUT CHART)
            if (data.status.series.length > 0) {
                new ApexCharts(document.querySelector("#chart-status"), {
                    series: data.status.series,
                    labels: data.status.labels,
                    chart: {
                        type: 'donut',
                        height: 320,
                        fontFamily: fontFamily,
                        animations: {
                            enabled: true,
                            dynamicAnimation: {
                                speed: 1000
                            }
                        }
                    },
                    colors: ['#10b981', '#f59e0b', '#ef4444'], // Yeşil, Sarı, Kırmızı
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '24px',
                                        fontWeight: 700
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'Toplam',
                                        fontSize: '14px',
                                        color: '#64748b'
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        colors: '#ffffff',
                        width: 2
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center',
                        fontSize: '13px',
                        markers: {
                            radius: 12
                        }
                    }
                }).render();
            }

            // 2. BELGE TİPİ DAĞILIMI (PIE CHART)
            if (data.types.series.length > 0) {
                new ApexCharts(document.querySelector("#chart-types"), {
                    series: data.types.series,
                    labels: data.types.labels,
                    chart: {
                        type: 'pie',
                        height: 320,
                        fontFamily: fontFamily
                    },
                    colors: ['#3b82f6', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f97316'],
                    dataLabels: {
                        enabled: true,
                        dropShadow: {
                            enabled: false
                        }
                    },
                    stroke: {
                        show: true,
                        colors: '#ffffff',
                        width: 2
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center',
                        fontSize: '13px'
                    }
                }).render();
            }

            // 3. LİDERLİK TABLOSU (HORIZONTAL BAR CHART)
            if (data.top_users.series[0].data.length > 0) {
                new ApexCharts(document.querySelector("#chart-users"), {
                    series: data.top_users.series,
                    chart: {
                        type: 'bar',
                        height: 320,
                        fontFamily: fontFamily,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 6,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    colors: ['#eab308'], // Altın sarısı/Gold konsepti (Liderlik)
                    dataLabels: {
                        enabled: true,
                        offsetX: 25,
                        style: {
                            fontSize: '12px',
                            colors: ['#475569']
                        }
                    },
                    stroke: {
                        show: true,
                        width: 1,
                        colors: ['#fff']
                    },
                    xaxis: {
                        categories: data.top_users.categories,
                        labels: {
                            style: {
                                colors: '#64748b'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontWeight: 600,
                                colors: '#334155'
                            }
                        }
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 4
                    }
                }).render();
            }

            // 4. DEPARTMAN AKTİVİTELERİ (VERTICAL COLUMN CHART)
            if (data.departments.series[0].data.length > 0) {
                new ApexCharts(document.querySelector("#chart-departments"), {
                    series: data.departments.series,
                    chart: {
                        type: 'bar',
                        height: 320,
                        fontFamily: fontFamily,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: '45%'
                        }
                    },
                    colors: ['#3b82f6'],
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories: data.departments.categories,
                        labels: {
                            style: {
                                fontWeight: 600,
                                colors: '#64748b'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#64748b'
                            }
                        }
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 4
                    }
                }).render();
            }

            // 5. SON 30 GÜN TRENDİ (SMOOTH AREA CHART)
            if (data.trend.length > 0) {
                new ApexCharts(document.querySelector("#chart-trend"), {
                    series: [{
                        name: 'Yüklenen Belgeler',
                        data: data.trend
                    }],
                    chart: {
                        type: 'area',
                        height: 350,
                        fontFamily: fontFamily,
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        }
                    },
                    colors: ['#4f46e5'], // İndigo Rengi
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.45,
                            opacityTo: 0.05,
                            stops: [20, 100]
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    xaxis: {
                        type: 'datetime',
                        labels: {
                            format: 'dd MMM',
                            style: {
                                colors: '#64748b'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#64748b'
                            }
                        }
                    },
                    grid: {
                        borderColor: '#e2e8f0',
                        strokeDashArray: 5,
                        padding: {
                            top: 0,
                            right: 0,
                            bottom: 0,
                            left: 10
                        }
                    },
                    tooltip: {
                        x: {
                            format: 'dd MMM yyyy'
                        }
                    }
                }).render();
            }
        });
    </script>
@endpush
