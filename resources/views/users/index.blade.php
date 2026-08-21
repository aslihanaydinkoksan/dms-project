@extends('layouts.app')

@section('content')
    <div class="page-header flex-between">
        <div>
            <h1 class="page-title">{{ __('Kullanıcı Yönetimi') }}</h1>
            <p class="text-muted">{{ __('Sistemdeki tüm personelleri ve erişim yetkilerini yönetin.') }}</p>
        </div>
        {{-- Aksiyon Butonları --}}
        <div style="display: flex; gap: 10px; align-items: center;">
            {{-- MYS Senkronizasyon Butonu --}}
            <form action="{{ route('users.sync_mys') }}" method="POST"
                onsubmit="const btn = this.querySelector('button'); btn.disabled = true; btn.innerHTML = '<i data-lucide=\'refresh-cw\' style=\'width: 16px;\'></i> {{ __('Senkronize Ediliyor...') }}';">
                @csrf
                <button type="submit" class="btn btn-outline-primary"
                    style="display: flex; align-items: center; gap: 8px; height: 42px; padding: 0 16px; font-weight: 500;">
                    <i data-lucide="refresh-cw" style="width: 16px;"></i>
                    {{ __('Merkezden Senkronize Et') }}
                </button>
            </form>

            {{-- Yeni Kullanıcı Butonu --}}
            <a href="{{ route('users.create') }}" class="btn btn-primary"
                style="display: flex; align-items: center; height: 42px; padding: 0 18px; font-weight: 500;">
                + {{ __('Yeni Kullanıcı') }}
            </a>
        </div>
    </div>

    @include('partials.alerts')

    <div class="card glass-card"
        style="margin-bottom: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); padding: 20px;">
        <form action="{{ route('users.index') }}" method="GET"
            style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">

            {{-- Arama Kutusu --}}
            <div style="flex: 2; min-width: 200px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block; color: var(--text-color);">{{ __('Kullanıcı Ara') }}</label>
                <div style="position: relative;">
                    <i data-lucide="search"
                        style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-muted);"></i>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                        placeholder="{{ __('Ad, Soyad veya E-Posta...') }}"
                        style="width: 100%; padding: 10px 10px 10px 38px; border-radius: 6px; border: 1px solid var(--border-color);">
                </div>
            </div>

            {{-- Departman Filtresi --}}
            <div style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block; color: var(--text-color);">{{ __('Departman') }}</label>
                <select name="department_id" class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                    <option value="">{{ __('-- Tümü --') }}</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Rol Filtresi --}}
            <div style="flex: 1; min-width: 150px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block; color: var(--text-color);">{{ __('Sistem Rolü') }}</label>
                <select name="role" class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                    <option value="">{{ __('-- Tümü --') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Durum Filtresi --}}
            <div style="flex: 1; min-width: 120px;">
                <label
                    style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block; color: var(--text-color);">{{ __('Hesap Durumu') }}</label>
                <select name="status" class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                    <option value="">{{ __('-- Tümü --') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Aktif') }}
                    </option>
                    <option value="passive" {{ request('status') === 'passive' ? 'selected' : '' }}>{{ __('Pasif') }}
                    </option>
                </select>
            </div>

            {{-- Aksiyon Butonları --}}
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"
                    style="padding: 10px 25px; height: 42px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="filter" style="width: 16px;"></i> {{ __('Uygula') }}
                </button>
                @if (request()->hasAny(['q', 'department_id', 'role', 'status']))
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"
                        style="padding: 10px 15px; height: 42px; display: flex; align-items: center; justify-content: center; background: #fff;"
                        title="{{ __('Filtreleri Temizle') }}">
                        <i data-lucide="x" style="width: 16px;"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="card glass-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Ad Soyad') }}</th>
                        <th>{{ __('E-Posta') }}</th>
                        <th>{{ __('Departman') }}</th>
                        <th>{{ __('Roller') }}</th>
                        <th>{{ __('Durum') }}</th>
                        <th>{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="font-medium">{{ $user->name }}</td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td>{{ $user->department->name ?? __('Atanmadı') }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge"
                                        style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; margin-right: 4px;">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-muted" style="font-size: 0.85rem; font-style: italic;">
                                        <i data-lucide="alert-circle"
                                            style="width: 14px; display: inline-block; vertical-align: middle;"></i>
                                        {{ __('Rol Atanmamış') }}
                                    </span>
                                @endforelse
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Pasif') }}</span>
                                @endif
                            </td>
                            <td class="action-cell">
                                <div class="action-group" style="display: flex; gap: 8px;">
                                    <a href="{{ route('profile.show', $user->id) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="{{ __('Personel Performansını İncele') }}">
                                        <i data-lucide="bar-chart-2" style="width: 16px;"></i> {{ __('İncele') }}
                                    </a>

                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary"
                                        title="{{ __('Düzenle') }}">✏️ {{ __('Düzenle') }}</a>

                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Bu kullanıcıyı sistemden silmek istediğinize emin misiniz? (Soft delete uygulanacaktır)') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="{{ __('Sil') }}">🗑️
                                            {{ __('Sil') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
