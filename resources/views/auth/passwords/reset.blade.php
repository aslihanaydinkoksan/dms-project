@extends('layouts.app')

@section('content')
    <div style="min-height: 80vh; display: flex; align-items: center; justify-content: center;">
        <div class="card glass-card"
            style="max-width: 450px; width: 100%; padding: 40px; border-top: 4px solid var(--success-color);">

            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 3rem; margin-bottom: 10px;">🔐</div>
                <h2 style="color: var(--success-color); margin: 0;">{{__('Yeni Şifre Belirle')}}</h2>
                <p class="text-muted" style="font-size: 0.9rem; margin-top: 10px;">
                    {{__('Lütfen hesabınız için yeni ve güvenli bir şifre oluşturun.')}}
                </p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="modern-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label>{{__('E-Posta Adresiniz')}}</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ $email ?? old('email') }}" required readonly style="background: #f8fafc; color: #64748b;">
                    @error('email')
                        <span class="text-danger" style="font-size: 0.85rem; margin-top: 5px; display: block;">⚠️
                            {{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('Yeni Şifre')}}</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        required autofocus placeholder="{{__('En az 6 karakter')}}">
                    @error('password')
                        <span class="text-danger" style="font-size: 0.85rem; margin-top: 5px; display: block;">⚠️
                            {{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>{{__('Yeni Şifre (Tekrar)')}}</label>
                    <input type="password" name="password_confirmation" class="form-control" required
                        placeholder="{{__('Şifrenizi doğrulayın')}}">
                </div>

                <div class="form-actions mt-25">
                    <button type="submit" class="btn btn-success btn-block" style="padding: 12px; font-weight: bold;">
                        {{__('Şifremi Güncelle')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
