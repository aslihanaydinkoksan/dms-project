@extends('layouts.app')

@section('content')
    <div class="page-header mb-20 flex-between">
        <div>
            <h1 class="page-title">📭 {{ __('Tüm Bildirim Geçmişim') }}</h1>
            <p class="text-muted">{{ __('Sistemde size gönderilen tüm geçmiş ve yeni bildirimlerin listesi.') }}</p>
        </div>
        <div class="action-buttons" style="display: flex; gap: 10px;">
            <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm"
                    style="display: flex; align-items: center; gap: 5px;">
                    <i data-lucide="check-square" style="width: 16px;"></i> {{ __('Tümünü Okundu İşaretle') }}
                </button>
            </form>

            {{-- YENİ: TÜMÜNÜ SİL BUTONU --}}
            @if ($notifications->count() > 0)
                <form action="{{ route('notifications.clear-all') }}" method="POST" class="d-inline"
                    onsubmit="return confirm('{{ __('Tüm bildirim geçmişinizi kalıcı olarak silmek istediğinize emin misiniz?') }}');">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm"
                        style="display: flex; align-items: center; gap: 5px; background: var(--danger-color); color: white; border: none;">
                        <i data-lucide="trash-2" style="width: 16px;"></i> {{ __('Bildirim Geçmişini Temizle') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @include('partials.alerts')

    <div class="card glass-card p-20">
        <div class="notification-history-list">
            @forelse($notifications as $notification)
                {{-- Eskiden burası <a> etiketiydi, içine buton koyabilmek için <div>'e çevirdik --}}
                <div class="history-item flex-between {{ empty($notification->read_at) ? 'is-unread' : 'is-read' }}"
                    style="padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; transition: background 0.2s;">

                    {{-- Sol Taraf (Tıklanabilir Bildirim İçeriği) --}}
                    <a href="{{ $notification->data['url'] ?? '#' }}" class="item-left d-flex"
                        style="align-items: center; gap: 15px; text-decoration: none; color: inherit; flex: 1;">
                        <div class="icon-box"
                            style="font-size: 1.8rem; background: #f8fafc; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                            {{ $notification->data['icon'] ?? '🔔' }}
                        </div>
                        <div class="content-box">
                            <h4
                                style="margin: 0 0 5px 0; font-size: 1.05rem; color: {{ empty($notification->read_at) ? 'var(--primary-color)' : 'var(--text-color)' }};">
                                {{ __($notification->data['title'] ?? 'Sistem Bildirimi') }}

                                @if (empty($notification->read_at))
                                    <span class="badge badge-danger"
                                        style="font-size: 0.65rem; padding: 2px 6px; margin-left: 5px;">{{ __('YENİ') }}</span>
                                @endif
                            </h4>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                                @if (isset($notification->data['message_key']))
                                    {{ __($notification->data['message_key'], $notification->data['message_params'] ?? []) }}
                                @else
                                    {{ __($notification->data['message'] ?? '') }}
                                @endif
                            </p>
                        </div>
                    </a>

                    {{-- Sağ Taraf (Zaman ve Silme Butonu) --}}
                    <div class="item-right text-right"
                        style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                        <div class="time" style="font-size: 0.85rem; color: #94a3b8;">
                            🕒 {{ $notification->created_at->format('d.m.Y H:i') }}
                        </div>

                        {{-- YENİ: TEKİL SİLME BUTONU --}}
                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST"
                            class="d-inline" title="{{ __('Bu bildirimi sil') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                style="padding: 2px 8px; border: none; background: transparent; color: var(--danger-color);">
                                <i data-lucide="x-circle" style="width: 18px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center p-30 text-muted">
                    <div style="font-size: 3rem; margin-bottom: 10px;">📭</div>
                    <p>{{ __('Sistemde size ait hiçbir bildirim bulunmuyor.') }}</p>
                </div>
            @endforelse
        </div>

        {{-- UI FACİASI ÇÖZÜLDÜ: Sadece links() kullandık --}}
        <div class="mt-20 custom-pagination-wrapper">
            {{ $notifications->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .history-item:hover {
            background-color: #f8fafc !important;
        }

        .history-item.is-unread {
            background-color: #f0fdf4;
            border-left: 4px solid var(--success-color);
        }

        .history-item.is-read {
            border-left: 4px solid transparent;
        }

        .history-item:last-child {
            border-bottom: none !important;
        }

        /* --- KUSURSUZ SAYFALAMA (PAGINATION) TASARIMI --- */
        .custom-pagination-wrapper {
            margin-top: 30px;
            width: 100%;
        }

        /* 1. KURAL: Laravel'in ürettiği çirkin Mobil (Previous/Next) kutusunu TAMAMEN YOK ET */
        .custom-pagination-wrapper nav>div:first-of-type {
            display: none !important;
        }

        /* 2. KURAL: Numaralı Masaüstü görünümünü zorla göster ve alt alta ortala */
        .custom-pagination-wrapper nav>div:last-of-type {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100%;
        }

        /* "Showing 1 to 15 of 29 results" yazısını merkeze alıp şıklaştırıyoruz */
        .custom-pagination-wrapper nav>div:last-of-type>div:first-child {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-align: center;
            width: 100%;
        }

        .custom-pagination-wrapper nav>div:last-of-type>div:first-child p {
            margin: 0;
        }

        /* Kutu ve Flex ayarları */
        .custom-pagination-wrapper .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            gap: 8px;
            margin: 0;
            justify-content: center;
        }

        /* Butonların genel tasarımı */
        .custom-pagination-wrapper .page-item .page-link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 10px;
            color: var(--primary-color);
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        /* Hover (Üzerine gelince) */
        .custom-pagination-wrapper .page-item .page-link:hover:not(.disabled) {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        /* Aktif sayfa (Şu an bulunulan sayfa) */
        .custom-pagination-wrapper .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        /* Pasif butonlar (Örn: İlk sayfadayken "Geri" butonu) */
        .custom-pagination-wrapper .page-item.disabled .page-link {
            color: #94a3b8;
            pointer-events: none;
            background-color: #f1f5f9;
            border-color: #e2e8f0;
            box-shadow: none;
        }

        /* Eğer Laravel içinden SVG ikonları gelirse boyutlarını küçültüyoruz */
        .custom-pagination-wrapper .page-link svg {
            width: 18px;
            height: 18px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
@endpush
