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
            --sidebar-width: 260px;
            --sidebar-gap: 24px;
        }
        .sidebar {
            background-color: #173f43;
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
            transform: translateX(0);
            transition: transform 0.3s ease;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            min-height: calc(100vh - 80px);
        }
        .main-col {
            display: flex;
            flex-direction: column;
            padding: 0;
            flex: 1;
            margin-left: var(--sidebar-width);
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1020;
            background: rgba(15, 23, 42, 0.4);
        }
        .sidebar-toggle {
            width: 2.5rem;
            height: 2.5rem;
            border: 1px solid #d9dde3;
            border-radius: 0.5rem;
            background: #fff;
            color: #2c3e50;
            display: none;
        }
        .sidebar-toggle:hover {
            background: #f3f4f6;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            body.sidebar-open .sidebar {
                transform: translateX(0);
            }
            body.sidebar-open .sidebar-overlay {
                display: block;
            }
            .main-col {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .main-col,
            .main-content {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }
        .sidebar .nav-link {
            color: #bbb;
            padding: 1rem 1.5rem;
            border-left: 4px solid transparent;
            min-height: 3.5rem;
            display: flex;
            align-items: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            border-left-color: #2dd4bf;
        }
        .sidebar .nav-link i { width: 1.25rem; margin-right: .35rem; color: #99f6e4; }
        .sidebar .nav-link.active i,
        .sidebar .nav-link:hover i { color: #fff; }
        .sidebar .municipality-nav-link {
            border-left-color: transparent;
            border-left-width: 4px;
            border-left-style: solid;
        }
        .sidebar .municipality-nav-link:hover,
        .sidebar .municipality-nav-link.active {
            color: #fff;
        }
        .sidebar .brand {
            padding: 1.5rem;
            background-color: #1a252f;
            border-bottom: 1px solid #444;
            font-size: 1.5rem;
            font-weight: bold;
            color: #5eead4;
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
                rgba(23, 63, 67, 0.98),
                rgba(23, 63, 67, 0)
            );
        }
        @if(request()->routeIs('super-admin.*', 'municipality-admin.*', 'tourist_spots.*', 'municipalities.*'))
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
            color: #99f6e4;
        }
        .sidebar-account { border-top: 1px solid rgba(153, 246, 228, .22); padding: .9rem 0 1rem; color: #e6fffb; }
        .sidebar-account-icon { width: 2.35rem; height: 2.35rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #2dd4bf; color: #134e4a; }
        .sidebar-account-role { color: #99f6e4; font-size: .72rem; }
        .sidebar-version { color: #8bb8b3; font-size: .7rem; letter-spacing: .04em; }
        @media (max-width: 768px) {
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
                            : ($currentUser->isMunicipalityStaff() ? 'Staff Panel' : 'Admin Panel'));
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
                        <a class="nav-link district-municipalities-tab @if(request()->routeIs('municipalities.*')) active @endif" href="{{ route('municipalities.index') }}" title="2nd District Municipalities">
                            <i class="fas fa-building"></i> 2nd District Municipalities
                        </a>
                        <a class="nav-link @if(request()->routeIs('super-admin.admins*')) active @endif" href="{{ route('super-admin.admins') }}">
                            <i class="fas fa-user-shield"></i> Municipality Admins
                        </a>
                    @elseif(auth()->user()->belongsToMunicipalityTeam())
                        <!-- Municipality Admin Navigation -->
                        <a class="nav-link municipality-nav-link dashboard-tab @if(Route::currentRouteName() == 'municipality-admin.dashboard') active @endif" href="{{ route('municipality-admin.dashboard') }}">
                            <i class="fas fa-dashboard"></i> Dashboard
                        </a>
                        @if(auth()->user()->hasPermission('manage_spots'))
                            <a class="nav-link municipality-nav-link spots-tab @if(Route::currentRouteName() == 'municipality-admin.tourist-spots') active @endif" href="{{ route('municipality-admin.tourist-spots') }}">
                                <i class="fas fa-map-location-dot"></i> Tourist Spots
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('view_reports'))
                            <a class="nav-link municipality-nav-link reports-tab @if(Route::currentRouteName() == 'municipality-admin.reports') active @endif" href="{{ route('municipality-admin.reports') }}">
                                <i class="fas fa-chart-column"></i> Reports
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('manage_reviews'))
                            <a class="nav-link municipality-nav-link reviews-tab @if(Route::currentRouteName() == 'municipality-admin.reviews') active @endif" href="{{ route('municipality-admin.reviews') }}">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        @endif
                        @if(auth()->user()->isMunicipalityAdmin() && auth()->user()->hasPermission('manage_staff'))
                            <a class="nav-link municipality-nav-link @if(Route::currentRouteName() == 'municipality-admin.staff') active @endif" href="{{ route('municipality-admin.staff') }}">
                                <i class="fas fa-users"></i> Staff Accounts
                            </a>
                        @endif
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
                    <div class="sidebar-account d-flex align-items-center gap-2">
                        <span class="sidebar-account-icon"><i class="fas fa-user-tie"></i></span>
                        <div class="min-w-0">
                            <strong class="d-block text-truncate">{{ $currentUser->name ?: 'Provincial Tourism Office' }}</strong>
                        </div>
                    </div>
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
            <div class="sidebar-overlay" data-sidebar-overlay></div>

            <!-- Main Content -->
            <div class="main-col">
                <div class="navbar-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open menu" title="Open menu">
                                <i class="fas fa-bars"></i>
                            </button>
                            @if(trim($__env->yieldContent('header', 'Dashboard')) !== '')
                                <h4 class="m-0">@yield('header', 'Dashboard')</h4>
                            @endif
                        </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('[data-sidebar-toggle]');
            const overlay = document.querySelector('[data-sidebar-overlay]');

            if (!sidebar || !toggle || !overlay) return;

            sidebar.id = 'admin-sidebar';

            function setSidebarOpen(isOpen) {
                document.body.classList.toggle('sidebar-open', isOpen);
                toggle.setAttribute('aria-expanded', String(isOpen));
                toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
                toggle.title = isOpen ? 'Close menu' : 'Open menu';
                toggle.innerHTML = `<i class="fas fa-${isOpen ? 'times' : 'bars'}"></i>`;
            }

            toggle.addEventListener('click', function () {
                setSidebarOpen(!document.body.classList.contains('sidebar-open'));
            });
            overlay.addEventListener('click', function () { setSidebarOpen(false); });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') setSidebarOpen(false);
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
