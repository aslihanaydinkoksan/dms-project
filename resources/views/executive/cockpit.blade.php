@extends('layouts.app')

@section('content')
    <style>
        /* =========================================
               EXECUTIVE COCKPIT - VİP GLASSMORPHISM UI
               ========================================= */

        /* Sayfa Kapsayıcısı */
        .executive-cockpit {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            padding: 3rem;
            color: #f8fafc;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            box-sizing: border-box;
        }

        /* Başlık Alanı */
        .cockpit-header {
            border-bottom: 2px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 1.5rem;
            margin-bottom: 3rem;
        }

        .cockpit-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin: 0;
            letter-spacing: 0.5px;
            color: #e2e8f0;
        }

        .cockpit-title strong {
            font-weight: 700;
            color: #60a5fa;
            /* Kurumsal Mavi Vurgu */
        }

        .cockpit-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            font-weight: 400;
        }

        /* Kart Izgarası (Grid) */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        /* Glassmorphism Kart Tasarımı */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease, background 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.06);
        }

        /* Kritik Durum Kartı (Kırmızı) */
        .danger-card {
            border-color: rgba(248, 113, 113, 0.3);
            background: rgba(248, 113, 113, 0.05);
        }

        .danger-pulse-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: #ef4444;
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% {
                opacity: 0.4;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.4;
            }
        }

        /* Kart İçerik Yazıları (Yaşlı/Yönetici Dostu Büyük Puntolar) */
        .card-label {
            display: block;
            color: #cbd5e1;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .card-value {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        /* Metrik Renkleri */
        .text-green {
            color: #4ade80;
        }

        .text-yellow {
            color: #fbbf24;
        }

        .text-blue {
            color: #60a5fa;
        }

        .text-red {
            color: #f87171;
        }

        /* Tablo Konteyneri */
        .table-glass-container {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow-x: auto;
        }

        .table-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-top: 0;
            margin-bottom: 2rem;
            border-left: 5px solid #60a5fa;
            padding-left: 15px;
        }

        /* VİP Tablo Tasarımı */
        .vip-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 1.1rem;
            /* Okunabilirliği yüksek punto */
        }

        .vip-table th {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            font-weight: 700;
            padding: 1.2rem 1.5rem;
            letter-spacing: 0.5px;
        }

        .vip-table th:first-child {
            border-top-left-radius: 0.75rem;
            border-bottom-left-radius: 0.75rem;
        }

        .vip-table th:last-child {
            border-top-right-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }

        .vip-table td {
            padding: 1.2rem 1.5rem;
            color: #94a3b8;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
        }

        .vip-table tr:last-child td {
            border-bottom: none;
        }

        .vip-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: #f8fafc;
        }

        /* Kullanıcı Avatarı ve Rozetler */
        .user-flex {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #f1f5f9;
            font-weight: 500;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
        }

        .action-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 600;
            background: rgba(51, 65, 85, 0.8);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .empty-row {
            text-align: center;
            padding: 3rem !important;
            color: #64748b !important;
            font-style: italic;
        }
    </style>

    <div class="executive-cockpit">

        <div class="cockpit-header">
            <h1 class="cockpit-title">Yönetim Kurulu <strong>Kokpiti</strong></h1>
            <p class="cockpit-subtitle">Merkezi Yönetim Sistemi (KYS) Bütünleşik Ekranı</p>
        </div>

        <div class="kpi-grid">

            <div class="glass-card">
                <span class="card-label">Aktif Süreçler</span>
                <span class="card-value text-green">{{ $stats['active_tasks'] ?? 0 }}</span>
            </div>

            <div class="glass-card">
                <span class="card-label">Onay Bekleyenler</span>
                <span class="card-value text-yellow">{{ $stats['pending_closure'] ?? 0 }}</span>
            </div>

            <div class="glass-card">
                <span class="card-label">Yayındaki Belgeler</span>
                <span class="card-value text-blue">{{ $stats['published_docs'] ?? 0 }}</span>
            </div>

            <div class="glass-card danger-card">
                <div class="danger-pulse-line"></div>
                <span class="card-label">Kritik Belgeler</span>
                <span class="card-value text-red">{{ $stats['expiring_docs'] ?? 0 }}</span>
            </div>

        </div>

        <div class="table-glass-container">
            <h2 class="table-title">Son Sistem Hareketleri</h2>

            <table class="vip-table">
                <thead>
                    <tr>
                        <th>Zaman</th>
                        <th>İşlemi Yapan Kullanıcı</th>
                        <th>Gerçekleşen Aksiyon</th>
                        <th>Etkilenen Kayıt (Modül / No)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['recent_activities'] ?? [] as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y - H:i') }}</td>
                            <td>
                                <div class="user-flex">
                                    <div class="avatar-circle">
                                        {{ mb_substr($log->user->name ?? '?', 0, 2) }}
                                    </div>
                                    {{ $log->user->name ?? 'Sistem Kullanıcısı' }}
                                </div>
                            </td>
                            <td>
                                <span class="action-badge">{{ ucfirst($log->event) }}</span>
                            </td>
                            <td style="font-family: monospace; font-size: 1.15rem; color: #cbd5e1;">
                                {{ class_basename($log->auditable_type) }} (#{{ $log->auditable_id }})
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">Şu an için gösterilecek bir sistem hareketi bulunmuyor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
