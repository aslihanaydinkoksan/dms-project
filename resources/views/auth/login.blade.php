<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sisteme Giriş - Kurumsal DMS</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Login Sayfasına Özel Ekstra Modern Dokunuşlar */
        .login-body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            /* Kurumsal koyu mavi/arduvas degradeli arkaplan */
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            margin: 0;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: #f1f5f9;
            border-radius: 16px;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            width: 18px;
            height: 18px;
        }

        .input-icon-wrapper .form-control {
            padding-left: 42px;
            height: 46px;
        }

        .kvkk-label {
            font-size: 0.85rem;
            color: var(--text-color);
            cursor: pointer;
            user-select: none;
            line-height: 1.4;
        }

        .kvkk-label a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .kvkk-label a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
    </style>
</head>

<body class="login-body">

    <div class="login-wrapper">
        <div class="card login-card">

            <div class="login-header">
                <div class="login-logo">
                    <i data-lucide="layers" style="width: 32px; height: 32px;"></i>
                </div>
                <h2 class="page-title" style="margin-bottom: 5px; font-size: 1.5rem; color: var(--primary-color);">
                    Sisteme Giriş</h2>
                <p class="text-muted" style="font-size: 0.9rem;">Lütfen kurumsal kimlik bilgilerinizle giriş yapın.</p>
            </div>

            @include('partials.alerts')

            <form action="{{ route('login.post') }}" method="POST" class="modern-form">
                @csrf

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="email" class="form-label"
                        style="font-weight: 600; color: var(--secondary-color);">E-Posta Adresi</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="mail"></i>
                        <input type="email" name="email" id="email" class="form-control"
                            value="{{ old('email') }}" required autofocus placeholder="isim@sirket.com">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="password" class="form-label"
                        style="font-weight: 600; color: var(--secondary-color);">Şifre</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="lock"></i>
                        <input type="password" name="password" id="password" class="form-control" required
                            placeholder="••••••••">
                    </div>
                    <a href="{{ route('password.request') }}"
                        style="font-size: 0.8rem; color: var(--primary-color); text-decoration: none; font-weight: 500;">Şifreni
                        mi unuttun?</a>
                </div>

                <div class="form-group"
                    style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <input type="checkbox" name="kvkk" id="kvkkCheckbox" required
                        style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: var(--accent-color);">
                    <label for="kvkkCheckbox" class="kvkk-label">
                        <a href="#" id="openKvkkModal">KVKK Aydınlatma Metni</a>'ni okudum, anladım ve sistemin
                        işleyişi kapsamında kişisel verilerimin işlenmesini kabul ediyorum.
                    </label>
                </div>

                <div class="form-group" style="display: flex; justify-content: center; margin-bottom: 25px;">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block"
                        style="padding: 14px; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        Giriş Yap <i data-lucide="log-in" style="width: 18px;"></i>
                    </button>
                </div>
            </form>

            <div class="login-footer text-muted" style="text-align: center; margin-top: 25px; font-size: 0.8rem;">
                <i data-lucide="shield-check"
                    style="width: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                &copy; {{ date('Y') }} Kurumsal Doküman Yönetim Sistemi
            </div>

        </div>
    </div>

    <div id="kvkkModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 99999; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
        <div class="modal-content"
            style="background: #fff; width: 90%; max-width: 800px; height: 85vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); padding: 0;">

            <div
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h2
                    style="margin: 0; font-size: 1.15rem; color: var(--primary-color); font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="file-text" style="color: var(--accent-color);"></i> Kişisel Verilerin Korunması
                    (KVKK) Aydınlatma Metni
                </h2>
                <button type="button" id="closeKvkkBtn"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); transition: color 0.2s;">&times;</button>
            </div>

            <div style="flex: 1; position: relative; background: #e2e8f0;">
                <iframe src="{{ asset('docs/kvkk_aydinlatma_metni.pdf') }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                    style="width: 100%; height: 100%; border: none; position: absolute; top: 0; left: 0;"></iframe>
            </div>

            <div
                style="padding: 15px 25px; text-align: right; border-top: 1px solid var(--border-color); background: #f8fafc;">
                <button type="button" id="acceptKvkkBtn" class="btn btn-primary"
                    style="padding: 10px 25px; font-size: 1rem; display: inline-flex; align-items: center; gap: 8px;">
                    <i data-lucide="check-square" style="width: 18px;"></i> Okudum, Onaylıyorum
                </button>
            </div>

        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lucide ikonlarını oluştur
            lucide.createIcons();

            const modal = document.getElementById('kvkkModal');
            const openBtn = document.getElementById('openKvkkModal');
            const closeBtn = document.getElementById('closeKvkkBtn');
            const acceptBtn = document.getElementById('acceptKvkkBtn');
            const checkbox = document.getElementById('kvkkCheckbox');

            if (openBtn && modal) {
                // Modalı Aç
                openBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = 'flex';
                });

                // Modalı Kapat (Çarpı)
                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });

                // Dışarı tıklayınca kapat
                window.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });

                // OKUDUM ONAYLIYORUM BUTONU
                acceptBtn.addEventListener('click', function() {
                    if (checkbox) {
                        checkbox.checked = true; // Checkbox'ı otomatik işaretle

                        // Görsel Geri Bildirim (Yeşil Parlama)
                        const kvkkContainer = checkbox.closest('.form-group');
                        if (kvkkContainer) {
                            kvkkContainer.style.transition = 'all 0.3s ease';
                            kvkkContainer.style.background = '#f0fdf4'; // success background
                            kvkkContainer.style.borderColor = '#86efac'; // success border

                            setTimeout(() => {
                                kvkkContainer.style.background = '#f8fafc';
                                kvkkContainer.style.borderColor = 'var(--border-color)';
                            }, 1500);
                        }
                    }
                    modal.style.display = 'none'; // Modalı kapat
                });
            }
        });
    </script>
</body>

</html>
