@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20" style="flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="page-title"
                style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                <div
                    style="background: #eef2ff; color: var(--accent-color); padding: 10px; border-radius: 12px; display:flex;">
                    <i data-lucide="git-merge" style="width: 28px; height: 28px;"></i>
                </div>
                {{ __('Süreç Şablonları (BPM)') }}
            </h1>
            <p class="text-muted" style="font-size: 0.95rem; margin-top: 5px;">
                {{ __('Departmanların iş süreçlerini, form yapılarını ve Kanban aşamalarını buradan yönetebilirsiniz.') }}
            </p>
        </div>

        <div class="header-actions">
            <a href="{{ route('process-templates.create') }}" class="btn btn-primary"
                style="display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
                <i data-lucide="plus" style="width: 18px;"></i> {{ __('Yeni Şablon Tasarla') }}
            </a>
        </div>
    </div>

    @include('partials.alerts')

    <div class="card glass-card"
        style="border-radius: 12px; border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); overflow: hidden;">
        <div class="table-responsive">
            <table class="table modern-table" style="width: 100%; margin: 0;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 15px;">{{ __('Süreç Adı') }}</th>
                        <th style="padding: 15px;">{{ __('Departman') }}</th>
                        <th class="text-center" style="padding: 15px;">{{ __('Dinamik Alan') }}</th>
                        <th class="text-center" style="padding: 15px;">{{ __('Aşama (Sütun)') }}</th>
                        <th class="text-right" style="padding: 15px;">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr class="hover-row" style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 15px; font-weight: 600; color: var(--text-color);">
                                {{ $template->name }}
                            </td>
                            <td style="padding: 15px; color: var(--text-muted);">
                                <span class="badge"
                                    style="background: #eef2ff; color: var(--primary-color); padding: 5px 10px; border-radius: 6px;">
                                    {{ $template->department->name ?? 'Genel' }}
                                </span>
                            </td>
                            <td class="text-center" style="padding: 15px;">
                                <span class="badge"
                                    style="background: #f0fdf4; color: #166534; padding: 5px 10px; border-radius: 6px;">
                                    {{ is_array($template->fields) ? count($template->fields) : 0 }} Alan
                                </span>
                            </td>
                            <td class="text-center" style="padding: 15px;">
                                <span class="badge"
                                    style="background: #fffbeb; color: #b45309; padding: 5px 10px; border-radius: 6px;">
                                    {{ $template->stages()->count() }} Aşama
                                </span>
                            </td>
                            <td class="text-right" style="padding: 15px;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="{{ route('process-templates.edit', $template->id) }}"
                                        class="btn btn-sm btn-outline-primary action-btn"
                                        title="{{ __('Düzenle & Aşama Ekle') }}">
                                        <i data-lucide="edit-2" style="width: 16px;"></i>
                                    </a>
                                    <form action="{{ route('process-templates.destroy', $template->id) }}" method="POST"
                                        onsubmit="return confirm('Bu şablonu silmek istediğinize emin misiniz? (Aktif işler varsa silinemez)');"
                                        style="margin: 0;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger action-btn"
                                            title="{{ __('Sil') }}">
                                            <i data-lucide="trash-2" style="width: 16px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 40px;">
                                <i data-lucide="inbox"
                                    style="width: 48px; height: 48px; opacity: 0.3; margin-bottom: 15px; display: block; margin: 0 auto;"></i>
                                {{ __('Henüz hiçbir süreç şablonu tanımlanmamış.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
    <style>
        .hover-row:hover {
            background-color: #f8fafc;
            transition: 0.2s;
        }
    </style>
@endpush
