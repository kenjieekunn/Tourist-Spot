    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tourist Spot Admin')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        (function () {
            const preferences = JSON.parse(localStorage.getItem('touristSpotAdminPreferences') || '{}');
            const theme = preferences.theme || 'system';
            const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.adminTheme = dark ? 'dark' : 'light';
        })();
    </script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        html[data-admin-theme="dark"] body { background-color: #111827; color: #e5e7eb; }
        html[data-admin-theme="dark"] .navbar-custom,
        html[data-admin-theme="dark"] .card,
        html[data-admin-theme="dark"] .card-header,
        html[data-admin-theme="dark"] .official-report { background-color: #1f2937 !important; color: #e5e7eb; }
        html[data-admin-theme="dark"] .text-muted,
        html[data-admin-theme="dark"] .form-text { color: #cbd5e1 !important; }
        html[data-admin-theme="dark"] .form-control,
        html[data-admin-theme="dark"] .form-select,
        html[data-admin-theme="dark"] .readonly-field { background-color: #374151; border-color: #4b5563; color: #f9fafb; }
        html[data-admin-theme="dark"] .table { --bs-table-color: #e5e7eb; --bs-table-bg: #1f2937; --bs-table-border-color: #4b5563; }
        html[data-admin-theme="dark"] .bg-light { background-color: #374151 !important; color: #f9fafb !important; }
        :root {
            --sidebar-width: 350px;
            --sidebar-gap: 40px;
        }
        .sidebar {
            background-color: #2c3e50;
            min-height: 100vh;
            color: white;
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            z-index: 1030;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .main-content {
            flex: 1;
            padding: 2rem 2rem 2rem 0;
            min-height: calc(100vh - 80px);
        }
        .main-col {
            display: flex;
            flex-direction: column;
            padding: 0;
            padding-left: calc(var(--sidebar-width) + var(--sidebar-gap));
            flex: 1;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .main-col,
            .main-content {
                padding-left: 1rem !important;
            }
        }
        .sidebar .nav-link {
            color: #bbb;
            padding: 1rem 1.5rem;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            border-left-color: #ff6b35;
        }
        .sidebar .municipality-nav-link {
            border-left-color: transparent;
            border-left-width: 4px;
            border-left-style: solid;
        }
        .sidebar .municipality-nav-link:hover,
        .sidebar .municipality-nav-link.active {
            color: #fff;
        }
        .sidebar .municipality-nav-link.dashboard-tab:hover,
        .sidebar .municipality-nav-link.dashboard-tab.active {
            background-color: rgba(20, 184, 166, 0.24);
            border-left-color: #2dd4bf;
        }
        .sidebar .municipality-nav-link.spots-tab:hover,
        .sidebar .municipality-nav-link.spots-tab.active {
            background-color: rgba(59, 130, 246, 0.26);
            border-left-color: #60a5fa;
        }
        .sidebar .municipality-nav-link.reports-tab:hover,
        .sidebar .municipality-nav-link.reports-tab.active {
            background-color: rgba(245, 158, 11, 0.25);
            border-left-color: #fbbf24;
        }
        .sidebar .municipality-nav-link.reviews-tab:hover,
        .sidebar .municipality-nav-link.reviews-tab.active {
            background-color: rgba(236, 72, 153, 0.23);
            border-left-color: #f472b6;
        }
        .sidebar .municipality-nav-link.dashboard-tab i { color: #5eead4; }
        .sidebar .municipality-nav-link.spots-tab i { color: #93c5fd; }
        .sidebar .municipality-nav-link.reports-tab i { color: #fcd34d; }
        .sidebar .municipality-nav-link.reviews-tab i { color: #f9a8d4; }
        .sidebar .settings-heading {
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            margin: 1.15rem 1.1rem 0.35rem;
            text-transform: uppercase;
        }
        .sidebar .settings-dropdown {
            margin-top: 0.35rem;
        }
        .sidebar .settings-dropdown summary {
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 1rem;
            font-weight: 400;
            list-style: none;
            margin: 0;
            padding: 0.85rem 1.1rem;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .settings-dropdown summary:hover,
        .sidebar .settings-dropdown[open] summary {
            background-color: rgba(148, 163, 184, 0.16);
            border-left-color: #cbd5e1;
            color: #fff;
        }
        .sidebar .settings-dropdown summary i {
            width: 1.1rem;
            text-align: center;
        }
        .sidebar .settings-dropdown summary::-webkit-details-marker {
            display: none;
        }
        .sidebar .settings-link {
            color: #cbd5e1;
            font-size: 0.9rem;
            padding: 0.65rem 1.1rem 0.65rem 2.15rem;
        }
        .sidebar .settings-link:hover,
        .sidebar .settings-link.active {
            background-color: rgba(148, 163, 184, 0.16);
            color: #fff;
        }
        .sidebar .brand {
            padding: 1.5rem;
            background-color: #1a252f;
            border-bottom: 1px solid #444;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff6b35;
            text-align: center;
        }
        .sidebar-nav {
            padding-bottom: 5rem;
        }
        .sidebar-logout {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            padding: 0 1.5rem 1.25rem;
            background: linear-gradient(
                to top,
                rgba(44, 62, 80, 0.95),
                rgba(44, 62, 80, 0)
            );
        }
        @if(request()->routeIs('municipalities.edit', 'municipalities.show', 'profile.edit', 'tourist_spots.create', 'tourist_spots.show', 'tourist_spots.edit', 'super-admin.admins.edit', 'super-admin.admins.create'))
        .sidebar {
            display: none;
        }
        .main-col {
            padding-left: 1in;
        }
        @endif
        @if(request()->routeIs('super-admin.*', 'municipality-admin.*'))
        :root {
            --sidebar-width: 260px;
            --sidebar-gap: 24px;
        }
        .sidebar .nav-link {
            padding: 0.85rem 1.1rem;
        }
        .sidebar .brand {
            padding: 1.25rem;
            font-size: 1.25rem;
        }
        @endif
/* Removed duplicate main-content padding */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #ff6b35;
            border-color: #ff6b35;
        }
        .btn-primary:hover {
            background-color: #e55a2b;
            border-color: #e55a2b;
        }
        .stat-card {
            text-align: center;
            padding: 2rem;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6b35;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .navbar-custom {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        .profile-menu {
            min-width: 220px;
        }
        .profile-trigger {
            border: 1px solid rgba(255, 107, 53, 0.2);
            background: linear-gradient(135deg, #fff, #fff7f3);
            color: #1f2d3d;
            border-radius: 999px;
            padding: 0.35rem 0.75rem 0.35rem 0.4rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .profile-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(255, 107, 53, 0.12);
        }
        .profile-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid rgba(255, 107, 53, 0.2);
            background: #fff;
        }
        .profile-fallback {
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b35, #ff9f68);
            color: #fff;
            font-size: 0.95rem;
            border: 2px solid rgba(255, 255, 255, 0.9);
        }
        .sidebar-profile {
            color: #fff;
            font-size: 0.8rem;
            text-decoration: none;
            white-space: nowrap;
        }
        .sidebar-profile:hover {
            color: #ffb08f;
        }
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-col,
            .main-content {
                padding: 1rem !important;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div style="display: flex; height: 100vh;">
        <div class="row g-0" style="flex: 1; display: flex;">
            <!-- Sidebar -->
            <div class="sidebar">
                @php
                    $currentUser = auth()->user();
                    $sidebarLabel = $currentUser->isSuperAdmin()
                        ? 'Super Admin Console'
                        : ($currentUser->isMunicipalityAdmin() && $currentUser->municipality
                            ? $currentUser->municipality->name . ' Admin Console'
                            : 'Admin Panel');
                @endphp
                <div class="brand">
                    <span class="d-block"><i class="fas fa-map-marker-alt"></i> Pangasinan 2nd District</span>
                    @unless($currentUser->isSuperAdmin() || $currentUser->isMunicipalityAdmin())
                        <div class="small text-uppercase mt-1" style="color: #ffb08f; letter-spacing: 0.08em;">{{ $sidebarLabel }}</div>
                    @endunless
                </div>
                <nav class="nav flex-column sidebar-nav">
                    @if(auth()->user()->isSuperAdmin())
                        <!-- Super Admin Navigation -->
                        <a class="nav-link @if(Route::currentRouteName() == 'super-admin.dashboard') active @endif" href="{{ route('super-admin.dashboard') }}">
                            <i class="fas fa-dashboard"></i> Dashboard
                        </a>
                        <a class="nav-link @if(Route::currentRouteName() == 'super-admin.tourist-spots') active @endif" href="{{ route('super-admin.tourist-spots') }}">
                            <i class="fas fa-check-circle"></i> Spots Verification
                        </a>
                        <a class="nav-link @if(Route::currentRouteName() == 'super-admin.reports') active @endif" href="{{ route('super-admin.reports') }}">
                            <i class="fas fa-chart-column"></i> Reports
                        </a>
                        <details class="settings-dropdown" @if(request()->routeIs('profile.edit')) open @endif>
                            <summary><span>Settings</span></summary>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && !request()->get('section')) active @endif" href="{{ route('profile.edit', ['section' => 'profile']) }}">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && request()->get('section') === 'admins') active @endif" href="{{ route('profile.edit', ['section' => 'admins']) }}">
                                <i class="fas fa-users-cog"></i> Admin Management
                            </a>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && request()->get('section') === 'preferences') active @endif" href="{{ route('profile.edit', ['section' => 'preferences']) }}">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && request()->get('section') === 'preferences') active @endif" href="{{ route('profile.edit', ['section' => 'preferences']) }}">
                                <i class="fas fa-sliders"></i> System Preferences
                            </a>
                        </details>
                    @elseif(auth()->user()->isMunicipalityAdmin())
                        <!-- Municipality Admin Navigation -->
                        <a class="nav-link municipality-nav-link dashboard-tab @if(Route::currentRouteName() == 'municipality-admin.dashboard') active @endif" href="{{ route('municipality-admin.dashboard') }}">
                            <i class="fas fa-dashboard"></i> Dashboard
                        </a>
                        <a class="nav-link municipality-nav-link spots-tab @if(Route::currentRouteName() == 'municipality-admin.tourist-spots') active @endif" href="{{ route('municipality-admin.tourist-spots') }}">
                            <i class="fas fa-map-location-dot"></i> Tourist Spots
                        </a>
                        <a class="nav-link municipality-nav-link reports-tab @if(Route::currentRouteName() == 'municipality-admin.reports') active @endif" href="{{ route('municipality-admin.reports') }}">
                            <i class="fas fa-chart-column"></i> Reports
                        </a>
                        <a class="nav-link municipality-nav-link reviews-tab @if(Route::currentRouteName() == 'municipality-admin.reviews') active @endif" href="{{ route('municipality-admin.reviews') }}">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                        <details class="settings-dropdown" @if(request()->routeIs('profile.edit')) open @endif>
                            <summary><span>Settings</span></summary>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && !request()->get('section')) active @endif" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user"></i> Admin Profile
                            </a>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && request()->get('section') === 'account') active @endif" href="{{ route('profile.edit', ['section' => 'account']) }}">
                                <i class="fas fa-user-gear"></i> Account Settings
                            </a>
                            <a class="nav-link settings-link @if(Route::currentRouteName() == 'profile.edit' && request()->get('section') === 'preferences') active @endif" href="{{ route('profile.edit', ['section' => 'preferences']) }}">
                                <i class="fas fa-sliders"></i> System Preferences
                            </a>
                        </details>
                    @else
                        <!-- Default Navigation -->
                        <a class="nav-link @if(Route::currentRouteName() == 'dashboard') active @endif" href="{{ route('dashboard') }}">
                            <i class="fas fa-dashboard"></i> Dashboard
                        </a>
                        <a class="nav-link @if(Route::currentRouteName() == 'tourist_spots.index') active @endif" href="{{ route('tourist_spots.index') }}">
                            <i class="fas fa-map-location-dot"></i> Tourist Spots
                        </a>
                        <a class="nav-link @if(Route::currentRouteName() == 'reviews.index') active @endif" href="{{ route('reviews.index') }}">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                    @endif
                </nav>
                <div class="sidebar-logout">
                    <hr style="border-color: #555;">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-col">
                <div class="navbar-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="m-0">@yield('header', 'Dashboard')</h4>
                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            @yield('header_actions')
                        </div>
                    </div>
                    @hasSection('header_bottom')
                        <div class="mt-3">
                            @yield('header_bottom')
                        </div>
                    @endif
                </div>

                <div class="main-content">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    @yield('scripts')
</body>
</html>
