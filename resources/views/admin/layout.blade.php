<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>MAVII Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite(['resources/css/app.css', 'resources/css/notification.css', 'resources/js/app.js', 'resources/js/notification.js'])

</head>
<body>
    <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 100000;"></div>

<div class="app">
    <div class="mobile-overlay" id="mobileOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="{{ asset('assets/image/logo.png') }}" alt="Logo">
            <div class="logo-text">
                <small>Manajemen Asisten Visual<br>Infrastruktur Internet</small>
            </div>
        </div>

        <nav class="menu">
            <a href="{{ route('admin.main-page') }}" class="{{ request()->routeIs('admin.main-page') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i>
                Main Page
            </a>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-door-fill"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.tracking') }}" class="{{ request()->routeIs('admin.tracking') ? 'active' : '' }}">
                <i class="bi bi-broadcast"></i>
                Tracking Monitoring
            </a>
            <a href="{{ route('admin.history') }}" class="{{ request()->routeIs('admin.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                History
            </a>
            <a href="{{ route('admin.tasks') }}" class="{{ request()->routeIs('admin.tasks') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i>
                Task Management
            </a>
            <a href="{{ route('admin.technicians') }}" class="{{ request()->routeIs('admin.technicians') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i>
                Technician Management
            </a>
            <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i>
                Profile
            </a>
        </nav>

        <div class="logout-menu">
            <a href="#" onclick="confirmLogout(event)">
                <i class="bi bi-box-arrow-right"></i>
                Log out
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left" style="display: flex; align-items: center;">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="bi bi-list"></i>
                </button>
                @stack('topbar_left')
            </div>
            <div class="top-user">

                {{-- NOTIFIKASI --}}
                @include('admin.notification')

                <span class="divider-line"></span>
                <div class="avatar">
                    @php
                        $userSess = session('user');
                        $sessAvatar = is_array($userSess) ? ($userSess['avatar'] ?? '') : ($userSess->avatar ?? '');
                        $finalSessAvatar = '';
                        
                        if(!empty($sessAvatar)) {
                            if(Str::startsWith($sessAvatar, 'http')) {
                                $finalSessAvatar = $sessAvatar;
                            } else {
                                $purePath = ltrim(str_replace('storage/', '', ltrim($sessAvatar, '/')), '/');
                                $finalSessAvatar = rtrim(env('VITE_API_BASE_URL'), '/') . '/storage/' . $purePath;
                            }
                        }
                    @endphp
                    @if($finalSessAvatar)
                        <img src="{{ $finalSessAvatar }}" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="avatar-icon-default" style="display:none;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    @else
                        <div class="avatar-icon-default">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    @endif
                </div>
                <div class="welcome">
                    <span class="greet">Welcome,</span>
                    <span class="user-name">{{ Str::limit(is_array(session('user')) ? (session('user')['name'] ?? 'Admin FSMS') : (session('user')->name ?? 'Admin FSMS'), 50, '...') }}</span>
                </div>
            </div>
        </header>

        <section class="content">
            @yield('content')
        </section>
    </main>

</div>

<div id="logoutModal">
    <div class="logout-modal-box">
        <div class="logout-modal-icon">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        <h3 class="logout-modal-title">Konfirmasi Logout</h3>
        <p class="logout-modal-desc">Apakah kamu yakin ingin keluar dari aplikasi?</p>
        <div class="logout-modal-actions">
            <button class="logout-btn-cancel" onclick="closeLogout()">Batal</button>
            <button class="logout-btn-confirm" onclick="document.getElementById('logout-form').submit()">Ya, Logout</button>
        </div>
    </div>
</div>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => window.showToast("{{ session('success') }}", 'success'), 100);
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => window.showToast("{{ session('error') }}", 'error'), 100);
            });
        </script>
    @endif

@stack('scripts')
</body>
</html>