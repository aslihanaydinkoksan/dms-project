@extends('layouts.app')

@section('content')
    <div class="page-header mb-20">
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">
            📊 {{ __('Self-Servis Analitik Motoru') }}
        </h1>
        <p class="text-muted">Tüm modüllerdeki verilerinizi istediğiniz formatta, anında görselleştirin.</p>
    </div>

    {{-- GELİŞMİŞ RAPOR OLUŞTURUCU (GLASSMORPHISM) --}}
    <div class="card glass-card mb-30" style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">
        <form id="reportBuilderForm"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">Modül / Veri
                    Kaynağı</label>
                <select id="moduleSelect" class="form-control" style="border-radius: 8px;">
                    <option value="">-- Modül Seçin --</option>
                    @foreach ($modulesConfig as $key => $module)
                        <option value="{{ $key }}">{{ $module['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">Gruplama
                    Kriteri</label>
                <select id="groupSelect" class="form-control" style="border-radius: 8px;" disabled>
                    <option value="">-- Önce Modül Seçin --</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">Grafik Türü</label>
                <select id="chartType" class="form-control" style="border-radius: 8px;">
                    <option value="bar">Sütun Grafik (Bar)</option>
                    <option value="pie">Pasta Grafik (Pie)</option>
                    <option value="donut">Halka Grafik (Donut)</option>
                    <option value="line">Çizgi Grafik (Line)</option>
                    <option value="area">Alan Grafik (Area)</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">Tarih
                    Aralığı</label>
                <div style="display: flex; gap: 5px;">
                    <input type="date" id="dateStart" class="form-control" style="border-radius: 8px;">
                    <input type="date" id="dateEnd" class="form-control" style="border-radius: 8px;">
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; border-radius: 8px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
                    <i data-lucide="play" style="width: 16px;"></i> Raporu Çiz
                </button>
            </div>
        </form>
    </div>

    {{-- EVRENSEL GRAFİK ALANI --}}
    <div class="card glass-card" style="border-radius: 12px; padding: 25px; min-height: 450px;">
        <div id="universalChartContainer"
            style="min-height: 400px; display: flex; align-items: center; justify-content: center;">
            <div class="text-muted text-center" id="emptyStateMsg">
                <i data-lucide="pie-chart" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 15px;"></i>
                <p>Verilerinizi görselleştirmek için yukarıdan kriterleri belirleyip "Raporu Çiz" butonuna tıklayın.</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* Yükleniyor ikonu için küçük bir dönme animasyonu */
        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const modulesConfig = @json($modulesConfig);
            const moduleSelect = document.getElementById('moduleSelect');
            const groupSelect = document.getElementById('groupSelect');
            const form = document.getElementById('reportBuilderForm');
            let currentChart = null;

            // Kademeli (Cascading) Dropdown Mantığı
            moduleSelect.addEventListener('change', function() {
                const selectedModule = this.value;
                groupSelect.innerHTML = '<option value="">-- Gruplama Seçin --</option>';

                if (selectedModule && modulesConfig[selectedModule]) {
                    const groupings = modulesConfig[selectedModule].groupings;
                    for (const [groupKey, groupData] of Object.entries(groupings)) {
                        if (!groupData.label || !groupData.col) continue;
                        groupSelect.innerHTML += `<option value="${groupKey}">${groupData.label}</option>`;
                    }
                    groupSelect.disabled = false;
                } else {
                    groupSelect.disabled = true;
                }
            });

            // Form Submit -> Fetch API
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const module = moduleSelect.value;
                const group = groupSelect.value;
                const type = document.getElementById('chartType').value;
                const start = document.getElementById('dateStart').value;
                const end = document.getElementById('dateEnd').value;

                if (!module || !group) {
                    alert('Lütfen Modül ve Gruplama kriteri seçin.');
                    return;
                }

                // HATA ÇÖZÜMÜ: Element silinmemişse (null değilse) gizle
                const emptyMsg = document.getElementById('emptyStateMsg');
                if (emptyMsg) {
                    emptyMsg.style.display = 'none';
                }

                // Ekranda halihazırda çizilmiş bir grafik varsa, hafıza sızıntısını önlemek için önce onu yok et (destroy)
                if (currentChart) {
                    currentChart.destroy();
                    currentChart = null;
                }

                // Grafik alanına şık bir yükleniyor durumu bas
                document.getElementById('universalChartContainer').innerHTML =
                    '<div style="margin:auto; font-weight:600; color:var(--text-muted); display:flex; align-items:center; gap:8px;"><i data-lucide="loader-2" style="animation: spin 1s linear infinite;"></i> Veriler Analiz Ediliyor...</div>';
                lucide.createIcons();

                fetch('{{ route('analytics.generate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            module: module,
                            group: group,
                            date_start: start,
                            date_end: end
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                        renderApexChart(data, type);
                    })
                    .catch(err => {
                        document.getElementById('universalChartContainer').innerHTML =
                            `<div style="color:var(--danger-color); margin:auto; font-weight:600; display:flex; align-items:center; gap:8px;"><i data-lucide="alert-triangle"></i> Hata: ${err.message}</div>`;
                        lucide.createIcons();
                    });
            });

            // ApexCharts Çizim Motoru
            function renderApexChart(apiData, chartType) {
                document.getElementById('universalChartContainer').innerHTML = ''; // Yükleniyor yazısını temizle

                const options = {
                    chart: {
                        type: chartType,
                        height: 400,
                        toolbar: {
                            show: true
                        },
                        fontFamily: 'inherit'
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'],
                    dataLabels: {
                        enabled: true
                    }
                };

                // ApexCharts; Pie/Donut için ayrı, Bar/Line için ayrı veri yapısı ister
                if (chartType === 'pie' || chartType === 'donut') {
                    options.series = apiData.data;
                    options.labels = apiData.labels;
                } else {
                    options.series = [{
                        name: 'Kayıt Sayısı',
                        data: apiData.data
                    }];
                    options.xaxis = {
                        categories: apiData.labels
                    };
                }

                currentChart = new ApexCharts(document.querySelector("#universalChartContainer"), options);
                currentChart.render();
            }
        });
    </script>
@endpush
