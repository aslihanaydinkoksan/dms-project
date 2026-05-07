@extends('layouts.app')

<head>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>
@section('content')
    <div class="page-header">
        <h1 class="page-title">🤝 {{ __('Vekalet Yönetimi') }}</h1>
        <p class="text-muted">
            {{ __('İzinli veya raporlu olduğunuz dönemlerde iş akışlarının durmaması için onay yetkilerinizi devredin.') }}
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success mt-20"
            style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="doc-detail-layout mt-20">
        <aside class="doc-tabs-sidebar card glass-card">
            <ul class="vertical-tabs" id="delegationTabs">
                <li class="tab-item active" data-target="tab-given">📤 {{ __('Verdiğim Vekaletler') }}</li>
                <li class="tab-item" data-target="tab-received">📥 {{ __('Bana Verilen Vekaletler') }}</li>
            </ul>
        </aside>

        <main class="doc-tab-content card glass-card">

            <div id="tab-given" class="tab-pane active">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">{{ __('Yeni Vekalet Tanımla') }}</h2>

                <form action="{{ route('profile.delegations.store') }}" method="POST" class="modern-form"
                    style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 30px;">
                    @csrf
                    <div class="metadata-grid-2col">
                        <div class="form-group">
                            <label>{{ __('Vekil Tayin Edilecek Kişi') }} <span class="text-danger">*</span></label>
                            <select name="proxy_id" class="form-control" required>
                                <option value="">{{ __('-- Personel Seçin --') }}</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Sebep') }} <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control"
                                placeholder="{{ __('Örn: Yıllık İzin, Sağlık Raporu') }}" required>
                            @error('reason')
                                <span class="text-danger"
                                    style="font-size: 0.85rem; margin-top: 5px; display: block; font-weight: 500;">
                                    ⚠️ {{ $message }}
                                </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>{{ __('Başlangıç Tarihi') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Bitiş Tarihi') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="text-right mt-15">
                        <button type="submit" class="btn btn-primary pulse-animation">➕
                            {{ __('Vekaleti Başlat') }}</button>
                    </div>
                </form>

                <h3>{{ __('Geçmiş ve Aktif Vekaletlerim') }}</h3>
                <div class="table-responsive mt-15">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th>{{ __('Vekil Personel') }}</th>
                                <th>{{ __('Tarih Aralığı') }}</th>
                                <th>{{ __('Sebep') }}</th>
                                <th>{{ __('Durum') }}</th>
                                <th>{{ __('İşlem') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($given as $del)
                                <tr>
                                    <td><strong>{{ $del->proxy?->name ?? __('Bilinmeyen / Silinmiş Personel') }}</strong>
                                    </td>
                                    <td style="font-size: 0.85rem;">
                                        {{ $del->start_date->format('d.m.Y H:i') }} <br>
                                        <span class="text-muted">{{ __('İle') }}</span> <br>
                                        {{ $del->end_date->format('d.m.Y H:i') }}
                                    </td>
                                    <td>{{ $del->reason ?? '-' }}</td>
                                    <td>
                                        @php
                                            $now = \Carbon\Carbon::now();
                                        @endphp

                                        @if ($now->gte($del->start_date) && $now->lte($del->end_date))
                                            <span class="badge badge-success">🟢 {{ __('Aktif') }}</span>
                                        @elseif($now->lt($del->start_date))
                                            <span class="badge badge-warning">⏳ {{ __('Bekliyor') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Süresi Doldu') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('profile.delegations.destroy', $del->id) }}" method="POST"
                                            onsubmit="return confirm('{{ __('Bu vekaleti iptal etmek istediğinize emin misiniz?') }}');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger">{{ __('İptal') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted p-15">
                                        {{ __('Henüz kimseye vekalet vermediniz.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-received" class="tab-pane" style="display: none;">
                <h2 style="color: var(--warning-color); margin-bottom: 20px;">{{ __('Bana Devredilen Yetkiler') }}</h2>
                <div class="alert alert-warning mb-20">
                    💡 {!! __(
                        'Aşağıdaki listede durumu <strong>\"Aktif\"</strong> olan kişilere ait iş akışlarını ve belge onaylarını, kendi hesabınız üzerinden <strong>vekaleten</strong> gerçekleştirebilirsiniz.',
                    ) !!}
                </div>

                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th>{{ __('Yetkiyi Devreden (Asıl Sahip)') }}</th>
                                <th>{{ __('Tarih Aralığı') }}</th>
                                <th>{{ __('Sebep') }}</th>
                                <th>{{ __('Durum') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($received as $rec)
                                <tr>
                                    <td><strong>{{ $rec->delegator->name ?? __('Bilinmeyen / Silinmiş Personel') }}</strong>
                                    </td>
                                    <td style="font-size: 0.85rem;">
                                        {{ $rec->start_date->format('d.m.Y H:i') }} <br>
                                        <span class="text-muted">{{ __('İle') }}</span> <br>
                                        {{ $rec->end_date->format('d.m.Y H:i') }}
                                    </td>
                                    <td>{{ $rec->reason ?? '-' }}</td>
                                    <td>
                                        @php
                                            $now = \Carbon\Carbon::now();
                                        @endphp

                                        @if ($now->gte($rec->start_date) && $now->lte($rec->end_date))
                                            <span class="badge badge-success">🟢 {{ __('Aktif') }}</span>
                                        @elseif($now->lt($rec->start_date))
                                            <span class="badge badge-warning">⏳ {{ __('Bekliyor') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Süresi Doldu') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted p-15">
                                        {{ __('Şu anda kimsenin vekili değilsiniz.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('#delegationTabs .tab-item');
            const panes = document.querySelectorAll('.tab-pane');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    panes.forEach(p => {
                        p.classList.remove('active');
                        p.style.display = 'none';
                    });

                    this.classList.add('active');
                    const target = document.getElementById(this.getAttribute('data-target'));
                    target.classList.add('active');
                    target.style.display = 'block';
                    target.style.opacity = 0;
                    setTimeout(() => target.style.opacity = 1, 50);
                });
            });
            new TomSelect(
                'select[name="proxy_id"]', { // name kısmı senin select'ine göre değişebilir (Örn: proxy_id, user_id)
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    placeholder: "Kime vekalet vereceğinizi arayın...",
                });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .tab-pane {
            transition: opacity 0.3s ease;
        }

        /* Tom Select'in senin modern temana uyması için ufak dokunuşlar */
        .ts-control {
            border-radius: 6px;
            padding: 10px 12px;
            border-color: var(--border-color);
            font-size: 0.95rem;
        }

        .ts-control.focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
    </style>
@endpush
