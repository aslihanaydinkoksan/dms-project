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

        /* BİLDİRİM VE ÇAN ANİMASYON STİLLERİ */
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

        /* YENİ: Çan Yanıp Sönme ve Sallanma Animasyonu */
        @keyframes bell-glow-shake {
            0% { filter: drop-shadow(0 0 0 rgba(239, 68, 68, 0)); color: var(--text-color); transform: rotate(0deg); }
            10% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); }
            30% { transform: rotate(5deg); }
            40% { transform: rotate(-5deg); }
            50% { 
                filter: drop-shadow(0 0 8px rgba(239, 68, 68, 0.8)); 
                color: var(--danger-color); 
                transform: rotate(0deg) scale(1.1); 
            }
            100% { filter: drop-shadow(0 0 0 rgba(239, 68, 68, 0)); color: var(--text-color); transform: rotate(0deg); }
        }

        .notification-btn.has-unread svg {
            animation: bell-glow-shake 2s infinite ease-in-out;
            fill: rgba(239, 68, 68, 0.1); /* Hafif kırmızı iç dolgu */
        }

        .pulse-badge-animation {
            animation: super-pulse 1s ease-in-out infinite;
        }

        @keyframes super-pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50% { transform: scale(1.3); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
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

        .notification-item:hover { background: var(--bg-color); }
        .notification-item.unread { background: #f0fdf4; }
        .notif-icon { margin-right: 12px; color: var(--accent-color); }
        .notif-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 3px; }
        .notif-desc { font-size: 0.8rem; color: var(--text-muted); line-height: 1.3; margin-bottom: 5px; }
        .notif-time { font-size: 0.75rem; color: #94a3b8; }

        body.guest-mode .app-container {
            display: flex; justify-content: center; align-items: center; min-height: 100vh; background: var(--bg-color);
        }
        body.guest-mode .sidebar, body.guest-mode .topbar { display: none !important; }
        body.guest-mode .main-content { margin-left: 0 !important; width: 100%; display: flex; justify-content: center; align-items: center; padding: 0; }
        body.guest-mode .content-area { width: 100%; max-width: 1200px; }

        /* --- FAVORİLER SAĞ PANEL --- */
        .favorites-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
            z-index: 1040; opacity: 0; visibility: hidden; transition: all 0.3s ease;
        }
        .favorites-overlay.show { opacity: 1; visibility: visible; }
        .favorites-drawer {
            position: fixed; top: 0; right: -400px; width: 400px; max-width: 100%;
            height: 100vh; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.1); z-index: 1050; display: flex;
            flex-direction: column; transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid var(--border-color);
        }
        .favorites-drawer.open { right: 0; }
        .drawer-header { padding: 20px; border-bottom: 1px solid var(--border-color); background: #f8fafc; display: flex; flex-direction: column; gap: 15px; }
        .drawer-body { padding: 0; overflow-y: auto; flex-grow: 1; position: relative; }

        @keyframes spin { from { transform: translateY(-50%) rotate(0deg); } to { transform: translateY(-50%) rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; }

        /* FAVORİLER DÜZENLE MODU */
        #favDrawerBody.edit-mode-active a[href*="/documents/"] { pointer-events: none; opacity: 0.4; }
        #favDrawerBody.edit-mode-active a.btn-outline-primary { display: none !important; }
        #favDrawerBody.edit-mode-active .toggle-fav-btn { border-color: var(--danger-color) !important; background: #fef2f2 !important; animation: pulse-red 1.5s infinite; }
        #favDrawerBody.edit-mode-active .toggle-fav-btn svg { color: var(--danger-color) !important; fill: rgba(239, 68, 68, 0.2) !important; }

        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

        @media (max-width: 768px) { .brand-text { display: none; } }

        .app-container { display: flex; min-height: 100vh; width: 100%; margin: 0; padding: 0; gap: 0 !important; }
        .modern-sidebar {
            width: 260px; background-color: #111827; border-right: 1px solid #374151;
            padding: 1.5rem 1rem; height: 100vh; overflow-y: auto; font-family: 'Inter', system-ui, sans-serif;
            position: sticky; top: 0; flex-shrink: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .main-content {
            flex: 1; min-width: 0; margin-left: 0 !important; padding: 0;
            display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html.sidebar-collapsed .modern-sidebar { width: 0 !important; padding-left: 0 !important; padding-right: 0 !important; border-right: none; overflow: hidden; }

        .sidebar-nav { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.25rem; }
        .nav-section { font-size: 0.75rem; font-weight: 700; color: #9ca3af; letter-spacing: 0.05em; margin-top: 1.5rem; margin-bottom: 0.5rem; padding-left: 0.75rem; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.75rem; color: #e5e7eb; text-decoration: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease-in-out; }
        .nav-icon { width: 1.25rem; height: 1.25rem; color: #9ca3af; transition: color 0.2s; }
        .nav-link:hover { background-color: #1f2937; color: #ffffff; transform: translateX(3px); }
        .nav-link:hover .nav-icon { color: #ffffff; }
        .nav-link.active { background-color: #374151; color: #60a5fa; font-weight: 600; }
        .nav-link.active .nav-icon { color: #60a5fa; }
        .nav-item.has-submenu { flex-direction: column; align-items: stretch; }
        .nav-submenu { list-style: none; padding: 0; margin: 0; display: none; padding-left: 2rem; margin-top: 0.25rem; }
        .nav-submenu.open { display: flex; flex-direction: column; gap: 0.25rem; animation: slideDown 0.2s ease-out forwards; }
        .submenu-icon { width: 1rem; height: 1rem; margin-left: auto; transition: transform 0.2s ease; }
        .nav-group-toggle.open .submenu-icon { transform: rotate(180deg); }

        .nav-link-special { border: 1px dashed rgba(139, 92, 246, 0.4); background: transparent !important; transition: all 0.3s ease; }
        .nav-link-special .nav-icon { color: #8b5cf6; }
        .nav-link-special:hover { background: rgba(139, 92, 246, 0.1) !important; border-color: rgba(139, 92, 246, 0.6); color: #ffffff; }
        .nav-link-special:hover .nav-icon { color: #a78bfa; }
        .nav-link-special.active { background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%) !important; color: #ffffff; border: 1px solid transparent; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4); }
        .nav-link-special.active .nav-icon { color: #ffffff; }
    </style>
</head>

<body class="{{ auth()->guest() ? 'guest-mode' : '' }}">

    <div class="app-container">
        @auth
            <aside class="modern-sidebar">
                <ul class="sidebar-nav">
                    @can('menu.dashboard')
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i data-lucide="layout-dashboard" class="nav-icon"></i>
                                <span class="nav-text">{{ __('Gösterge Paneli') }}</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['menu.documents', 'menu.folders', 'menu.tasks', 'menu.tasks_archive'])
                        <li class="nav-section">{{ __('DOKÜMANLAR & SÜREÇLER') }}</li>
                    @endcanany

                    @can('menu.documents')
                        <li class="nav-item">
                            <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.index', 'documents.show') ? 'active' : '' }}">
                                <i data-lucide="folder-search" class="nav-icon"></i>
                                <span class="nav-text">{{ __('Tüm Belgeler') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.create') }}" class="nav-link {{ request()->routeIs('documents.create') ? 'active' : '' }}">
                                <i data-lucide="upload-cloud" class="nav-icon"></i>
                                <span class="nav-text">{{ __('Yeni Belge Yükle') }}</span>
                            </a>
                        </li>
                    @endcan

                    @can('menu.folders')
                        <li class="nav-item">
                            <a href="{{ route('folders.index') }}" class="nav-link {{ request()->routeIs('folders.*') ? 'active' : '' }}">
                                <i data-lucide="folder-tree" class="nav-icon"></i>
                                <span class="nav-text">{{ __('Klasörler') }}</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['menu.tasks', 'menu.tasks_archive'])
                        <li class="nav-item has-submenu">
                            <a href="#" class="nav-link nav-group-toggle {{ request()->routeIs('tasks.*') ? 'active open' : '' }}">
                                <i data-lucide="kanban" class="nav-icon"></i>
                                <span class="nav-text">{{ __('Süreçler') }}</span>
                                <i data-lucide="chevron-down" class="submenu-icon"></i>
                            </a>
                            <ul class="nav-submenu {{ request()->routeIs('tasks.*') ? 'open' : '' }}">
                                @can('menu.tasks')
                                    <li><a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.index') ? 'active' : '' }}"><span class="nav-text">{{ __('Süreç Panosu') }}</span></a></li>
                                    <li><a href="{{ route('tasks.create') }}" class="nav-link {{ request()->routeIs('tasks.create') ? 'active' : '' }}"><span class="nav-text">{{ __('Yeni Süreç Başlat') }}</span></a></li>
                                @endcan
                                @can('menu.tasks_archive')
                                    <li><a href="{{ route('tasks.archive') }}" class="nav-link {{ request()->routeIs('tasks.archive') ? 'active' : '' }}"><span class="nav-text">{{ __('Süreç Arşivi') }}</span></a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    @canany(['menu.analytics', 'menu.users', 'menu.settings', 'menu.process_templates'])
                        <li class="nav-section">{{ __('SİSTEM YÖNETİMİ') }}</li>

                        @can('menu.analytics')
                            <li class="nav-item">
                                <a href="{{ route('analytics.index') }}" class="nav-link nav-link-special {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                                    <i data-lucide="pie-chart" class="nav-icon"></i>
                                    <span class="nav-text">{{ __('Sistem Analitiği') }}</span>
                                </a>
                            </li>
                        @endcan

                        @canany(['menu.users', 'menu.user_groups'])
                            <li class="nav-item has-submenu">
                                <a href="#" class="nav-link nav-group-toggle {{ request()->routeIs('users.*', 'settings.user-groups.*') ? 'active open' : '' }}">
                                    <i data-lucide="users" class="nav-icon"></i>
                                    <span class="nav-text">{{ __('Kullanıcı İşlemleri') }}</span>
                                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                                </a>
                                <ul class="nav-submenu {{ request()->routeIs('users.*', 'settings.user-groups.*') ? 'open' : '' }}">
                                    @can('menu.users')
                                        <li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}"><span class="nav-text">{{ __('Kullanıcı Listesi') }}</span></a></li>
                                        <li><a href="{{ route('users.onay_bekleyenler') }}" class="nav-link {{ request()->routeIs('users.onay_bekleyenler') ? 'active' : '' }}"><span class="nav-text">{{ __('Onay Bekleyenler') }}</span></a></li>
                                    @endcan
                                    @can('menu.user_groups')
                                        <li><a href="{{ route('settings.user-groups.index') }}" class="nav-link {{ request()->routeIs('settings.user-groups.*') ? 'active' : '' }}"><span class="nav-text">{{ __('Zorunlu Gruplar') }}</span></a></li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany

                        @can('menu.settings')
                            <li class="nav-item has-submenu">
                                @php
                                    $isSettingsActive = request()->routeIs('settings.*') || request()->routeIs('admin.*');
                                    $isUserGroup = request()->routeIs('settings.user-groups.*');
                                @endphp
                                <a href="#" class="nav-link nav-group-toggle {{ $isSettingsActive && !$isUserGroup ? 'active open' : '' }}">
                                    <i data-lucide="settings" class="nav-icon"></i>
                                    <span class="nav-text">{{ __('Sistem Ayarları') }}</span>
                                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                                </a>
                                <ul class="nav-submenu {{ $isSettingsActive && !$isUserGroup ? 'open' : '' }}">
                                    <li><a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}"><i data-lucide="building-2" class="nav-icon" style="width: 14px;"></i> <span class="nav-text">{{ __('Tesis ve Departmanlar') }}</span></a></li>
                                    <li><a href="{{ route('admin.document-types.index') }}" class="nav-link {{ request()->routeIs('admin.document-types.*') ? 'active' : '' }}"><i data-lucide="file-type" class="nav-icon" style="width: 14px;"></i> <span class="nav-text">{{ __('Doküman Tipleri') }}</span></a></li>
                                    <li><a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"><i data-lucide="shield" class="nav-icon" style="width: 14px;"></i> <span class="nav-text">{{ __('Roller ve Yetkiler') }}</span></a></li>
                                    <li><a href="{{ route('settings.notifications') }}" class="nav-link {{ request()->routeIs('settings.notifications') ? 'active' : '' }}"><i data-lucide="bell" class="nav-icon" style="width: 14px;"></i> <span class="nav-text">{{ __('Otomatik Raporlar') }}</span></a></li>
                                    <li><a href="{{ route('settings.mail') }}" class="nav-link {{ request()->routeIs('settings.mail') ? 'active' : '' }}"><i data-lucide="mail" class="nav-icon" style="width: 14px;"></i> <span class="nav-text">{{ __('Mail Şablonları') }}</span></a></li>
                                    <li><a href="{{ route('settings.intents.index') }}" class="nav-link {{ request()->routeIs('settings.intents.*') ? 'active' : '' }}"><i data-lucide="bot" class="nav-icon" style="width: 14px;"></i> <span class="nav-text">{{ __('Asistan Eğitimi') }}</span></a></li>
                                    {{-- <li><a href="{{ route('settings.permissions') }}" class="nav-link {{ request()->routeIs('settings.permissions') ? 'active' : '' }}"><i data-lucide="server" class="nav-icon" style="width: 14px;"></i> <span class="nav-text" style="color:#f87171;">{{ __('Eski Matris (Legacy)') }}</span></a></li> --}}
                                </ul>
                            </li>
                        @endcan

                        @can('menu.process_templates')
                            <li class="nav-item">
                                <a href="{{ route('process-templates.index') }}" class="nav-link {{ request()->routeIs('process-templates.*') ? 'active' : '' }}">
                                    <i data-lucide="layout-template" class="nav-icon"></i>
                                    <span class="nav-text">{{ __('Süreç Şablonları') }}</span>
                                </a>
                            </li>
                        @endcan

                        @if (auth()->user()->hasRole('Super Admin'))
                            <li class="nav-item">
                                <a href="{{ route('system.logs.index') }}" class="nav-link {{ request()->routeIs('system.logs.index') ? 'active' : '' }}">
                                    <i data-lucide="shield-alert" class="nav-icon"></i>
                                    <span class="nav-text">{{ __('Kanıt ve Log Merkezi') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('settings.permissions.explorer') }}" class="nav-link {{ request()->routeIs('settings.permissions.explorer') ? 'active' : '' }}">
                                    <i data-lucide="book-user" class="nav-icon"></i>
                                    <span class="nav-text">{{ __('Kullanıcı Yetki Röntgeni') }}</span>
                                </a>
                            </li>
                        @endif
                    @endcanany
                </ul>
            </aside>
        @endauth

        <main class="main-content">
            @auth
                <header class="topbar">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <button type="button" id="sidebarToggleBtn" class="hamburger-btn" title="{{ __('Gösterge Panelini Aç/Kapat') }}">
                            <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                        </button>

                        <a href="{{ route('dashboard') }}" class="brand-logo" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--primary-color); font-weight: 800; font-size: 1.2rem; letter-spacing: 0.5px; transition: opacity 0.2s; background: transparent !important; padding: 0 !important; margin: 0 !important;">
                            <i data-lucide="layers" style="width: 26px; height: 26px; color: var(--accent-color);"></i>
                            <span class="brand-text">KÖKSAN DMS</span>
                        </a>

                        <div class="search-bar-mini"></div>
                    </div>

                    <div class="header-actions flex-between" style="gap: 20px;">
                        <button type="button" id="openFavoritesBtn" class="notification-btn" title="{{ __('Favorilerim') }}" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; transition: background 0.2s;">
                            <i data-lucide="star" style="color: var(--warning-color); fill: rgba(245, 158, 11, 0.2);"></i>
                        </button>

                        <div class="notification-wrapper" style="position: relative;">
                            {{-- YENİ: ÇAN İKONU (Eğer okunmamış varsa has-unread class'ı alır) --}}
                            @php
                                $unreadCount = auth()->user()->unreadNotifications->count();
                            @endphp
                            <button id="notificationBtn" class="notification-btn {{ $unreadCount > 0 ? 'has-unread' : '' }}" style="background: none; border: none; cursor: pointer; color: var(--text-color); position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; transition: background 0.2s;">
                                <i data-lucide="bell"></i>
                                <span id="notification-badge" class="notification-badge" style="{{ $unreadCount == 0 ? 'display: none;' : '' }}">
                                    {{ $unreadCount }}
                                </span>
                            </button>

                            <div id="notificationDropdown" class="notification-dropdown glass-card">
                                <div class="dropdown-header flex-between">
                                    <h4 style="margin: 0; font-size: 1rem;">{{ __('Bildirimler') }}</h4>
                                    <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-primary" style="background: none; border: none; cursor: pointer; font-size: 0.8rem; color: var(--accent-color); font-weight: 500;">
                                            {{ __('Tümünü Okundu İşaretle') }}
                                        </button>
                                    </form>
                                </div>
                                <div class="dropdown-body">
                                    @forelse(auth()->user()->notifications()->limit(5)->get() as $notification)
                                        <a href="{{ route('notifications.read', $notification->id) }}" class="notification-item {{ is_null($notification->read_at) ? 'unread' : '' }}" style="text-decoration: none; color: inherit;">
                                            <div class="notif-icon"><i data-lucide="{{ $notification->data['icon'] ?? 'info' }}"></i></div>
                                            <div class="notification-content">
                                                <div class="notif-title">{{ __($notification->data['title'] ?? 'Bildirim') }}</div>
                                                <div class="notif-desc">{{ __($notification->data['message'] ?? '') }}</div>
                                                <div class="notif-time">{{ $notification->created_at->diffForHumans() }}</div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="text-center p-20 text-muted" style="padding: 20px;">
                                            <div style="display: flex; justify-content: center; margin-bottom: 10px;">
                                                <i data-lucide="mail-open" style="width: 32px; height: 32px; opacity: 0.5;"></i>
                                            </div>
                                            {{ __('Henüz bir bildiriminiz yok.') }}
                                        </div>
                                    @endforelse
                                </div>
                                <div class="dropdown-footer flex-between" style="padding: 12px 15px; border-top: 1px solid var(--border-color); background: var(--bg-color);">
                                    <a href="{{ route('notifications.history') }}" style="font-size: 0.85rem; color: var(--accent-color); font-weight: 600; text-decoration: none;">{{ __('Tümünü Gör') }}</a>
                                    <a href="{{ route('profile.notifications') }}" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="settings" style="width: 14px; height: 14px;"></i> {{ __('Ayarlar') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="lang-dropdown-container" style="position: relative; display: inline-block;">
                            <button type="button" id="langDropdownBtn" style="background: transparent; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-color); transition: all 0.2s;">
                                <i data-lucide="globe" style="width: 18px; color: var(--primary-color);"></i>
                                <span style="font-weight: 500; font-size: 0.85rem; text-transform: uppercase;">{{ app()->getLocale() }}</span>
                                <i data-lucide="chevron-down" style="width: 14px; color: var(--text-muted);"></i>
                            </button>
                            <div id="langDropdownMenu" style="display: none; position: absolute; right: 0; top: 110%; background: #fff; min-width: 150px; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid var(--border-color); z-index: 1000; overflow: hidden; transform: translateY(-10px); opacity: 0; transition: all 0.2s ease;">
                                <a href="{{ route('language.switch', 'tr') }}" style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-color); font-size: 0.9rem; transition: background 0.2s; {{ app()->getLocale() == 'tr' ? 'background: #f8fafc; font-weight: bold; color: var(--primary-color);' : '' }}">
                                    <span style="font-size: 1.1rem;">🇹🇷</span> Türkçe @if (app()->getLocale() == 'tr') <i data-lucide="check" style="width: 14px; margin-left: auto;"></i> @endif
                                </a>
                                <a href="{{ route('language.switch', 'en') }}" style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; text-decoration: none; color: var(--text-color); font-size: 0.9rem; transition: background 0.2s; border-top: 1px solid #f1f5f9; {{ app()->getLocale() == 'en' ? 'background: #f8fafc; font-weight: bold; color: var(--primary-color);' : '' }}">
                                    <span style="font-size: 1.1rem;">🇬🇧</span> English @if (app()->getLocale() == 'en') <i data-lucide="check" style="width: 14px; margin-left: auto;"></i> @endif
                                </a>
                            </div>
                        </div>

                        <div class="user-dropdown-container relative-container" style="position: relative;">
                            <button id="userDropdownBtn" class="btn btn-outline-primary" style="border-radius: 30px; padding: 6px 16px; border-color: var(--border-color); background: var(--bg-color);">
                                <i data-lucide="user-circle" style="width: 18px; height: 18px; color: var(--text-muted);"></i>
                                <span style="font-weight: 600; margin: 0 6px;">{{ auth()->user()->name }}</span>
                                <i data-lucide="chevron-down" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                            </button>
                            <div id="userDropdownMenu" class="dropdown-menu glass-card" style="display: none; position: absolute; top: 110%; right: 0; min-width: 220px; z-index: 1000; padding: 8px; border: 1px solid var(--border-color); border-radius: 12px;">
                                <a href="https://kys.koksan.com/merkezi_yonetim_sistemi/profile" target="_blank" style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="user-cog" style="width: 18px; height: 18px;"></i> {{ __('Profilimi Düzenle (KYS)') }}
                                </a>
                                <a href="{{ route('profile.vault.security') }}" style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="shield-alert" style="width: 18px; height: 18px; color: #dc2626;"></i> {{ __('Özel Kasa Şifresi Belirle') }}
                                </a>
                                <a href="{{ route('profile.show') }}" style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="chart-area" style="width: 18px; height: 18px;"></i> {{ __('Performansımı İncele') }}
                                </a>
                                <a href="{{ route('profile.delegations') }}" style="display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="users" style="width: 18px; height: 18px;"></i> {{ __('Vekalet İşlemleri') }}
                                </a>
                                <form action="{{ route('logout') }}" method="POST" style="margin: 0; padding-top: 8px;">
                                    @csrf
                                    <button type="submit" style="width: 100%; display: flex; align-items: center; gap: 10px; background: none; border: none; padding: 12px; color: var(--danger-color); cursor: pointer; font-size: 0.95rem; font-weight: 500; border-radius: 6px; transition: background 0.2s;">
                                        <i data-lucide="log-out" style="width: 18px; height: 18px;"></i> {{ __('Çıkış Yap') }}
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

    @auth
        <div class="favorites-overlay" id="favOverlay"></div>
        <div class="favorites-drawer" id="favDrawer">
            <div class="drawer-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="star" style="fill: var(--warning-color); color: var(--warning-color);"></i>
                        {{ __('Favorilerim') }}
                    </h3>
                    <button type="button" id="closeFavoritesBtn" style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding: 5px;">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-primary" style="flex: 1; display: flex; justify-content: center; align-items: center; gap: 6px; text-decoration: none; border-radius: 8px;">
                        <i data-lucide="plus-circle" style="width: 16px;"></i> {{ __('Yeni Ekle') }}
                    </a>
                    <button type="button" id="editFavoritesBtn" class="btn btn-sm btn-outline-secondary" style="flex: 1; display: flex; justify-content: center; align-items: center; gap: 6px; border-radius: 8px; transition: all 0.2s;">
                        <i data-lucide="settings-2" style="width: 16px;"></i> <span>{{ __('Düzenle') }}</span>
                    </button>
                </div>
                <div style="position: relative; margin-top: 15px;">
                    <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-muted);"></i>
                    <input type="text" id="drawerFavSearch" placeholder="{{ __('Favorilerde ara...') }}" style="width: 100%; padding: 10px 35px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; outline: none; font-size: 0.9rem;">
                    <i data-lucide="loader" id="drawerFavSpinner" class="spin" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--warning-color); display: none;"></i>
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
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');

            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    document.documentElement.classList.toggle('sidebar-collapsed');
                    const isNowCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', isNowCollapsed);
                });
            }

            @auth
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

            const userBtn = document.getElementById('userDropdownBtn');
            const userMenu = document.getElementById('userDropdownMenu');

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.style.display = (userMenu.style.display === 'none' || userMenu.style.display === '') ? 'block' : 'none';
                });
            }

            const notifBtn = document.getElementById('notificationBtn');
            const notifDropdown = document.getElementById('notificationDropdown');

            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('show');
                    
                    // YENİ EKLENDİ: Menü açıldığında dikkati dağıtmaması için animasyonu durdur
                    if (notifDropdown.classList.contains('show')) {
                        notifBtn.classList.remove('has-unread');
                    } else {
                        // Menü kapanınca, eğer hala okunmamış bildirim varsa animasyonu geri getir
                        const badge = document.getElementById('notification-badge');
                        if (badge && badge.style.display !== 'none' && parseInt(badge.innerText) > 0) {
                            notifBtn.classList.add('has-unread');
                        }
                    }
                });
            }

            window.addEventListener('click', function(e) {
                if (langMenu && !langMenu.contains(e.target)) {
                    langMenu.style.transform = 'translateY(-10px)';
                    langMenu.style.opacity = '0';
                    setTimeout(() => {
                        langMenu.style.display = 'none';
                    }, 200);
                }
                if (userMenu) userMenu.style.display = 'none';
                
                if (notifDropdown && notifDropdown.classList.contains('show') && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.remove('show');
                    // Dışarı tıklanıp menü kapandığında okunmamış varsa animasyonu geri başlat
                    const badge = document.getElementById('notification-badge');
                    if (badge && badge.style.display !== 'none' && parseInt(badge.innerText) > 0) {
                        notifBtn.classList.add('has-unread');
                    }
                }
            });

            const badge = document.getElementById('notification-badge');
            if (badge && notifBtn) {
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
                            if (data.count > 0) {
                                badge.innerText = data.count;
                                badge.style.display = 'flex';
                                if (data.count > currentCount) {
                                    badge.classList.add('pulse-badge-animation');
                                }
                                // YENİ EKLENDİ: Arkada yeni bildirim gelirse çanı tekrar sallandır
                                if (!notifDropdown.classList.contains('show')) {
                                    notifBtn.classList.add('has-unread'); 
                                }
                            } else {
                                badge.style.display = 'none';
                                notifBtn.classList.remove('has-unread');
                            }
                            currentCount = data.count;
                        }).catch(e => {});
                }, 30000);
            }

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
                        favDrawerBody.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--danger-color);">{{ __('Favoriler yüklenirken bir hata oluştu.') }}</div>';
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
                        editFavBtn.innerHTML = '<i data-lucide="settings-2" style="width: 16px;"></i> <span>{{ __('Düzenle') }}</span>';
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
                            this.innerHTML = '<i data-lucide="check" style="width: 16px; color: var(--success-color);"></i> <span style="color: var(--success-color); font-weight: 600;">{{ __('Tamamla') }}</span>';
                            this.style.borderColor = 'var(--success-color)';
                            this.style.background = '#f0fdf4';
                        } else {
                            favDrawerBody.classList.remove('edit-mode-active');
                            this.innerHTML = '<i data-lucide="settings-2" style="width: 16px;"></i> <span>{{ __('Düzenle') }}</span>';
                            this.style.borderColor = 'var(--border-color)';
                            this.style.background = 'transparent';
                        }
                        lucide.createIcons();
                    });
                }

                function attachFavToggleEvent() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                    favDrawerBody.querySelectorAll('.toggle-fav-btn').forEach(btn => {
                        btn.addEventListener('click', async function(e) {
                            e.preventDefault();
                            const docId = this.getAttribute('data-id');
                            const liElement = this.closest('li');

                            try {
                                const response = await fetch(`{{ url('/documents') }}/${docId}/favorite`, {
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

                                    const dashRow = document.querySelector(`.content-area .toggle-fav-btn[data-id="${docId}"]`);
                                    if (dashRow && window.location.pathname.includes('dashboard')) {
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
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                        try {
                            const response = await fetch(`{{ url('/documents') }}/${docId}/favorite-note`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ note: newNote })
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

            const groupToggles = document.querySelectorAll('.nav-group-toggle');
            groupToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault(); 
                    const parentLi = this.parentElement;
                    const submenu = parentLi.querySelector('.nav-submenu');
                    const isOpen = submenu.classList.contains('open');

                    if (isOpen) {
                        submenu.classList.remove('open');
                        this.classList.remove('open');
                    } else {
                        submenu.classList.add('open');
                        this.classList.add('open');
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>