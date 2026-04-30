@extends('layouts.app')

@section('content')
    <div class="vault-container">
        <div class="vault-box glass-card text-center">

            <div class="vault-icon pulse-animation">
                🔒
            </div>

            <h2 class="vault-title">{{ __('Gizli Dosya Güvenlik Doğrulaması') }}</h2>
            <p class="vault-desc">
                {{ __('Erişmeye çalıştığınız') }} <strong>"{{ $document->title }}"</strong>
                {{ __('belgesi yüksek gizlilik seviyesine') }} (<span
                    class="badge badge-warning">{{ mb_strtoupper(__($document->privacy_level_text)) }}</span>)
                {{ __('sahiptir.') }}<br>
                <br>
                {{ __('Devam etmek ve şifreli kasayı 15 dakikalığına açmak için lütfen oturum açma şifrenizi tekrar giriniz.') }}
            </p>

            @if ($errors->has('password'))
                <div class="alert alert-danger" style="margin-top: 15px; border-radius: 8px;">
                    ❌ {{ $errors->first('password') }}
                </div>
            @endif

            <form action="{{ route('documents.vault.unlock', $document->id) }}" method="POST" class="mt-20">
                @csrf
                <div class="form-group text-left">
                    <label style="color: #94a3b8; font-size: 0.9rem;">{{ __('Mevcut Şifreniz') }}</label>
                    <input type="password" name="password" class="form-control vault-input" required autofocus
                        placeholder="••••••••">
                </div>

                <div class="form-actions mt-20 flex-between">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">{{ __('İptal ve Geri Dön') }}</a>
                    <button type="submit" class="btn btn-warning" style="font-weight: bold; padding: 10px 25px;">
                        🔓 {{ __('Kasayı Aç') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        /* Karanlık Kasa Teması Özelleştirmesi */
        .vault-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            padding: 20px;
        }

        .vault-box {
            background: #0f172a;
            /* Çok koyu lacivert/siyah */
            color: #f8fafc;
            max-width: 500px;
            width: 100%;
            padding: 40px;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .vault-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            line-height: 1;
        }

        .vault-title {
            color: #f1f5f9;
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .vault-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .vault-input {
            background: #1e293b !important;
            border: 1px solid #475569 !important;
            color: #fff !important;
            font-size: 1.2rem;
            letter-spacing: 3px;
            text-align: center;
        }

        .vault-input:focus {
            border-color: var(--warning-color) !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
        }

        @keyframes pulseLock {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.1);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }

        .pulse-animation {
            animation: pulseLock 2s infinite ease-in-out;
        }
    </style>
@endpush
