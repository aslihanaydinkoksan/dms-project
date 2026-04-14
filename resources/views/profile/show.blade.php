@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-30"
        style="background: var(--surface-color); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold;">
                {{ mb_substr($targetUser->name, 0, 1) }}
            </div>
            <div>
                <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.6rem; color: var(--primary-color);">
                    {{ $targetUser->name }}
                </h1>
                <p class="text-muted" style="margin: 0; display: flex; align-items: center; gap: 15px;">
                    <span><i data-lucide="mail" style="width: 16px; vertical-align: text-bottom;"></i>
                        {{ $targetUser->email }}</span>
                    <span><i data-lucide="briefcase" style="width: 16px; vertical-align: text-bottom;"></i>
                        {{ $targetUser->department->name ?? __('Departman Atanmadı') }}</span>
                </p>
            </div>
        </div>
        <div>
            @if (auth()->id() === $targetUser->id)
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary"><i data-lucide="settings"
                        style="width: 18px;"></i> {{ __('Ayarlarımı Düzenle') }}</a>
            @else
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i data-lucide="arrow-left"
                        style="width: 18px;"></i> {{ __('Listeye Dön') }}</a>
            @endif
        </div>
    </div>

    <h2
        style="font-size: 1.3rem; margin-bottom: 20px; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="award" style="color: var(--warning-color);"></i> {{ __('Belgeler ve Performans Özeti') }}
    </h2>

    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="card glass-card" style="padding: 25px; border-top: 4px solid var(--primary-color); text-align: center;">
            <i data-lucide="layers"
                style="width: 40px; height: 40px; color: var(--primary-color); margin-bottom: 15px; opacity: 0.8;"></i>
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--text-color); line-height: 1;">{{ $totalDocs }}
            </div>
            <div
                style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-top: 8px; text-transform: uppercase;">
                {{ __('Toplam Yüklenen Belge') }}</div>
        </div>

        <div class="card glass-card" style="padding: 25px; border-top: 4px solid var(--success-color); text-align: center;">
            <i data-lucide="check-circle"
                style="width: 40px; height: 40px; color: var(--success-color); margin-bottom: 15px; opacity: 0.8;"></i>
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--success-color); line-height: 1;">
                %{{ $approvalRate }}</div>
            <div
                style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-top: 8px; text-transform: uppercase;">
                {{ __('Onaylanma Başarısı') }}</div>
        </div>

        <div class="card glass-card" style="padding: 25px; border-top: 4px solid var(--warning-color); text-align: center;">
            <i data-lucide="refresh-cw"
                style="width: 40px; height: 40px; color: var(--warning-color); margin-bottom: 15px; opacity: 0.8;"></i>
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--text-color); line-height: 1;">
                {{ $totalRevisions }}</div>
            <div
                style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600; margin-top: 8px; text-transform: uppercase;">
                {{ __('Yapılan Revizyon') }}</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start;">
        <div class="card glass-card">
            <div class="card-header"
                style="background: #f8fafc; padding: 20px; border-bottom: 1px solid var(--border-color); font-weight: 600;">
                <i data-lucide="pie-chart"
                    style="width: 18px; color: var(--accent-color); vertical-align: text-bottom; margin-right: 5px;"></i>
                {{ __('Doküman Tipi Dağılımı') }}
            </div>
            <div class="card-body" style="padding: 25px;">
                @if ($docTypesChart->isEmpty())
                    <p class="text-muted text-center py-20">{{ __('Kullanıcı henüz sisteme hiç belge yüklememiş.') }}</p>
                @else
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px;">
                        @foreach ($docTypesChart as $stat)
                            @php
                                $percent = $totalDocs > 0 ? round(($stat->total / $totalDocs) * 100) : 0;
                            @endphp
                            <li>
                                <div
                                    style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.9rem; font-weight: 500;">
                                    <span>{{ $stat->documentType?->name ?? __('Genel Kategorisiz') }}</span>
                                    <span>{{ $stat->total }} {{ __('Adet') }} (%{{ $percent }})</span>
                                </div>
                                <div
                                    style="width: 100%; background-color: #e2e8f0; border-radius: 10px; height: 10px; overflow: hidden;">
                                    <div
                                        style="width: {{ $percent }}%; background-color: var(--accent-color); height: 100%; border-radius: 10px;">
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header"
                style="background: #f8fafc; padding: 20px; border-bottom: 1px solid var(--border-color); font-weight: 600;">
                <i data-lucide="activity"
                    style="width: 18px; color: var(--danger-color); vertical-align: text-bottom; margin-right: 5px;"></i>
                {{ __('Onay & Ret Analizi') }}
            </div>
            <div class="card-body" style="padding: 25px;">
                <div style="margin-bottom: 20px;">
                    <div
                        style="display: flex; justify-content: space-between; color: var(--success-color); font-weight: bold; margin-bottom: 5px;">
                        <span>{{ __('Onaylanıp Yayınlananlar') }}</span>
                        <span>{{ $approvedDocs }} {{ __('Adet') }}</span>
                    </div>
                    <div style="width: 100%; background-color: #dcfce7; border-radius: 10px; height: 12px;">
                        <div
                            style="width: {{ $approvalRate }}%; background-color: #22c55e; height: 100%; border-radius: 10px;">
                        </div>
                    </div>
                </div>

                <div>
                    <div
                        style="display: flex; justify-content: space-between; color: var(--danger-color); font-weight: bold; margin-bottom: 5px;">
                        <span>{{ __('Akışta Reddedilenler') }}</span>
                        <span>{{ $rejectedDocs }} {{ __('Adet') }}</span>
                    </div>
                    <div style="width: 100%; background-color: #fee2e2; border-radius: 10px; height: 12px;">
                        <div
                            style="width: {{ $rejectionRate }}%; background-color: #ef4444; height: 100%; border-radius: 10px;">
                        </div>
                    </div>
                </div>

                <div class="mt-20 pt-15"
                    style="border-top: 1px dashed var(--border-color); font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                    <i data-lucide="info" style="width: 14px; vertical-align: middle;"></i>
                    {{ __('Kalan belgeler taslak veya değerlendirme (onay) aşamasında olan belgelerdir.') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
@endpush
