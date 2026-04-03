@extends('layouts.app')

@section('content')
    <div class="page-header flex-between">
        <div>
            <h1 class="page-title">Kullanıcı Yönetimi</h1>
            <p class="text-muted">Sistemdeki tüm personelleri ve erişim yetkilerini yönetin.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ Yeni Kullanıcı</a>
    </div>

    @include('partials.alerts') <div class="card glass-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-Posta</th>
                        <th>Departman</th>
                        <th>Roller</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="font-medium">{{ $user->name }}</td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td>{{ $user->department->name ?? 'Atanmadı' }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge"
                                        style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; margin-right: 4px;">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-muted" style="font-size: 0.85rem; font-style: italic;">
                                        <i data-lucide="alert-circle"
                                            style="width: 14px; display: inline-block; vertical-align: middle;"></i> Rol
                                        Atanmamış
                                    </span>
                                @endforelse
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Pasif</span>
                                @endif
                            </td>
                            <td class="action-cell">
                                <div class="action-group">
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary"
                                        title="Düzenle">✏️ Düzenle</a>

                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                        onsubmit="return confirm('Bu kullanıcıyı sistemden silmek istediğinize emin misiniz? (Soft delete uygulanacaktır)');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">🗑️
                                            Sil</button>
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
