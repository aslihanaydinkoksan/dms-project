@extends('layouts.app')
{{-- Not: Eğer login için özel bir layout'un varsa (örn: layouts.guest) onu kullan. --}}

@section('content')
    <div style="min-height: 80vh; display: flex; align-items: center; justify-content: center;">
        <div class="card glass-card"
            style="max-width: 450px; width: 100%; padding: 40px; border-top: 4px solid var(--primary-color);">

            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 3rem; margin-bottom: 10px;">📩</div>
                <h2 style="color: var(--primary-color); margin: 0;">Şifremi Unuttum</h2>
                <p class="text-muted" style="font-size: 0.9rem; margin-top: 10px;">
                    Sistemde kayıtlı e-posta adresinizi girin. Size şifrenizi sıfırlamanız için güvenli bir bağlantı
                    göndereceğiz.
                </p>
            </div>

            @if (session('success'))
                <div class="alert alert-success"
                    style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                    ✅ {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="modern-form">
                @csrf
                <div class="form-group">
                    <label>E-Posta Adresiniz</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required autofocus placeholder="ornek@koksan.com">
                    @error('email')
                        <span class="text-danger" style="font-size: 0.85rem; margin-top: 5px; display: block;">⚠️
                            {{ $message }}</span>
                    @enderror
                </div>

                <div class="form-actions mt-25">
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-weight: bold;">
                        Sıfırlama Bağlantısı Gönder
                    </button>
                </div>

                <div class="text-center mt-20">
                    <a href="{{ route('login') }}"
                        style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">&larr; Giriş Ekranına
                        Dön</a>
                </div>
            </form>
        </div>
    </div>
@endsection
