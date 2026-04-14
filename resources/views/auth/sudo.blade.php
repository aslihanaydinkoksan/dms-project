@extends('layouts.app')

@section('content')
    <div class="sudo-container" style="min-height: 70vh; display: flex; align-items: center; justify-content: center;">
        <div class="card glass-card"
            style="max-width: 450px; width: 100%; padding: 40px; text-align: center; border-top: 4px solid var(--danger-color);">

            <div class="lock-icon" style="font-size: 3.5rem; margin-bottom: 15px; animation: pulse-red 2s infinite;">
                🔐
            </div>

            <h2 style="color: var(--danger-color); margin-bottom: 10px;">{{__('Çok Gizli Alan')}}</h2>
            <p class="text-muted" style="font-size: 0.95rem; margin-bottom: 25px;">
                {{__('Erişmeye çalıştığınız bu alan ekstra güvenlik katmanı ile korunmaktadır. Devam etmek için lütfen')}}
                <strong>{{__('Kasa Şifrenizi')}}</strong> {{__('(belirlemediyseniz sistem şifrenizi) giriniz.')}}
            </p>

            <form action="{{ route('sudo.verify') }}" method="POST" class="modern-form">
                @csrf
                <div class="form-group text-left">
                    <label>{{__('Kasa Şifreniz')}} <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        required autofocus placeholder="••••••••">
                    @error('password')
                        <span class="text-danger" style="font-size: 0.85rem; margin-top: 5px; display: block;">⚠️
                            {{ $message }}</span>
                    @enderror
                </div>

                <div class="form-actions mt-25">
                    <button type="submit" class="btn btn-danger btn-block"
                        style="padding: 12px; font-weight: bold; font-size: 1.05rem;">
                        {{__('Kilidi Aç ve Devam Et')}}
                    </button>
                </div>

                <div class="mt-15">
                    <a href="{{ route('dashboard') }}"
                        style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">{{__('İptal Et ve Ana Sayfaya
                        Dön')}}</a>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
        <style>
            @keyframes pulse-red {
                0% {
                    transform: scale(1);
                    text-shadow: 0 0 0 rgba(239, 68, 68, 0.7);
                }

                50% {
                    transform: scale(1.1);
                    text-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
                }

                100% {
                    transform: scale(1);
                    text-shadow: 0 0 0 rgba(239, 68, 68, 0);
                }
            }
        </style>
    @endpush
@endsection
