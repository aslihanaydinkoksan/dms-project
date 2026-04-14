@extends('layouts.app')

@section('content')
    <div class="page-header flex-between">
        <div>
            <h1 class="page-title">{{ __('Kullanıcı Yönetimi') }}</h1>
            <p class="text-muted">{{ __('Sistemdeki tüm personelleri ve erişim yetkilerini yönetin.') }}</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ {{ __('Yeni Kullanıcı') }}</a>
    </div>

    @include('partials.alerts')

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
