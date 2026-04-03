@extends('layouts.app')

@section('content')
    <div class="report-engine-container">

        <div class="report-header">
            <div>
                <h2>Otomatik Rapor Motoru</h2>
                <p>Sistem verilerini periyodik olarak Excel/PDF formatında iletin.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-back">Geri Dön</a>
        </div>

        <div class="report-body">
            <form action="{{ route('reports.store') }}" method="POST">
                @csrf

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label>RAPORUN ADI / BAŞLIĞI</label>
                        <input type="text" name="report_name" class="form-control"
                            placeholder="Örn: Aylık Bakım Analiz Raporu">
                    </div>
                    <div class="form-group">
                        <label>HANGİ MODÜL RAPORLANACAK?</label>
                        <select name="module" class="form-control">
                            <option value="">Rapor Seçiniz...</option>
                            <option value="documents">Tüm Belgeler Envanteri</option>
                            <option value="workflows">Bekleyen İş Akışları</option>
                            <option value="physical_archives">Fiziksel Zimmet Raporu</option>
                            <option value="audit_logs">Kullanıcı İşlem Logları</option>
                        </select>
                    </div>
                </div>

                <div class="form-row three-cols">
                    <div class="form-group">
                        <label>E-POSTA GÖNDERİM SIKLIĞI</label>
                        <select name="frequency" class="form-control">
                            <option value="">Gönderim Zamanı...</option>
                            <option value="daily">Her Gün (Saat 18:00)</option>
                            <option value="weekly">Her Hafta Başı (Pazartesi 08:00)</option>
                            <option value="monthly">Her Ayın 1'i (08:00)</option>
                        </select>
                        <small class="help-text">Raporun ne zaman oluşturulup mail atılacağını belirler.</small>
                    </div>

                    <div class="form-group">
                        <label>RAPOR VERİ KAPSAMI</label>
                        <select name="date_range" class="form-control">
                            <option value="">Zaman Aralığı Seçiniz...</option>
                            <option value="last_24_hours">Son 24 Saat</option>
                            <option value="last_7_days">Son 1 Hafta</option>
                            <option value="last_30_days">Son 1 Ay</option>
                            <option value="all_time">Tüm Zamanlar (Kümülatif)</option>
                        </select>
                        <small class="help-text">Tablodaki verilerin ne kadar geriye gideceğini belirler.</small>
                    </div>

                    <div class="form-group">
                        <label>DOSYA FORMATI</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="format" value="excel" checked> Excel (.xlsx)
                            </label>
                            <label class="radio-label" style="margin-left: 15px;">
                                <input type="radio" name="format" value="pdf"> PDF (.pdf)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width mt-10">
                    <label>E-POSTA ALICILARI</label>
                    <textarea name="recipients" class="form-control" rows="3"
                        placeholder="Her mailin arasına virgül koyun (örn: mudur@koksan.com, sef@koksan.com)"></textarea>
                </div>

                <button type="submit" class="btn-submit">Zamanlanmış Raporu Kaydet</button>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Orijinal Görsele Birebir Uygun CSS */
        .report-engine-container {
            max-width: 900px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .report-header {
            background: #0d6efd;
            /* Görseldeki parlak mavi */
            color: #ffffff;
            padding: 25px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-header h2 {
            margin: 0 0 5px 0;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .report-header p {
            margin: 0;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .btn-back {
            background: #ffffff;
            color: #0d6efd;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-back:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
        }

        .report-body {
            padding: 35px;
        }

        .form-row {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
        }

        .two-cols>.form-group {
            flex: 1;
        }

        .three-cols>.form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            /* Gri etiket rengi */
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.95rem;
            color: #334155;
            background: #fff;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #0d6efd;
            outline: none;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .help-text {
            display: block;
            margin-top: 6px;
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.4;
        }

        .radio-group {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: #334155;
            cursor: pointer;
            text-transform: none;
            font-weight: 500;
            letter-spacing: normal;
        }

        .radio-label input[type="radio"] {
            accent-color: #0d6efd;
            width: 16px;
            height: 16px;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .btn-submit {
            width: 100%;
            background: #0d6efd;
            color: #ffffff;
            border: none;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 15px;
        }

        .btn-submit:hover {
            background: #0b5ed7;
        }
    </style>
@endpush
