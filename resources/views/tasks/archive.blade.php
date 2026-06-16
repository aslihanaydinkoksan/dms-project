@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20" style="flex-wrap: wrap; gap: 20px; animation: fadeIn 0.5s ease;">
        <div>
            <h1 class="page-title"
                style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                <div style="background: #f0fdf4; color: #166534; padding: 10px; border-radius: 12px; display:flex;">
                    <i data-lucide="archive" style="width: 28px; height: 28px;"></i>
                </div>
                {{ __('Süreçler Arşivi') }}
            </h1>
            <p class="text-muted" style="font-size: 0.95rem; margin-top: 5px;">
                {{ __('Başarıyla tamamlanıp yönetici onayıyla kapatılmış tüm süreçlerin geçmişini buradan inceleyebilirsiniz.') }}
            </p>
        </div>

        <div class="header-actions" style="display: flex; gap: 15px; align-items: center;">
            <form method="GET" action="{{ route('tasks.archive') }}" id="archiveFilterForm"
                style="margin: 0; display: flex; align-items: center; gap: 10px; background: var(--surface-color); padding: 8px 15px; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <label style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;"><i
                        data-lucide="filter" style="width:14px; vertical-align:middle;"></i> Filtrele:</label>
                <select name="template_id" onchange="document.getElementById('archiveFilterForm').submit();"
                    class="form-control form-control-sm" style="border-radius: 6px; cursor: pointer; min-width: 200px;">
                    <option value="">{{ __('Tüm Süreçler') }}</option>
                    @foreach ($templates as $tpl)
                        <option value="{{ $tpl->id }}" {{ request('template_id') == $tpl->id ? 'selected' : '' }}>📂
                            {{ $tpl->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="card glass-card"
        style="border-radius: 12px; border-top: 4px solid #166534; overflow: hidden; box-shadow: var(--card-shadow); animation: fadeIn 0.6s ease;">
        <div class="table-responsive">
            <table class="table modern-table" style="width: 100%; margin: 0;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 15px;">{{ __('Süreç No & Başlık') }}</th>
                        <th style="padding: 15px;">{{ __('Şablon') }}</th>
                        <th style="padding: 15px;">{{ __('Başlatan') }}</th>
                        <th style="padding: 15px;">{{ __('Kapanış Notu') }}</th>
                        <th style="padding: 15px;">{{ __('Kapanış Tarihi') }}</th>
                        <th class="text-right" style="padding: 15px;">{{ __('Ekli Kanıt') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr class="hover-row" style="border-bottom: 1px solid var(--border-color); cursor: pointer;"
                            onclick="window.location='{{ route('tasks.show', $task->id) }}'">
                            <td style="padding: 15px; vertical-align: middle;">
                                <div
                                    style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">
                                    TASK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</div>
                                <div style="font-weight: 600; color: var(--primary-color);">{{ $task->title }}</div>
                            </td>
                            <td style="padding: 15px; vertical-align: middle;">
                                <span class="badge"
                                    style="background: #eef2ff; color: var(--primary-color); padding: 5px 10px; border-radius: 6px;">{{ $task->template->name ?? '-' }}</span>
                            </td>
                            <td style="padding: 15px; vertical-align: middle; color: var(--text-color); font-weight: 500;">
                                {{ $task->creator->name ?? 'Sistem' }}
                            </td>
                            <td style="padding: 15px; vertical-align: middle;">
                                <div style="max-width: 250px; font-size: 0.85rem; color: var(--text-muted); font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="{{ $task->closure_note }}">
                                    {{ $task->closure_note ?? '-' }}
                                </div>
                            </td>
                            <td style="padding: 15px; vertical-align: middle; color: var(--text-muted); font-size: 0.9rem;">
                                {{ $task->updated_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="text-right" style="padding: 15px; vertical-align: middle;">
                                @if ($task->closure_document_path)
                                    {{-- event.stopPropagation() ile indirme butonuna basınca satır tıklanmasını (sayfa değişimini) engelliyoruz --}}
                                    <a href="{{ route('tasks.closure-document', $task->id) }}" target="_blank"
                                        onclick="event.stopPropagation();" class="btn btn-sm btn-outline-success"
                                        style="display: inline-flex; align-items: center; gap: 6px;"
                                        title="Kanıt Belgesini İndir/Gör">
                                        <i data-lucide="download" style="width: 16px;"></i> {{ __('Belge') }}
                                    </a>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><i data-lucide="minus"
                                            style="width: 14px;"></i></span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted" style="padding: 50px;">
                                <i data-lucide="archive-restore"
                                    style="width: 48px; height: 48px; opacity: 0.3; margin-bottom: 15px; display: block; margin: 0 auto;"></i>
                                {{ __('Henüz tamamlanmış (kapatılmış) bir iş bulunmuyor.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tasks->hasPages())
            <div style="padding: 15px 20px; border-top: 1px solid var(--border-color); background: #f8fafc;">
                {{ $tasks->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
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

        .hover-row:hover {
            background-color: #f8fafc;
            transition: 0.2s;
        }
    </style>
@endpush
