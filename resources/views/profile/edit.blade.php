@extends('layouts.app')

@section('content')
    <div class="form-container">
        <div class="page-header">
            <h1 class="page-title">⚙️ {{ __('Profil Ayarları') }}</h1>
            <p class="text-muted">{{ __('Kişisel bilgilerinizi ve güvenlik seçeneklerinizi buradan yönetebilirsiniz.') }}</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="grid-dashboard" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); align-items: start;">

            <div class="card glass-card">
                <div class="card-header">👤 {{ __('Genel Bilgiler') }}</div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" class="modern-form">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">{{ __('Ad Soyad') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('E-Posta') }}</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled
                                style="background: #f1f5f9; cursor: not-allowed;">
                            <small class="form-help">{{ __('Kurumsal e-posta adresi değiştirilemez.') }}</small>
                        </div>

                        <div class="form-section-divider"></div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Yeni Şifre (İsteğe Bağlı)') }}</label>
                            <input type="password" name="password" class="form-control"
                                placeholder="{{ __('Değiştirmek istemiyorsanız boş bırakın') }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Yeni Şifre (Tekrar)') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••">
                        </div>

                        <button type="submit"
                            class="btn btn-primary btn-block mt-20">{{ __('Değişiklikleri Kaydet') }}</button>
                    </form>
                </div>
            </div>

            <div class="card glass-card" style="border-top: 4px solid var(--danger-color);">
                <div class="card-header flex-between" style="color: var(--danger-color);">
                    <span>🔐 {{ __('Özel Kasa Şifresi') }}</span>
                    @if (auth()->user()->vault_password)
                        <span class="badge badge-success" style="font-size: 0.7rem;">{{ __('Aktif') }}</span>
                    @endif
                </div>

                <div class="card-body">
                    <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px; line-height: 1.5;">
                        {!! __(
                            '"Çok Gizli" belgelere erişirken sistem giriş şifrenizden <strong>farklı bir şifre</strong> kullanmak istiyorsanız buradan belirleyebilirsiniz.',
                        ) !!}
                    </p>

                    <form action="{{ route('profile.vault-password.update') }}" method="POST" class="modern-form">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label class="form-label">
                                {{ auth()->user()->vault_password ? __('Yeni Kasa Şifresi') : __('Kasa Şifresi Oluştur') }}
                            </label>
                            <input type="password" name="vault_password" class="form-control"
                                placeholder="{{ __('Özel kasa şifrenizi girin') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Şifreyi Onaylayın') }}</label>
                            <input type="password" name="vault_password_confirmation" class="form-control"
                                placeholder="{{ __('Tekrar girin') }}" required>
                        </div>

                        <button type="submit" class="btn btn-outline-danger btn-block mt-10">
                            {{ auth()->user()->vault_password ? __('Kasa Şifresini Güncelle') : __('Kasa Şifresini Tanımla') }}
                        </button>
                    </form>

                    @if (auth()->user()->vault_password)
                        <div class="mt-30 pt-20" style="border-top: 1px dashed #cbd5e1;">
                            <h4 style="color: #475569; font-size: 0.95rem; margin-bottom: 10px;">
                                {{ __('Kasa Şifrenizi mi Unuttunuz?') }}</h4>
                            <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 15px;">
                                {!! __(
                                    'Eğer özel kasa şifrenizi unuttuysanız, <strong>Mevcut Sistem Giriş Şifrenizi</strong> kullanarak kasanızı sıfırlayabilirsiniz.',
                                ) !!}
                            </p>

                            <form action="{{ route('profile.vault-password.destroy') }}" method="POST"
                                class="modern-form flex-between" style="gap: 10px; align-items: flex-end;">
                                @csrf
                                @method('DELETE')

                                <div class="form-group" style="flex: 1; margin: 0;">
                                    <label class="form-label" style="font-size: 0.8rem;">{{ __('Ana Sistem Şifreniz') }}
                                        <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control" required
                                        placeholder="{{ __('Giriş şifreniz') }}">
                                </div>

                                <button type="submit" class="btn btn-danger"
                                    style="height: 44px; padding: 0 15px; white-space: nowrap;"
                                    onclick="return confirm('{{ __('Kasa şifreniz kalıcı olarak silinecek ve kasa standart giriş şifrenizle açılmaya başlayacaktır. Emin misiniz?') }}')">
                                    🗑️ {{ __('Sıfırla') }}
                                </button>
                            </form>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
@endsection
