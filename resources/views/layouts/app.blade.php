<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KÖKSAN DMS</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <script src="https://unpkg.com/lucide@latest"></script>

    @stack('styles')

    <style>
        /* BİLDİRİM STİLLERİ */
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--danger-color);
            color: white;
            font-size: 0.65rem;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--surface-color);
        }

        .notification-dropdown {
            position: absolute;
            top: 45px;
            right: 0;
            width: 320px;
            background: var(--surface-color);
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            display: none;
            z-index: 1000;
            overflow: hidden;
        }

        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-color);
        }

        .notification-item {
            display: flex;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
        }

        .notification-item:hover {
            background: var(--bg-color);
        }

        .notification-item.unread {
            background: #f0fdf4;
        }

        .notif-icon {
            margin-right: 12px;
            color: var(--accent-color);
        }

        .notif-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }

        .notif-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.3;
            margin-bottom: 5px;
        }

        .notif-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .pulse-badge-animation {
            animation: super-pulse 1s ease-in-out infinite;
        }

        @keyframes super-pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            50% {
                transform: scale(1.3);
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        /* YENİ: Misafir Kullanıcılar İçin Tam Ekran (Menüsüz) Düzen */
        body.guest-mode .app-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: var(--bg-color);
        }

        body.guest-mode .sidebar {
            display: none !important;
        }

        body.guest-mode .topbar {
            display: none !important;
        }

        body.guest-mode .main-content {
            margin-left: 0 !important;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
        }

        body.guest-mode .content-area {
            width: 100%;
            max-width: 1200px;
        }
    </style>
</head>

<body class="{{ auth()->guest() ? 'guest-mode' : '' }}">

    <div class="app-container">

        @auth
            <aside class="sidebar">
                <a href="{{ route('dashboard') }}" class="sidebar-brand"
                    style="text-decoration: none; color: inherit; display: flex; align-items: center;">
                    <i data-lucide="layers" style="margin-right: 10px; width: 24px; height: 24px;"></i>
                    <span style="font-weight: 700; letter-spacing: 0.5px;">KÖKSAN DMS</span>
                </a>
                <ul class="sidebar-nav">
                    <li>
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i data-lucide="layout-dashboard" class="nav-icon"></i> {{ __('Gösterge Paneli') }}
                        </a>
                    </li>

                    <li class="nav-section">{{ __('DOKÜMANLAR') }}</li>
                    <li>
                        <a href="{{ route('documents.index') }}"
                            class="{{ request()->routeIs('documents.index', 'documents.show') ? 'active' : '' }}">
                            <i data-lucide="folder-search" class="nav-icon"></i> {{ __('Tüm Belgeler') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('documents.create') }}"
                            class="{{ request()->routeIs('documents.create') ? 'active' : '' }}">
                            <i data-lucide="upload-cloud" class="nav-icon"></i> {{ __('Yeni Belge Yükle') }}
                        </a>
                    </li>

                    @can('document.create')
                        <li class="nav-section">{{ __('SİSTEM YÖNETİMİ') }}</li>
                        <li>
                            <a href="{{ route('folders.index') }}"
                                class="{{ request()->routeIs('folders.*') ? 'active' : '' }}">
                                <i data-lucide="folder-tree" class="nav-icon"></i> {{ __('Klasör Yönetimi') }}
                            </a>
                        </li>
                        @hasanyrole('Super Admin|Admin')
                            <li>
                                <a href="{{ route('settings.permissions') }}"
                                    class="{{ request()->routeIs('settings.permissions') ? 'active' : '' }}">
                                    <i data-lucide="shield-alert" class="nav-icon"></i> {{ __('3D Yetki Matrisi') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('settings.notifications') }}"
                                    class="{{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
                                    <i data-lucide="printer" class="nav-icon"></i> {{ __('Otomatik Rapor Ayarları') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('settings.mail') }}"
                                    class="{{ request()->routeIs('settings.mail') ? 'active' : '' }}">
                                    <i data-lucide="mail" class="nav-icon"></i> {{ __('Mail Şablonları ve Ayarlar') }}
                                </a>
                            </li>
                        @endhasanyrole
                    @endcan

                    @can('user.manage')
                        <li>
                            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i data-lucide="users" class="nav-icon"></i> {{ __('Kullanıcı Yönetimi') }}
                            </a>
                        </li>
                    @endcan
                </ul>
            </aside>
        @endauth

        <main class="main-content">
            @auth
                <header class="topbar">
                    <div class="search-bar-mini">
                    </div>

                    <div class="header-actions flex-between" style="gap: 20px;">
                        <div class="notification-wrapper" style="position: relative;">
                            <button id="notificationBtn" class="notification-btn"
                                style="background: none; border: none; cursor: pointer; color: var(--text-color); position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; transition: background 0.2s;">
                                <i data-lucide="bell"></i>
                                <span id="notification-badge" class="notification-badge"
                                    style="{{ auth()->user()->unreadNotifications->count() == 0 ? 'display: none;' : '' }}">
                                    {{ auth()->user()->unreadNotifications->count() }}
                                </span>
                            </button>

                            <div id="notificationDropdown" class="notification-dropdown glass-card">
                                <div class="dropdown-header flex-between">
                                    <h4 style="margin: 0; font-size: 1rem;">{{ __('Bildirimler') }}</h4>
                                    <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-primary"
                                            style="background: none; border: none; cursor: pointer; font-size: 0.8rem; color: var(--accent-color); font-weight: 500;">
                                            {{ __('Tümünü Okundu İşaretle') }}
                                        </button>
                                    </form>
                                </div>

                                <div class="dropdown-body">
                                    @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                                        <a href="{{ $notification->data['url'] ?? '#' }}" class="notification-item unread">
                                            <div class="notif-icon">
                                                <i data-lucide="{{ $notification->data['icon'] ?? 'info' }}"></i>
                                            </div>
                                            <div class="notif-content">
                                                <div class="notif-title">
                                                    {{ __($notification->data['title'] ?? 'Bildirim') }}
                                                </div>
                                                <div class="notif-desc">
                                                    {{-- Yeni mimari varsa çevirerek bas, yoksa (eski veriyse) yedeği bas --}}
                                                    @if (isset($notification->data['message_key']))
                                                        {{ __($notification->data['message_key'], $notification->data['message_params'] ?? []) }}
                                                    @else
                                                        {{ __($notification->data['message'] ?? '') }}
                                                    @endif
                                                </div>
                                                <div class="notif-time">{{ $notification->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="text-center p-20 text-muted" style="padding: 20px;">
                                            <div style="display: flex; justify-content: center; margin-bottom: 10px;">
                                                <i data-lucide="mail-open"
                                                    style="width: 32px; height: 32px; opacity: 0.5;"></i>
                                            </div>
                                            {{ __('Yeni bildiriminiz yok.') }}
                                        </div>
                                    @endforelse
                                </div>
                                <div class="dropdown-footer flex-between"
                                    style="padding: 12px 15px; border-top: 1px solid var(--border-color); background: var(--bg-color);">
                                    <a href="{{ route('notifications.history') }}"
                                        style="font-size: 0.85rem; color: var(--accent-color); font-weight: 600; text-decoration: none;">
                                        {{ __('Tümünü Gör') }}
                                    </a>
                                    <a href="{{ route('profile.notifications') }}"
                                        style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="settings" style="width: 14px; height: 14px;"></i>
                                        {{ __('Ayarlar') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="lang-dropdown-container"
                            style="position: relative; display: inline-block; margin-right: 15px;">
                            <button type="button" id="langDropdownBtn"
                                style="background: transparent; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-color); transition: all 0.2s;">
                                <i data-lucide="globe" style="width: 18px; color: var(--primary-color);"></i>
                                <span style="font-weight: 500; font-size: 0.85rem; text-transform: uppercase;">
                                    {{ app()->getLocale() }}
                                </span>
                                <i data-lucide="chevron-down" style="width: 14px; color: var(--text-muted);"></i>
                            </button>

                            <div id="langDropdownMenu"
                                style="display: none; position: absolute; right: 0; top: 110%; background: #fff; min-width: 150px; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid var(--border-color); z-index: 1000; overflow: hidden; transform: translateY(-10px); opacity: 0; transition: all 0.2s ease;">

                                <a href="{{ route('language.switch', 'tr') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-color); font-size: 0.9rem; transition: background 0.2s; {{ app()->getLocale() == 'tr' ? 'background: #f8fafc; font-weight: bold; color: var(--primary-color);' : '' }}">
                                    <span style="font-size: 1.1rem;">🇹🇷</span> Türkçe
                                    @if (app()->getLocale() == 'tr')
                                        <i data-lucide="check" style="width: 14px; margin-left: auto;"></i>
                                    @endif
                                </a>

                                <a href="{{ route('language.switch', 'en') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-color); font-size: 0.9rem; transition: background 0.2s; border-top: 1px solid #f1f5f9; {{ app()->getLocale() == 'en' ? 'background: #f8fafc; font-weight: bold; color: var(--primary-color);' : '' }}">
                                    <span style="font-size: 1.1rem;">🇬🇧</span> English
                                    @if (app()->getLocale() == 'en')
                                        <i data-lucide="check" style="width: 14px; margin-left: auto;"></i>
                                    @endif
                                </a>
                            </div>
                        </div>

                        <div class="user-dropdown-container relative-container" style="position: relative;">
                            <button id="userDropdownBtn" class="btn btn-outline-primary"
                                style="border-radius: 30px; padding: 6px 16px; border-color: var(--border-color); background: var(--bg-color);">
                                <i data-lucide="user-circle"
                                    style="width: 18px; height: 18px; color: var(--text-muted);"></i>
                                <span style="font-weight: 600; margin: 0 6px;">{{ auth()->user()->name }}</span>
                                <i data-lucide="chevron-down"
                                    style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                            </button>

                            <div id="userDropdownMenu" class="dropdown-menu glass-card"
                                style="display: none; position: absolute; top: 110%; right: 0; min-width: 220px; z-index: 1000; padding: 8px; border: 1px solid var(--border-color); border-radius: 12px;">
                                <a href="{{ route('profile.edit') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="settings" style="width: 18px; height: 18px;"></i>
                                    {{ __('Profilimi Düzenle') }}
                                </a>
                                <a href="{{ route('profile.show') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="chart-area" style="width: 18px; height: 18px;"></i>
                                    {{ __('Performansımı İncele') }}
                                </a>
                                <a href="{{ route('profile.delegations') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                                    {{ __('Vekalet İşlemleri') }}
                                </a>
                                <form action="{{ route('logout') }}" method="POST"
                                    style="margin: 0; padding-top: 8px;">
                                    @csrf
                                    <button type="submit"
                                        style="width: 100%; display: flex; align-items: center; gap: 10px; background: none; border: none; padding: 12px; color: var(--danger-color); cursor: pointer; font-size: 0.95rem; font-weight: 500; border-radius: 6px; transition: background 0.2s;">
                                        <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
                                        {{ __('Çıkış Yap') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>
            @endauth

            <section class="content-area">
                @yield('content')
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // LUCIDE İKONLARINI OLUŞTUR (ÇOK ÖNEMLİ)
            lucide.createIcons();

            // SADECE OTURUM AÇIKSA BU SCRİPTLER ÇALIŞSIN
            @auth
            // --- KULLANICI PROFİL MENÜSÜ ---
            const userBtn = document.getElementById('userDropdownBtn');
            const userMenu = document.getElementById('userDropdownMenu');

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.style.display = (userMenu.style.display === 'none' || userMenu.style
                        .display === '') ? 'block' : 'none';
                });

                document.addEventListener('click', function() {
                    userMenu.style.display = 'none';
                });
            }

            // --- BİLDİRİM ZİLİ MENÜSÜ ---
            const notifBtn = document.getElementById('notificationBtn');
            const notifDropdown = document.getElementById('notificationDropdown');

            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('show');
                });

                window.addEventListener('click', function(e) {
                    if (!notifDropdown.contains(e.target)) {
                        notifDropdown.classList.remove('show');
                    }
                });
            }

            // --- AJAX POLLING (BİLDİRİM NABZI) ---
            const badge = document.getElementById('notification-badge');
            if (badge) {
                let currentCount = parseInt(badge.innerText) || 0;

                setInterval(() => {
                    fetch('{{ route('notifications.check') }}', {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) throw new Error("Sunucu yanıt vermedi");
                            return response.json();
                        })
                        .then(data => {
                            if (data.count === undefined) return;

                            if (data.count > currentCount) {
                                currentCount = data.count;
                                badge.innerText = currentCount;
                                badge.style.display = 'flex';
                                badge.classList.add('pulse-badge-animation');
                                setTimeout(() => badge.classList.remove('pulse-badge-animation'), 3000);
                            } else if (data.count < currentCount) {
                                currentCount = data.count;
                                badge.innerText = currentCount;
                                if (currentCount === 0) badge.style.display = 'none';
                            }
                        })
                        .catch(error => console.log('Bildirim kontrolü atlandı.'));
                }, 30000);
            }
            const btn = document.getElementById('langDropdownBtn');
            const menu = document.getElementById('langDropdownMenu');

            if (btn && menu) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = menu.style.display === 'block';

                    if (!isVisible) {
                        menu.style.display = 'block';
                        // Animasyon için minik bir gecikme
                        setTimeout(() => {
                            menu.style.transform = 'translateY(0)';
                            menu.style.opacity = '1';
                        }, 10);
                    } else {
                        menu.style.transform = 'translateY(-10px)';
                        menu.style.opacity = '0';
                        setTimeout(() => {
                            menu.style.display = 'none';

                        }, 200);
                    }
                });

                // Dışarı tıklayınca kapatma
                document.addEventListener('click', function(e) {
                    if (!menu.contains(e.target) && menu.style.display === 'block') {
                        menu.style.transform = 'translateY(-10px)';
                        menu.style.opacity = '0';
                        setTimeout(() => {
                            menu.style.display = 'none';
                        }, 200);
                    }
                });
            }
        @endauth
        });
    </script>
    @stack('scripts')
</body>

</html>
