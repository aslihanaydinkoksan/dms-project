<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KÖKSAN DMS</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    {{-- FOUC (Sayfa yüklenirken kayma) Engellemek İçin Bloklayıcı Script --}}
    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>

    @stack('styles')

    <style>
        /* Tom Select'in senin modern temana uyması için ufak dokunuşlar */
        .ts-control {
            border-radius: 6px;
            padding: 10px 12px;
            border-color: var(--border-color);
            font-size: 0.95rem;
        }

        .ts-control.focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }

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

        /* --- FAVORİLER SAĞ PANEL (DRAWER) STİLLERİ --- */
        .favorites-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .favorites-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .favorites-drawer {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            max-width: 100%;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid var(--border-color);
        }

        .favorites-drawer.open {
            right: 0;
        }

        .drawer-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .drawer-body {
            padding: 0;
            overflow-y: auto;
            flex-grow: 1;
            position: relative;
        }

        @keyframes spin {
            from {
                transform: translateY(-50%) rotate(0deg);
            }

            to {
                transform: translateY(-50%) rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        /* --- YENİ: FAVORİLER DÜZENLE MODU (EDİT MODE) STİLLERİ --- */
        #favDrawerBody.edit-mode-active a[href*="/documents/"] {
            pointer-events: none;
            opacity: 0.4;
        }

        #favDrawerBody.edit-mode-active a.btn-outline-primary {
            display: none !important;
        }

        #favDrawerBody.edit-mode-active .toggle-fav-btn {
            border-color: var(--danger-color) !important;
            background: #fef2f2 !important;
            animation: pulse-red 1.5s infinite;
        }

        #favDrawerBody.edit-mode-active .toggle-fav-btn svg {
            color: var(--danger-color) !important;
            fill: rgba(239, 68, 68, 0.2) !important;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        @media (max-width: 768px) {
            .brand-text {
                display: none;
                /* Mobilde sadece ikon kalsın */
            }
        }
    </style>

</head>

<body class="{{ auth()->guest() ? 'guest-mode' : '' }}">

    <div class="app-container">
        @auth
            <aside class="sidebar">
                <ul class="sidebar-nav">
                    @can('menu.dashboard')
                        <li>
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i data-lucide="layout-dashboard" class="nav-icon"></i> {{ __('Gösterge Paneli') }}
                            </a>
                        </li>
                    @endcan

                    @canany(['menu.documents', 'menu.folders'])
                        <li class="nav-section">{{ __('DOKÜMANLAR') }}</li>
                    @endcanany

                    @can('menu.documents')
                        <li>
                            <a href="{{ route('documents.index') }}"
                                class="{{ request()->routeIs('documents.index', 'documents.show') ? 'active' : '' }}">
                                <i data-lucide="folder-search" class="nav-icon"></i> {{ __('Tüm Belgeler') }}
                            </a>
                        </li>
                    @endcan

                    @can('menu.folders')
                        <li>
                            <a href="{{ route('folders.index') }}"
                                class="{{ request()->routeIs('folders.*') ? 'active' : '' }}">
                                <i data-lucide="folder-tree" class="nav-icon"></i> {{ __('Klasörler') }}
                            </a>
                        </li>
                    @endcan

                    @can('menu.documents')
                        <li>
                            <a href="{{ route('documents.create') }}"
                                class="{{ request()->routeIs('documents.create') ? 'active' : '' }}">
                                <i data-lucide="upload-cloud" class="nav-icon"></i> {{ __('Yeni Belge Yükle') }}
                            </a>
                        </li>
                    @endcan

                    @canany(['menu.settings', 'menu.users', 'menu.reports'])
                        <li class="nav-section">{{ __('SİSTEM YÖNETİMİ') }}</li>

                        @can('menu.users')
                            <li>
                                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i data-lucide="users" class="nav-icon"></i> {{ __('Kullanıcı Yönetimi') }}
                                </a>
                            </li>
                        @endcan

                        @can('menu.settings')
                            <li>
                                <a href="{{ route('settings.permissions') }}"
                                    class="{{ request()->routeIs('settings.permissions') ? 'active' : '' }}">
                                    <i data-lucide="shield-alert" class="nav-icon"></i> {{ __('Sistem Ayarları') }}
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
                            <li>
                                <a href="{{ route('settings.intents.index') }}"
                                    class="{{ request()->routeIs('settings.intents.*') ? 'active' : '' }}">
                                    <i data-lucide="bot" class="nav-icon"></i> {{ __('Asistan Eğitimi') }}
                                </a>
                            </li>
                        @endcan
                    @endcanany
                </ul>
            </aside>
        @endauth

        <main class="main-content">
            @auth
                <header class="topbar">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <button type="button" id="sidebarToggleBtn" class="hamburger-btn"
                            title="{{ __('Gösterge Panelini Aç/Kapat') }}">
                            <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                        </button>

                        <a href="{{ route('dashboard') }}" class="brand-logo"
                            style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--primary-color); font-weight: 800; font-size: 1.2rem; letter-spacing: 0.5px; transition: opacity 0.2s; background: transparent !important; padding: 0 !important; margin: 0 !important;">
                            <i data-lucide="layers" style="width: 26px; height: 26px; color: var(--accent-color);"></i>
                            <span class="brand-text">KÖKSAN DMS</span>
                        </a>

                        <div class="search-bar-mini"></div> {{-- Senin mevcut arama kutun --}}
                    </div>

                    <div class="header-actions flex-between" style="gap: 20px;">

                        {{-- FAVORİLER BUTONU --}}
                        <button type="button" id="openFavoritesBtn" class="notification-btn"
                            title="{{ __('Favorilerim') }}"
                            style="background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; transition: background 0.2s;">
                            <i data-lucide="star" style="color: var(--warning-color); fill: rgba(245, 158, 11, 0.2);"></i>
                        </button>

                        {{-- BİLDİRİM MERKEZİ --}}
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
                                            <div class="notif-icon"><i
                                                    data-lucide="{{ $notification->data['icon'] ?? 'info' }}"></i></div>
                                            <div class="notif-content">
                                                <div class="notif-title">
                                                    {{ __($notification->data['title'] ?? 'Bildirim') }}</div>
                                                <div class="notif-desc">{{ __($notification->data['message'] ?? '') }}
                                                </div>
                                                <div class="notif-time">{{ $notification->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="text-center p-20 text-muted" style="padding: 20px;">
                                            <div style="display: flex; justify-content: center; margin-bottom: 10px;"><i
                                                    data-lucide="mail-open"
                                                    style="width: 32px; height: 32px; opacity: 0.5;"></i></div>
                                            {{ __('Yeni bildiriminiz yok.') }}
                                        </div>
                                    @endforelse
                                </div>
                                <div class="dropdown-footer flex-between"
                                    style="padding: 12px 15px; border-top: 1px solid var(--border-color); background: var(--bg-color);">
                                    <a href="{{ route('notifications.history') }}"
                                        style="font-size: 0.85rem; color: var(--accent-color); font-weight: 600; text-decoration: none;">{{ __('Tümünü Gör') }}</a>
                                    <a href="{{ route('profile.notifications') }}"
                                        style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="settings" style="width: 14px; height: 14px;"></i>
                                        {{ __('Ayarlar') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- DİL SEÇİCİ --}}
                        <div class="lang-dropdown-container" style="position: relative; display: inline-block;">
                            <button type="button" id="langDropdownBtn"
                                style="background: transparent; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-color); transition: all 0.2s;">
                                <i data-lucide="globe" style="width: 18px; color: var(--primary-color);"></i>
                                <span
                                    style="font-weight: 500; font-size: 0.85rem; text-transform: uppercase;">{{ app()->getLocale() }}</span>
                                <i data-lucide="chevron-down" style="width: 14px; color: var(--text-muted);"></i>
                            </button>
                            <div id="langDropdownMenu"
                                style="display: none; position: absolute; right: 0; top: 110%; background: #fff; min-width: 150px; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid var(--border-color); z-index: 1000; overflow: hidden; transform: translateY(-10px); opacity: 0; transition: all 0.2s ease;">
                                <a href="{{ route('language.switch', 'tr') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-color); font-size: 0.9rem; transition: background 0.2s; {{ app()->getLocale() == 'tr' ? 'background: #f8fafc; font-weight: bold; color: var(--primary-color);' : '' }}">
                                    <span style="font-size: 1.1rem;">🇹🇷</span> Türkçe @if (app()->getLocale() == 'tr')
                                        <i data-lucide="check" style="width: 14px; margin-left: auto;"></i>
                                    @endif
                                </a>
                                <a href="{{ route('language.switch', 'en') }}"
                                    style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-color); font-size: 0.9rem; transition: background 0.2s; border-top: 1px solid #f1f5f9; {{ app()->getLocale() == 'en' ? 'background: #f8fafc; font-weight: bold; color: var(--primary-color);' : '' }}">
                                    <span style="font-size: 1.1rem;">🇬🇧</span> English @if (app()->getLocale() == 'en')
                                        <i data-lucide="check" style="width: 14px; margin-left: auto;"></i>
                                    @endif
                                </a>
                            </div>
                        </div>

                        {{-- KULLANICI PROFİL MENÜSÜ --}}
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

    {{-- YENİ: FAVORİLER OFFCANVAS (SAĞ PANEL) VE ARAMA KUTUSU --}}
    @auth
        <div class="favorites-overlay" id="favOverlay"></div>
        <div class="favorites-drawer" id="favDrawer">
            <div class="drawer-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="star" style="fill: var(--warning-color); color: var(--warning-color);"></i>
                        {{ __('Favorilerim') }}
                    </h3>
                    <button type="button" id="closeFavoritesBtn"
                        style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding: 5px;">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                {{-- YENİ: EKLE VE DÜZENLE BUTONLARI --}}
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-primary"
                        style="flex: 1; display: flex; justify-content: center; align-items: center; gap: 6px; text-decoration: none; border-radius: 8px;">
                        <i data-lucide="plus-circle" style="width: 16px;"></i> {{ __('Yeni Ekle') }}
                    </a>
                    <button type="button" id="editFavoritesBtn" class="btn btn-sm btn-outline-secondary"
                        style="flex: 1; display: flex; justify-content: center; align-items: center; gap: 6px; border-radius: 8px; transition: all 0.2s;">
                        <i data-lucide="settings-2" style="width: 16px;"></i> <span>{{ __('Düzenle') }}</span>
                    </button>
                </div>

                {{-- DRAWER CANLI ARAMA --}}
                <div style="position: relative; margin-top: 15px;">
                    <i data-lucide="search"
                        style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-muted);"></i>
                    <input type="text" id="drawerFavSearch" placeholder="{{ __('Favorilerde ara...') }}"
                        style="width: 100%; padding: 10px 35px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; outline: none; font-size: 0.9rem;">
                    <i data-lucide="loader" id="drawerFavSpinner" class="spin"
                        style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--warning-color); display: none;"></i>
                </div>
            </div>
            <div class="drawer-body custom-scrollbar" id="favDrawerBody">
                <div style="display:flex; justify-content:center; padding: 40px; color: var(--warning-color);">
                    <i data-lucide="loader" class="spin"></i>
                </div>
            </div>
        </div>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            // --- SİDEBAR AÇ/KAPAT (PUSH MANTIĞI) ---
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');

            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    // Sınıfı direkt <html> (documentElement) etiketinden değiştiriyoruz
                    document.documentElement.classList.toggle('sidebar-collapsed');

                    // Yeni durumu hafızaya kaydet
                    const isNowCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', isNowCollapsed);
                });
            }

            @auth
            // --- 1. DİL SEÇİCİ DROPDOWN ---
            const langBtn = document.getElementById('langDropdownBtn');
            const langMenu = document.getElementById('langDropdownMenu');

            if (langBtn && langMenu) {
                langBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = langMenu.style.display === 'block';

                    if (!isVisible) {
                        langMenu.style.display = 'block';
                        setTimeout(() => {
                            langMenu.style.transform = 'translateY(0)';
                            langMenu.style.opacity = '1';
                        }, 10);
                    } else {
                        langMenu.style.transform = 'translateY(-10px)';
                        langMenu.style.opacity = '0';
                        setTimeout(() => {
                            langMenu.style.display = 'none';
                        }, 200);
                    }
                });
            }

            // --- 2. KULLANICI PROFİL MENÜSÜ ---
            const userBtn = document.getElementById('userDropdownBtn');
            const userMenu = document.getElementById('userDropdownMenu');

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.style.display = (userMenu.style.display === 'none' || userMenu.style
                        .display === '') ? 'block' : 'none';
                });
            }

            // --- 3. BİLDİRİM MENÜSÜ ---
            const notifBtn = document.getElementById('notificationBtn');
            const notifDropdown = document.getElementById('notificationDropdown');

            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('show');
                });
            }

            // --- GENEL: DIŞARI TIKLAYINCA KAPATMA ---
            window.addEventListener('click', function(e) {
                if (langMenu && !langMenu.contains(e.target)) {
                    langMenu.style.transform = 'translateY(-10px)';
                    langMenu.style.opacity = '0';
                    setTimeout(() => {
                        langMenu.style.display = 'none';
                    }, 200);
                }
                if (userMenu) userMenu.style.display = 'none';
                if (notifDropdown) notifDropdown.classList.remove('show');
            });

            // --- 4. AJAX BİLDİRİM KONTROLÜ ---
            const badge = document.getElementById('notification-badge');
            if (badge) {
                let currentCount = parseInt(badge.innerText) || 0;
                setInterval(() => {
                    fetch('{{ route('notifications.check') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.count > currentCount) {
                                badge.innerText = data.count;
                                badge.style.display = 'flex';
                                badge.classList.add('pulse-badge-animation');
                            }
                        }).catch(e => {});
                }, 30000);
            }

            // --- 5. FAVORİLER SAĞ PANEL (OFFCANVAS) & CANLI ARAMA ---
            const openFavBtn = document.getElementById('openFavoritesBtn');
            const closeFavBtn = document.getElementById('closeFavoritesBtn');
            const favDrawer = document.getElementById('favDrawer');
            const favOverlay = document.getElementById('favOverlay');
            const favDrawerBody = document.getElementById('favDrawerBody');
            const drawerSearchInput = document.getElementById('drawerFavSearch');
            const drawerSearchSpinner = document.getElementById('drawerFavSpinner');
            const editFavBtn = document.getElementById('editFavoritesBtn');

            let drawerDebounceTimer;
            let isFavEditMode = false;

            if (openFavBtn && favDrawer) {
                async function fetchDrawerFavorites(query = '') {
                    if (drawerSearchSpinner) drawerSearchSpinner.style.display = 'block';
                    favDrawerBody.style.opacity = '0.5';

                    try {
                        const url = new URL('{{ route('favorites.sidebar') }}');
                        if (query) url.searchParams.set('fav_search', query);

                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html'
                            }
                        });

                        if (!response.ok) throw new Error('Hata');

                        const html = await response.text();
                        favDrawerBody.innerHTML = html;
                        lucide.createIcons();
                        attachFavToggleEvent();
                    } catch (error) {
                        favDrawerBody.innerHTML =
                            '<div style="text-align: center; padding: 30px; color: var(--danger-color);">{{ __('Favoriler yüklenirken bir hata oluştu.') }}</div>';
                    } finally {
                        if (drawerSearchSpinner) drawerSearchSpinner.style.display = 'none';
                        favDrawerBody.style.opacity = '1';
                    }
                }

                openFavBtn.addEventListener('click', function() {
                    favOverlay.classList.add('show');
                    favDrawer.classList.add('open');

                    if (drawerSearchInput) drawerSearchInput.value = '';

                    isFavEditMode = false;
                    favDrawerBody.classList.remove('edit-mode-active');
                    if (editFavBtn) {
                        editFavBtn.innerHTML =
                            '<i data-lucide="settings-2" style="width: 16px;"></i> <span>{{ __('Düzenle') }}</span>';
                        editFavBtn.style.borderColor = 'var(--border-color)';
                        editFavBtn.style.background = 'transparent';
                    }

                    fetchDrawerFavorites();
                });

                const closeDrawer = () => {
                    favOverlay.classList.remove('show');
                    favDrawer.classList.remove('open');
                };
                closeFavBtn.addEventListener('click', closeDrawer);
                favOverlay.addEventListener('click', closeDrawer);

                if (drawerSearchInput) {
                    drawerSearchInput.addEventListener('input', function() {
                        clearTimeout(drawerDebounceTimer);
                        if (drawerSearchSpinner) drawerSearchSpinner.style.display = 'block';

                        drawerDebounceTimer = setTimeout(() => {
                            fetchDrawerFavorites(this.value);
                        }, 400);
                    });
                }

                if (editFavBtn) {
                    editFavBtn.addEventListener('click', function() {
                        isFavEditMode = !isFavEditMode;
                        if (isFavEditMode) {
                            favDrawerBody.classList.add('edit-mode-active');
                            this.innerHTML =
                                '<i data-lucide="check" style="width: 16px; color: var(--success-color);"></i> <span style="color: var(--success-color); font-weight: 600;">{{ __('Tamamla') }}</span>';
                            this.style.borderColor = 'var(--success-color)';
                            this.style.background = '#f0fdf4';
                        } else {
                            favDrawerBody.classList.remove('edit-mode-active');
                            this.innerHTML =
                                '<i data-lucide="settings-2" style="width: 16px;"></i> <span>{{ __('Düzenle') }}</span>';
                            this.style.borderColor = 'var(--border-color)';
                            this.style.background = 'transparent';
                        }
                        lucide.createIcons();
                    });
                }

                function attachFavToggleEvent() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                        '{{ csrf_token() }}';

                    favDrawerBody.querySelectorAll('.toggle-fav-btn').forEach(btn => {
                        btn.addEventListener('click', async function(e) {
                            e.preventDefault();
                            const docId = this.getAttribute('data-id');
                            const liElement = this.closest('li');

                            try {
                                const response = await fetch(
                                    `{{ url('/documents') }}/${docId}/favorite`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken,
                                            'Accept': 'application/json'
                                        }
                                    });
                                const data = await response.json();
                                if (!response.ok) throw new Error(data.message);

                                if (!data.is_favorited && liElement) {
                                    liElement.style.opacity = '0';
                                    setTimeout(() => liElement.remove(), 200);

                                    const dashRow = document.querySelector(
                                        `.content-area .toggle-fav-btn[data-id="${docId}"]`);
                                    if (dashRow && window.location.pathname.includes(
                                            'dashboard')) {
                                        dashRow.closest('li').style.display = 'none';
                                    }
                                }
                            } catch (error) {
                                console.error('{{ __('İşlem başarısız:') }}', error);
                            }
                        });
                    });

                    favDrawerBody.addEventListener('click', function(e) {
                        const noteDisplay = e.target.closest('.note-display-box');
                        const noteAddBtn = e.target.closest('.note-add-btn');

                        if (noteDisplay || noteAddBtn) {
                            const wrapper = e.target.closest('.fav-note-wrapper');
                            const inputBox = wrapper.querySelector('.note-input-box');
                            const input = wrapper.querySelector('.fav-note-input');

                            if (noteDisplay) noteDisplay.style.display = 'none';
                            if (noteAddBtn) noteAddBtn.style.display = 'none';
                            inputBox.style.display = 'block';
                            input.focus();

                            input.onblur = () => saveNote(wrapper, input);
                            input.onkeydown = (event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    input.blur();
                                }
                            };
                        }
                    });

                    async function saveNote(wrapper, input) {
                        const docId = wrapper.getAttribute('data-id');
                        const newNote = input.value.trim();
                        const noteDisplay = wrapper.querySelector('.note-display-box');
                        const noteText = wrapper.querySelector('.note-text');
                        const noteAddBtn = wrapper.querySelector('.note-add-btn');
                        const inputBox = wrapper.querySelector('.note-input-box');
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || '{{ csrf_token() }}';

                        try {
                            const response = await fetch(`{{ url('/documents') }}/${docId}/favorite-note`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    note: newNote
                                })
                            });

                            if (!response.ok) throw new Error('{{ __('Not kaydedilemedi') }}');

                            if (newNote === '') {
                                noteDisplay.style.display = 'none';
                                noteAddBtn.style.display = 'inline-flex';
                            } else {
                                noteText.textContent = newNote;
                                noteDisplay.style.display = 'flex';
                                noteAddBtn.style.display = 'none';
                            }
                            inputBox.style.display = 'none';

                        } catch (error) {
                            console.error('{{ __('İşlem başarısız:') }}', error);
                            alert('{{ __('Not kaydedilirken bir sorun oluştu.') }}');
                        }
                    }
                }
            }
        @endauth
        });
    </script>

    @stack('scripts')
    {{-- ========================================== --}}
    {{-- KÖKSAN AKILLI ASİSTAN WIDGET (PURE CSS/JS) --}}
    {{-- ========================================== --}}
    @auth
        <div id="smart-assistant-widget">
            <div id="sa-chat-window" class="sa-hidden">
                <div class="sa-header">
                    <div class="sa-header-info">
                        <div class="sa-avatar"><i data-lucide="bot"></i></div>
                        <div>
                            <h4 style="margin:0; font-size:1rem; color:var(--primary-color);">KöksanGPT</h4>
                            <span style="font-size:0.75rem; color:var(--success-color);">🟢 Çevrimiçi</span>
                        </div>
                    </div>
                    <button id="sa-close-btn"><i data-lucide="x"></i></button>
                </div>

                <div class="sa-body" id="sa-chat-body">
                    <div class="sa-msg sa-bot">
                        <div class="sa-bubble">
                            Merhaba {{ auth()->user()->name }}! Size nasıl yardımcı olabilirim? Belge yüklemek veya
                            yetkilerinizi kontrol etmek ister misiniz?
                        </div>
                    </div>
                </div>

                <div class="sa-footer">
                    <input type="text" id="sa-input" placeholder="Bir şeyler sorun..." autocomplete="off">
                    <button id="sa-send-btn"><i data-lucide="send"></i></button>
                </div>
            </div>

            <button id="sa-toggle-btn" class="sa-floating-btn">
                <i data-lucide="message-square-plus"></i>
            </button>
        </div>

        <style>
            /* WIDGET KAPSAYICI */
            #smart-assistant-widget {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 9999;
                font-family: inherit;
            }

            /* YÜZEN BUTON */
            .sa-floating-btn {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: var(--accent-color);
                color: white;
                border: none;
                cursor: pointer;
                box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
                display: flex;
                justify-content: center;
                align-items: center;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .sa-floating-btn:hover {
                transform: scale(1.1);
            }

            /* CHAT PENCERESİ (GLASSMORPHISM) */
            #sa-chat-window {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 350px;
                height: 500px;
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.4);
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                transform-origin: bottom right;
            }

            #sa-chat-window.sa-hidden {
                transform: scale(0);
                opacity: 0;
                pointer-events: none;
            }

            /* HEADER */
            .sa-header {
                padding: 15px 20px;
                background: rgba(255, 255, 255, 0.9);
                border-bottom: 1px solid var(--border-color);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .sa-header-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .sa-avatar {
                width: 40px;
                height: 40px;
                background: #eff6ff;
                color: var(--accent-color);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            #sa-close-btn {
                background: transparent;
                border: none;
                cursor: pointer;
                color: var(--text-muted);
                padding: 5px;
            }

            /* BODY (MESAJ ALANI) */
            .sa-body {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 15px;
                scroll-behavior: smooth;
            }

            .sa-body::-webkit-scrollbar {
                width: 6px;
            }

            .sa-body::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }

            /* MESAJ BALONLARI */
            .sa-msg {
                display: flex;
                flex-direction: column;
                max-width: 85%;
                animation: fadeIn 0.3s ease;
            }

            .sa-bot {
                align-self: flex-start;
            }

            .sa-user {
                align-self: flex-end;
            }

            .sa-bubble {
                padding: 12px 16px;
                border-radius: 16px;
                font-size: 0.9rem;
                line-height: 1.4;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            }

            .sa-bot .sa-bubble {
                background: #f8fafc;
                border: 1px solid var(--border-color);
                color: var(--text-color);
                border-bottom-left-radius: 4px;
            }

            .sa-user .sa-bubble {
                background: var(--success-color);
                color: white;
                border-bottom-right-radius: 4px;
            }

            /* AKSİYON BUTONU */
            .sa-action-link {
                display: inline-block;
                margin-top: 8px;
                padding: 8px 16px;
                background: var(--accent-color);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-size: 0.85rem;
                font-weight: 500;
                transition: background 0.2s;
            }

            .sa-action-link:hover {
                background: #2563eb;
                color: white;
            }

            /* YÜKLENİYOR ANİMASYONU */
            .sa-typing {
                display: flex;
                gap: 4px;
                padding: 15px;
                background: #f8fafc;
                border-radius: 16px;
                border-bottom-left-radius: 4px;
                width: fit-content;
                border: 1px solid var(--border-color);
            }

            .sa-dot {
                width: 6px;
                height: 6px;
                background: #94a3b8;
                border-radius: 50%;
                animation: sa-bounce 1.4s infinite ease-in-out both;
            }

            .sa-dot:nth-child(1) {
                animation-delay: -0.32s;
            }

            .sa-dot:nth-child(2) {
                animation-delay: -0.16s;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes sa-bounce {

                0%,
                80%,
                100% {
                    transform: scale(0);
                }

                40% {
                    transform: scale(1);
                }
            }

            /* FOOTER (INPUT) */
            .sa-footer {
                padding: 15px;
                background: rgba(255, 255, 255, 0.9);
                border-top: 1px solid var(--border-color);
                display: flex;
                gap: 10px;
            }

            #sa-input {
                flex: 1;
                padding: 12px 15px;
                border: 1px solid var(--border-color);
                border-radius: 20px;
                outline: none;
                font-size: 0.9rem;
                background: #f8fafc;
                transition: border-color 0.2s;
            }

            #sa-input:focus {
                border-color: var(--accent-color);
            }

            #sa-send-btn {
                background: var(--accent-color);
                color: white;
                border: none;
                border-radius: 50%;
                width: 42px;
                height: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: transform 0.2s;
            }

            #sa-send-btn:hover {
                transform: scale(1.05);
            }

            #sa-send-btn:disabled {
                background: #cbd5e1;
                cursor: not-allowed;
                transform: none;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggleBtn = document.getElementById('sa-toggle-btn');
                const closeBtn = document.getElementById('sa-close-btn');
                const chatWindow = document.getElementById('sa-chat-window');
                const chatBody = document.getElementById('sa-chat-body');
                const inputField = document.getElementById('sa-input');
                const sendBtn = document.getElementById('sa-send-btn');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '{{ csrf_token() }}';

                // Aç/Kapat Mantığı
                const toggleChat = () => {
                    chatWindow.classList.toggle('sa-hidden');
                    if (!chatWindow.classList.contains('sa-hidden')) {
                        setTimeout(() => inputField.focus(), 300);
                    }
                };

                toggleBtn.addEventListener('click', toggleChat);
                closeBtn.addEventListener('click', toggleChat);

                // Scroll'u en alta indirme fonksiyonu
                const scrollToBottom = () => {
                    chatBody.scrollTop = chatBody.scrollHeight;
                };

                // Mesaj Ekleme Fonksiyonu
                const appendMessage = (sender, text, link = null, linkText = null) => {
                    const msgDiv = document.createElement('div');
                    msgDiv.className = `sa-msg sa-${sender}`;

                    let html = `<div class="sa-bubble">${text}</div>`;

                    // Eğer asistansa ve link varsa buton ekle
                    if (sender === 'bot' && link) {
                        html += `<a href="${link}" class="sa-action-link" target="_self">${linkText}</a>`;
                    }

                    msgDiv.innerHTML = html;
                    chatBody.appendChild(msgDiv);
                    scrollToBottom();
                };

                // Yazıyor (Loading) Göstergesi
                const showTyping = () => {
                    const typingDiv = document.createElement('div');
                    typingDiv.className = 'sa-msg sa-bot sa-typing-indicator';
                    typingDiv.innerHTML =
                        `<div class="sa-typing"><div class="sa-dot"></div><div class="sa-dot"></div><div class="sa-dot"></div></div>`;
                    chatBody.appendChild(typingDiv);
                    scrollToBottom();
                    return typingDiv;
                };

                // AJAX Fetch İşlemi
                const sendMessage = async () => {
                    const message = inputField.value.trim();
                    if (!message) return;

                    // Kullanıcı mesajını ekle ve inputu temizle
                    appendMessage('user', message);
                    inputField.value = '';
                    inputField.disabled = true;
                    sendBtn.disabled = true;

                    // Yükleniyor efekti
                    const typingIndicator = showTyping();

                    try {
                        const response = await fetch('{{ route('assistant.chat') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                message: message
                            })
                        });

                        if (!response.ok) throw new Error('Sunucu hatası');

                        const data = await response.json();

                        // Yükleniyor efektini sil ve cevabı ekle (İnsansı olması için 500ms gecikme)
                        setTimeout(() => {
                            typingIndicator.remove();
                            appendMessage('bot', data.reply, data.link, data.link_text);
                            lucide.createIcons(); // Varsa yeni ikonları renderla
                        }, 600);

                    } catch (error) {
                        typingIndicator.remove();
                        appendMessage('bot',
                            'Üzgünüm, şu anda bağlantı kuramıyorum. Lütfen daha sonra tekrar deneyin.');
                    } finally {
                        inputField.disabled = false;
                        sendBtn.disabled = false;
                        inputField.focus();
                    }
                };

                // Tıklama ve Enter Tuşu Eventleri
                sendBtn.addEventListener('click', sendMessage);
                inputField.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            });
        </script>
    @endauth
</body>

</html>
