@extends('layouts.app')

@section('content')
    <div class="page-header mb-20 flex-between">
        <div>
            <h1 class="page-title">📭 {{ __('Tüm Bildirim Geçmişim') }}</h1>
            <p class="text-muted">{{ __('Sistemde size gönderilen tüm geçmiş ve yeni bildirimlerin listesi.') }}</p>
        </div>
        <div class="action-buttons">
            <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">✔️ {{ __('Tümünü Okundu İşaretle') }}</button>
            </form>
        </div>
    </div>

    <div class="card glass-card p-20">
        <div class="notification-history-list">
            @forelse($notifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}"
                    class="history-item flex-between {{ empty($notification->read_at) ? 'is-unread' : 'is-read' }}"
                    style="text-decoration: none; color: inherit; padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; transition: background 0.2s;">

                    <div class="item-left d-flex" style="align-items: center; gap: 15px;">
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
                                {{-- Yeni mimari varsa çevirerek bas, yoksa (eski veriyse) yedeği bas --}}
                                @if (isset($notification->data['message_key']))
                                    {{ __($notification->data['message_key'], $notification->data['message_params'] ?? []) }}
                                @else
                                    {{ __($notification->data['message'] ?? '') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="item-right text-right">
                        <div class="time" style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px;">
                            🕒 {{ $notification->created_at->format('d.m.Y H:i') }}
                        </div>
                        <div class="diff" style="font-size: 0.75rem; color: #cbd5e1;">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center p-30 text-muted">
                    <div style="font-size: 3rem; margin-bottom: 10px;">📭</div>
                    <p>{{ __('Sistemde size ait hiçbir bildirim bulunmuyor.') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-20">
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
            /* Hafif yeşil arka plan */
            border-left: 4px solid var(--success-color);
        }

        .history-item.is-read {
            border-left: 4px solid transparent;
        }

        .history-item:last-child {
            border-bottom: none !important;
        }
    </style>
@endpush
