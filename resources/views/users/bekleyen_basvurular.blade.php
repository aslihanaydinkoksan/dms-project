@extends('layouts.app')

@push('styles')
    <style>
        /* Senin style.css yapına uygun özel liste stilleri */
        .action-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action-item {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
            background-color: var(--surface-color);
        }

        .action-item:last-child {
            border-bottom: none;
        }

        .action-item:hover {
            background-color: var(--bg-color);
        }

        .action-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            color: var(--accent-color);
            font-size: 1.2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .form-control-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
            min-width: 140px;
        }

        /* Senin select tasarımına uyum */
        .select-role {
            border-color: rgba(79, 70, 229, 0.3);
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--accent-color);
        }
    </style>
@endpush

@section('content')
    <!-- 1. Sayfa Yapısı ve Kapsayıcılar -->
    <div class="page-header flex-between mb-30">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div
                style="background: var(--surface-color); padding: 12px; border-radius: var(--border-radius); border: 1px solid var(--border-color); color: var(--warning-color); box-shadow: var(--card-shadow);">
                <i data-lucide="user-clock" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h2 class="page-title" style="margin: 0 0 4px 0;">Onay Bekleyenler</h2>
                <p class="text-muted" style="margin: 0; font-size: 0.9rem;">DMS sistemine erişim için departman ve rol ataması
                    bekleyen adaylar.</p>
            </div>
        </div>
        <div>
            <span class="badge badge-warning"
                style="font-size: 0.9rem; padding: 8px 16px; border-radius: 20px; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2);">
                {{ $bekleyenler->count() }} Bekleyen Kayıt
            </span>
        </div>
    </div>

    <!-- Başarı Mesajı (Senin alert yapılarına uyumlu) -->
    @if (session('success'))
        <div class="alert alert-success" style="display: flex; align-items: center; gap: 10px;">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- 2. Liste Tasarımı (.card sınıfı style.css'den geliyor) -->
    <div class="card" style="padding: 0; overflow: hidden;">
        @if ($bekleyenler->count() > 0)
            <ul class="action-list">
                @foreach ($bekleyenler as $user)
                    <li class="action-item">

                        <!-- Sol: Aday Bilgisi -->
                        <div style="display: flex; align-items: center; gap: 15px; flex: 1; min-width: 300px;">
                            <div class="action-icon-wrapper">
                                {{ mb_strtoupper(mb_substr(trim($user->name), 0, 1, 'UTF-8'), 'UTF-8') }}
                            </div>
                            <div>
                                <div class="font-bold"
                                    style="color: var(--secondary-color); font-size: 1.1rem; margin-bottom: 2px;">
                                    {{ $user->name }}</div>
                                <div class="text-muted" style="font-size: 0.85rem; margin-bottom: 8px;">{{ $user->email }}
                                </div>

                                <div style="display: flex; gap: 10px; align-items: center; font-size: 0.8rem;">
                                    <span
                                        style="background: var(--surface-color); border: 1px solid var(--border-color); padding: 4px 8px; border-radius: 6px; color: var(--text-color); display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="building-2" style="width: 14px; color: var(--text-muted);"></i>
                                        {{ $user->department ? $user->department->name : 'Birim Seçilmemiş' }}
                                    </span>
                                    <span class="text-muted" style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="clock" style="width: 14px;"></i>
                                        {{ $user->created_at->diffForHumans() }} başvurdu
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Sağ: Aksiyon Alanı -->
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">

                            <!-- Onayla Formu -->
                            <form action="{{ route('r_yonetim_basvuru_onayla', $user->id) }}" method="POST"
                                style="display: flex; align-items: center; gap: 10px;">
                                @csrf

                                <select name="department_id" class="form-control form-control-sm" required>
                                    <option value="">Departman...</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ $user->department_id == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="role" class="form-control form-control-sm select-role" required>
                                    <option value="">Rol Ata...</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn"
                                    style="background-color: var(--success-color); color: white; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);">
                                    <i data-lucide="check-circle" style="width: 18px;"></i> Onayla
                                </button>
                            </form>

                            <!-- Reddet Formu -->
                            <form action="{{ route('r_yonetim_basvuru_reddet', $user->id) }}" method="POST"
                                onsubmit="return confirm('Bu başvuruyu reddetmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Reddet">
                                    <i data-lucide="x-circle" style="width: 18px;"></i>
                                </button>
                            </form>

                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <!-- 4. Boş Durum (Empty State) -->
            <div style="padding: 70px 20px; text-align: center; background: var(--surface-color);">
                <div
                    style="display: inline-flex; align-items: center; justify-content: center; width: 90px; height: 90px; border-radius: 50%; background: #d1fae5; border: 6px solid #f0fdf4; color: var(--success-color); margin-bottom: 20px;">
                    <i data-lucide="shield-check" style="width: 45px; height: 45px;"></i>
                </div>
                <h3 style="color: var(--secondary-color); margin-bottom: 10px; font-weight: 700; font-size: 1.3rem;">Her Şey
                    Kontrol Altında!</h3>
                <p class="text-muted" style="max-width: 450px; margin: 0 auto; line-height: 1.6;">
                    Şu anda sistemde onayınızı bekleyen hiçbir kullanıcı bulunmuyor. Yeni kayıtlar geldiğinde burada
                    listelenecektir.
                </p>
            </div>
        @endif
    </div>
@endsection
