@extends('layouts.app')

@section('content')
    <div class="page-header mb-20">
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">
            📊 {{ __('Özel Rapor ve Gösterge Paneli') }}
        </h1>
        <p class="text-muted">
            {{ __('Seçtiğiniz kriterlere göre birden fazla grafik oluşturabilir, kendi gösterge panelinizi tasarlayabilirsiniz.') }}
        </p>
    </div>

    {{-- GELİŞMİŞ RAPOR OLUŞTURUCU ARAÇ ÇUBUĞU --}}
    <div class="card glass-card mb-30" style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">
        <form id="widgetBuilderForm"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end;">

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">1. Veri
                    Kaynağı</label>
                <select id="moduleSelect" class="form-control" style="border-radius: 8px;" required>
                    <option value="">-- Modül Seçin --</option>
                    @foreach ($modulesConfig as $key => $module)
                        <option value="{{ $key }}">{{ $module['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">2. Gruplama</label>
                <select id="groupSelect" class="form-control" style="border-radius: 8px;" required disabled>
                    <option value="">-- Önce Modül Seçin --</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">3. Grafik
                    Türü</label>
                <select id="chartType" class="form-control" style="border-radius: 8px;" required>
                    <option value="bar">Sütun Grafik (Bar)</option>
                    <option value="pie">Pasta Grafik (Pie)</option>
                    <option value="donut">Halka Grafik (Donut)</option>
                    <option value="line">Çizgi Grafik (Line)</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">4. Tarih (Başlangıç
                    - Bitiş)</label>
                <div style="display: flex; gap: 5px;">
                    <input type="date" id="dateStart" class="form-control" style="border-radius: 8px;">
                    <input type="date" id="dateEnd" class="form-control" style="border-radius: 8px;">
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; border-radius: 8px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
                    <i data-lucide="plus-circle" style="width: 16px;"></i> Panele Ekle
                </button>
            </div>
        </form>
    </div>

    {{-- DIŞA AKTAR BUTONLARI --}}
    <div id="exportActionArea"
        style="display: none; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 1.2rem; color: var(--text-dark);">{{ __('Benim Gösterge Panelim') }}</h3>
        <div style="display: flex; gap: 10px;">
            <button onclick="DashboardEngine.clearAll()" class="btn btn-sm btn-outline-danger" style="border-radius: 6px;">
                <i data-lucide="trash" style="width: 16px;"></i> Paneli Temizle
            </button>
            <button onclick="downloadPDF()" class="btn btn-sm btn-danger" style="border-radius: 6px;">
                <i data-lucide="file-text" style="width: 16px;"></i> Paneli PDF İndir
            </button>
        </div>
    </div>

    {{-- DİNAMİK WIDGET (GRAFİK) IZGARASI (Yazdırılacak Alan) --}}
    <div id="printableReportArea" style="background: #f8fafc; padding: 15px; border-radius: 12px;">
        <div id="dynamicChartsGrid" style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px;">
            {{-- İlk girişte boş durum mesajı --}}
            <div id="emptyStateMsg"
                style="grid-column: span 12; text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 2px dashed #cbd5e1;">
                <i data-lucide="layout-dashboard"
                    style="width: 48px; height: 48px; opacity: 0.4; margin-bottom: 15px; color: var(--primary-color);"></i>
                <h4 style="color: var(--text-dark); margin-bottom: 5px;">{{ __('Paneliniz Şu An Boş') }}</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    {{ __('Yukarıdaki araç çubuğunu kullanarak analiz etmek istediğiniz verileri panelinize eklemeye başlayın.') }}
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        /* Widget Kartı Tasarımı */
        .widget-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            position: relative;
            border: 1px solid var(--border-color);
        }

        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .widget-title {
            font-size: 1rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }

        .widget-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .btn-remove-widget {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .btn-remove-widget:hover {
            background: #fee2e2;
        }

        @media (max-width: 768px) {
            .widget-card {
                grid-column: span 12 !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // Tarihleri varsayılan olarak son 30 gün yap
            const today = new Date();
            const lastMonth = new Date(today);
            lastMonth.setDate(lastMonth.getDate() - 30);

            document.getElementById('dateStart').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('dateEnd').value = today.toISOString().split('T')[0];

            // Form Bağımlılıkları (Cascading Dropdown)
            const modulesConfig = @json($modulesConfig);
            const moduleSelect = document.getElementById('moduleSelect');
            const groupSelect = document.getElementById('groupSelect');

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

            // Form Gönderildiğinde Widget Ekle
            document.getElementById('widgetBuilderForm').addEventListener('submit', function(e) {
                e.preventDefault();
                DashboardEngine.addWidgetConfig();
            });
        });

        // ==========================================
        // DİNAMİK DASHBOARD MOTORU (OOP MİMARİSİ)
        // ==========================================
        const DashboardEngine = {
            widgets: {}, // Aktif widget verilerini tutar
            chartInstances: {}, // ApexChart instance'larını tutar

            // 1. Formdan Veriyi Al ve Widget Taslağı Oluştur
            addWidgetConfig: function() {
                const module = document.getElementById('moduleSelect').value;
                const group = document.getElementById('groupSelect').value;
                const chartType = document.getElementById('chartType').value;
                const start = document.getElementById('dateStart').value;
                const end = document.getElementById('dateEnd').value;

                const moduleName = document.getElementById('moduleSelect').options[document.getElementById(
                    'moduleSelect').selectedIndex].text;
                const groupName = document.getElementById('groupSelect').options[document.getElementById(
                    'groupSelect').selectedIndex].text;

                // Benzersiz ID (Timestamp)
                const widgetId = 'widget_' + Date.now();

                // Ekranda kaplayacağı alan (Pie küçük, Bar büyük alan kaplasın)
                const colSpan = (chartType === 'pie' || chartType === 'donut') ? 4 : 8;

                // Boş durumu gizle, action barı göster
                document.getElementById('emptyStateMsg').style.display = 'none';
                document.getElementById('exportActionArea').style.display = 'flex';

                // Grid'e Placeholder DOM Ekle
                this.renderWidgetPlaceholder(widgetId, `${moduleName} (${groupName})`, chartType, start, end,
                    colSpan);

                // Backend'e İstek At
                this.fetchDataAndRender(widgetId, module, group, chartType, start, end);
            },

            // 2. DOM'a Boş Kart (Yükleniyor) Ekle
            renderWidgetPlaceholder: function(id, title, type, start, end, colSpan) {
                const grid = document.getElementById('dynamicChartsGrid');

                const dateText = start && end ?
                    `${start.split('-').reverse().join('.')} - ${end.split('-').reverse().join('.')}` :
                    'Tüm Zamanlar';

                const html = `
                    <div id="container_${id}" class="widget-card" style="grid-column: span ${colSpan};">
                        <div class="widget-header">
                            <div>
                                <h4 class="widget-title">${title}</h4>
                                <span class="widget-meta"><i data-lucide="calendar" style="width:12px;"></i> ${dateText}</span>
                            </div>
                            <button class="btn-remove-widget" onclick="DashboardEngine.removeWidget('${id}')" title="Kaldır">
                                <i data-lucide="x" style="width: 18px;"></i>
                            </button>
                        </div>
                        <div id="chart_${id}" style="min-height: 300px; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="loader-2" class="spin" style="width: 24px; color: var(--primary-color);"></i>
                        </div>
                    </div>
                `;

                // Grid'in başına ekle (En yeni eklenen en üstte görünsün)
                grid.insertAdjacentHTML('afterbegin', html);
                lucide.createIcons();
            },

            // 3. API İsteği ve ApexCharts Render
            fetchDataAndRender: function(id, module, group, chartType, start, end) {
                fetch('{{ route('analytics.generate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
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

                        document.getElementById(`chart_${id}`).innerHTML = ''; // Yükleniyor ikonunu temizle
                        this.drawChart(id, data, chartType);
                    })
                    .catch(err => {
                        document.getElementById(`chart_${id}`).innerHTML =
                            `<div style="color:var(--danger-color); font-weight:500;"><i data-lucide="alert-triangle"></i> Hata: ${err.message}</div>`;
                        lucide.createIcons();
                    });
            },

            // 4. ApexCharts Konfigürasyonu
            drawChart: function(id, apiData, chartType) {
                const options = {
                    chart: {
                        type: chartType,
                        height: 300,
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'inherit',
                        background: '#fff'
                    },
                    colors: ['#ce1126', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#64748b'],
                    dataLabels: {
                        enabled: true
                    }
                };

                // Eğer veri tamamen boşsa
                if (apiData.data.length === 0) {
                    document.getElementById(`chart_${id}`).innerHTML =
                        `<div style="color:var(--text-muted); font-size:0.9rem;">Bu kriterlere uygun veri bulunamadı.</div>`;
                    return;
                }

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
                    if (chartType === 'bar') {
                        options.plotOptions = {
                            bar: {
                                borderRadius: 4,
                                horizontal: false,
                                distributed: true
                            }
                        };
                    }
                }

                const chart = new ApexCharts(document.querySelector(`#chart_${id}`), options);
                chart.render();

                this.chartInstances[id] = chart; // Belleğe al
            },

            // 5. Widget Silme
            removeWidget: function(id) {
                if (this.chartInstances[id]) {
                    this.chartInstances[id].destroy(); // Bellek sızıntısını önle
                    delete this.chartInstances[id];
                }

                const container = document.getElementById(`container_${id}`);
                if (container) container.remove();

                // Eğer ekranda widget kalmadıysa boş durumu göster
                if (Object.keys(this.chartInstances).length === 0) {
                    document.getElementById('emptyStateMsg').style.display = 'block';
                    document.getElementById('exportActionArea').style.display = 'none';
                }
            },

            // 6. Ekranı Komple Temizle
            clearAll: function() {
                if (!confirm("Tüm paneli temizlemek istediğinize emin misiniz?")) return;

                for (const id in this.chartInstances) {
                    this.chartInstances[id].destroy();
                }
                this.chartInstances = {};

                // Placeholder hariç içindekileri sil
                const grid = document.getElementById('dynamicChartsGrid');
                grid.innerHTML = `
                    <div id="emptyStateMsg" style="grid-column: span 12; text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 2px dashed #cbd5e1;">
                        <i data-lucide="layout-dashboard" style="width: 48px; height: 48px; opacity: 0.4; margin-bottom: 15px; color: var(--primary-color);"></i>
                        <h4 style="color: var(--text-dark); margin-bottom: 5px;">Paneliniz Şu An Boş</h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Yukarıdaki araç çubuğunu kullanarak analiz etmek istediğiniz verileri panelinize eklemeye başlayın.</p>
                    </div>
                `;
                document.getElementById('exportActionArea').style.display = 'none';
                lucide.createIcons();
            }
        };

        // ==========================================
        // PDF DIŞA AKTARMA MOTORU
        // ==========================================
        function downloadPDF() {
            const element = document.getElementById('printableReportArea');
            const dateStr = new Date().toISOString().slice(0, 10);
            const opt = {
                margin: 0.5,
                filename: `KOKSAN_DMS_Dashboard_${dateStr}.pdf`,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#f8fafc'
                },
                jsPDF: {
                    unit: 'in',
                    format: 'letter',
                    orientation: 'landscape'
                }
            };

            // PDF alırken gridin bozulmaması için layout trick'i
            html2pdf().set(opt).from(element).save();
        }
    </script>
@endpush
