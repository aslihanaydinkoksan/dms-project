@extends('layouts.app')

@section('content')
    <style>
        /* =========================================
           EXECUTIVE COCKPIT - GLASSMORPHISM STYLES
           ========================================= */
        .cockpit-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 2rem;
            color: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .cockpit-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .cockpit-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin: 0;
            letter-spacing: 1px;
        }

        .cockpit-title strong {
            font-weight: 700;
            color: #60a5fa;
            /* Mavi Vurgu */
        }

        .cockpit-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        /* Kart Izgarası */
        .glass-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        /* Glassmorphism Kart Temeli */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, background 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }

        /* Kırmızı Alarm Kartı İçin Animasyon */
        .glass-card.danger-card {
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.05);
        }

        .danger-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #ef4444;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.5;
            }
        }

        .card-label {
            display: block;
            color: #cbd5e1;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.75rem;
        }

        .card-value {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .text-green {
            color: #4ade80;
        }

        .text-blue {
            color: #60a5fa;
        }

        .text-purple {
            color: #c084fc;
        }

        .text-red {
            color: #ef4444;
        }

        /* Tablo Alanı */
        .table-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow-x: auto;
        }

        .table-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-top: 0;
            margin-bottom: 1.5rem;
        }

        .glass-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }

        .glass-table th {
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .glass-table th:first-child {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }

        .glass-table th:last-child {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        .glass-table td {
            padding: 1rem 1.5rem;
            color: #cbd5e1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-table tr:last-child td {
            border-bottom: none;
        }

        .glass-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Kullanıcı Avatarı & Rozetler */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(51, 65, 85, 0.8);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
    </style>

    <div class="cockpit-wrapper">

        <div class="cockpit-header">
            <h1 class="cockpit-title">Yönetim Kurulu <strong>Kokpiti</strong></h1>
            <p class="cockpit-subtitle">Kurumsal Yönetim Sistemi (KYS) Gerçek Zamanlı Özet Ekranı</p>
        </div>

        <div class="glass-grid">

            <div class="glass-card">
                <span class="card-label">Aktif Süreçler</span>
                <span class="card-value text-green">{{ $stats['active_tasks'] ?? 0 }}</span>
            </div>

            <div class="glass-card">
                <span class="card-label">Yayındaki Belgeler</span>
                <span class="card-value text-blue">{{ $stats['published_docs'] ?? 0 }}</span>
            </div>

            <div class="glass-card">
                <span class="card-label">Bu Hafta Yüklenenler</span>
                <span class="card-value text-purple">{{ $stats['new_docs_this_week'] ?? 0 }}</span>
            </div>

            <div class="glass-card danger-card">
                <div class="danger-line"></div>
                <span class="card-label">Süresi Dolan Kritik Belge</span>
                <span class="card-value text-red">{{ $stats['expiring_docs'] ?? 0 }}</span>
            </div>

        </div>

        <div class="table-container">
            <h2 class="table-title">Son Sistem Hareketleri</h2>
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>Zaman</th>
                        <th>Kullanıcı</th>
                        <th>Aksiyon</th>
                        <th>Modül/Hedef</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['recent_activities'] ?? [] as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar">{{ mb_substr($log->user->name ?? 'U', 0, 2) }}</div>
                                    {{ $log->user->name ?? 'Bilinmeyen Kullanıcı' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge">{{ $log->action_type }}</span>
                            </td>
                            <td>{{ $log->target_model }} (#{{ $log->target_id }})</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">Henüz sistem hareketi bulunmamaktadır.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
