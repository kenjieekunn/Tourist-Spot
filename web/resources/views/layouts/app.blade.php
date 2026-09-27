@php
    $pageUser = auth()->user();
    $currentRouteName = Route::currentRouteName();
    $isSuperAdmin = $pageUser?->isSuperAdmin() ?? false;
    $routeTitles = [
        'dashboard' => 'Dashboard',
        'super-admin.dashboard' => 'Dashboard',
        'super-admin.tourist-spots' => 'All Tourist Spots',
        'super-admin.reports' => 'Reports',
        'super-admin.admins' => 'Municipality Admins',
        'super-admin.admins.create' => 'Add Municipality Admin',
        'municipality-admin.dashboard' => 'Dashboard',
        'municipality-admin.tourist-spots' => 'Tourist Spots',
        'municipality-admin.reports' => 'Reports',
        'municipality-admin.reviews' => 'Reviews',
        'municipality-admin.staff' => 'Staff Accounts',
        'municipality-admin.staff.create' => 'Add Staff Account',
        'municipality-admin.staff.edit' => 'Edit Staff Account',
        'municipality-admin.info' => 'Municipality Information',
        'tourist_spots.index' => 'Tourist Spots',
        'tourist_spots.create' => 'Add Tourist Spot',
        'tourist_spots.show' => 'Tourist Spot Details',
        'tourist_spots.edit' => 'Edit Tourist Spot',
        'municipalities.create' => 'Add Municipality',
        'municipalities.show' => isset($municipality) ? $municipality->name . ' Tourist Spots' : 'Municipality Tourist Spots',
        'municipalities.edit' => 'Edit Municipality',
        'reviews.index' => 'Reviews',
    ];
    $pageTitle = trim($__env->yieldContent('header', ''));
    if ($pageTitle === '') {
        $pageTitle = $routeTitles[$currentRouteName] ?? trim($__env->yieldContent('title', 'Dashboard'));
        $pageTitle = preg_replace('/\s+-\s+(Super Admin|Municipality Admin)$/', '', $pageTitle);
    }
@endphp
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
            .navbar-custom { padding: 1rem; }
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
            padding: 1rem 2rem;
        }
        .admin-header-actions { min-width: 0; }
        @media (max-width: 767.98px) {
            .admin-header-row { flex-wrap: wrap; row-gap: .75rem; }
            .admin-header-actions { width: 100%; justify-content: flex-end; }
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
        .super-admin-profile-menu { position: relative; display: flex; align-items: center; gap: .6rem; }
        .super-admin-profile-trigger { flex: 0 0 auto; padding: .15rem; border: 0; border-radius: 50%; background: transparent; color: #e6fffb; cursor: pointer; }
        .super-admin-profile-name { max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .super-admin-profile-trigger:hover,
        .super-admin-profile-trigger:focus-visible { outline: 3px solid #99f6e4; outline-offset: 3px; }
        .super-admin-profile-actions { display: none; position: absolute; z-index: 1040; bottom: calc(100% + .65rem); left: 0; width: max-content; min-width: 12rem; max-width: calc(100vw - 2rem); padding: .35rem; border: 1px solid #d9dde3; border-radius: .5rem; background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.18); }
        .super-admin-profile-actions::after { position: absolute; right: 1rem; bottom: -.4rem; width: .7rem; height: .7rem; border-right: 1px solid #d9dde3; border-bottom: 1px solid #d9dde3; background: #fff; content: ''; transform: rotate(45deg); }
        .super-admin-profile-actions form { margin: 0; }
        .super-admin-profile-action { align-items: center; border: 0; display: flex; gap: .55rem; padding: .55rem .65rem; text-align: left; }
        .super-admin-profile-action:hover,
        .super-admin-profile-action:focus-visible { background: #f1f5f4; }
        .super-admin-profile-action.profile-logout-button { color: #dc3545; }
        .super-admin-profile-action.profile-logout-button:hover,
        .super-admin-profile-action.profile-logout-button:focus { color: #b02a37; }
        .super-admin-profile-menu.is-open .super-admin-profile-actions { display: block; }
        .sidebar-version { color: #8bb8b3; font-size: .7rem; letter-spacing: .04em; }
        @media (max-width: 768px) {
            .main-col,
            .main-content {
                padding: 1rem !important;
            }
        }
        body.modal-embedded { height: auto; min-height: 100vh; overflow-y: auto; }
        body.modal-embedded > div { height: auto !important; min-height: 100vh; }
        body.modal-embedded .sidebar,
        body.modal-embedded .sidebar-overlay,
        body.modal-embedded .navbar-custom { display: none !important; }
        body.modal-embedded .main-col { width: 100%; margin-left: 0 !important; }
        body.modal-embedded .main-content { min-height: 0; padding: 1rem !important; }
    </style>
    @yield('styles')
</head>
<body class="{{ request()->boolean('modal') ? 'modal-embedded' : '' }}">
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
                        <a class="nav-link @if(request()->routeIs('super-admin.admins*')) active @endif" href="{{ route('super-admin.admins') }}">
                            <i class="fas fa-user-shield"></i> Municipality Admins
                        </a>
                        <a class="nav-link @if(Route::currentRouteName() == 'super-admin.tourist-spots') active @endif" href="{{ route('super-admin.tourist-spots') }}">
                            <i class="fas fa-map-location-dot"></i> All Tourist Spots
                        </a>
                        <a class="nav-link @if(Route::currentRouteName() == 'super-admin.reports') active @endif" href="{{ route('super-admin.reports') }}">
                            <i class="fas fa-chart-column"></i> Reports
                        </a>
                    @elseif(auth()->user()->belongsToMunicipalityTeam())
                        <!-- Municipality Admin Navigation -->
                        <a class="nav-link municipality-nav-link dashboard-tab @if(Route::currentRouteName() == 'municipality-admin.dashboard') active @endif" href="{{ route('municipality-admin.dashboard') }}">
                            <i class="fas fa-dashboard"></i> Dashboard
                        </a>
                        @if(auth()->user()->isMunicipalityAdmin() && auth()->user()->hasPermission('manage_staff'))
                            <a class="nav-link municipality-nav-link @if(Route::currentRouteName() == 'municipality-admin.staff') active @endif" href="{{ route('municipality-admin.staff') }}">
                                <i class="fas fa-users"></i> Staff Accounts
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('manage_spots'))
                            <a class="nav-link municipality-nav-link spots-tab @if(Route::currentRouteName() == 'municipality-admin.tourist-spots') active @endif" href="{{ route('municipality-admin.tourist-spots') }}">
                                <i class="fas fa-map-location-dot"></i> Tourist Spots
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('manage_reviews'))
                            <a class="nav-link municipality-nav-link reviews-tab @if(Route::currentRouteName() == 'municipality-admin.reviews') active @endif" href="{{ route('municipality-admin.reviews') }}">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('view_reports'))
                            <a class="nav-link municipality-nav-link reports-tab @if(Route::currentRouteName() == 'municipality-admin.reports') active @endif" href="{{ route('municipality-admin.reports') }}">
                                <i class="fas fa-chart-column"></i> Reports
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
                    <div class="sidebar-account">
                        <div class="super-admin-profile-menu" data-profile-menu>
                            <button type="button" class="super-admin-profile-trigger d-flex align-items-center" data-profile-toggle aria-expanded="false" aria-haspopup="true" aria-controls="superAdminProfileActions" aria-label="Open admin profile menu">
                                <span class="sidebar-account-icon"><i class="fas fa-user-tie" aria-hidden="true"></i></span>
                            </button>
                            <strong class="super-admin-profile-name">{{ $currentUser->name ?: 'Provincial Tourism Office' }}</strong>
                            <div class="super-admin-profile-actions" id="superAdminProfileActions">
                                @if($currentUser->isSuperAdmin() || $currentUser->isMunicipalityAdmin())
                                    <button type="button" class="btn w-100 super-admin-profile-action" data-profile-close data-bs-toggle="modal" data-bs-target="#changeOwnPasswordModal" title="Change password"><i class="fas fa-key" aria-hidden="true"></i> Change Password</button>
                                @endif
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn w-100 super-admin-profile-action profile-logout-button" title="Log out"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if($currentUser->isSuperAdmin() || $currentUser->isMunicipalityAdmin())
                <div class="modal fade" id="changeOwnPasswordModal" tabindex="-1" aria-labelledby="changeOwnPasswordModalLabel" aria-hidden="true" data-open-on-load="{{ $errors->any() && old('_password_change') ? 'true' : 'false' }}">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="changeOwnPasswordModalLabel">Change Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('account.password.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_password_change" value="1">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="currentAccountPassword" class="form-label">Current password</label>
                                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="currentAccountPassword" name="current_password" autocomplete="current-password" required>
                                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="newAccountPassword" class="form-label">New password</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="newAccountPassword" name="password" minlength="8" autocomplete="new-password" required>
                                        <div class="form-text">Use at least 8 characters, including an uppercase letter and a symbol.</div>
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div>
                                        <label for="confirmAccountPassword" class="form-label">Confirm new password</label>
                                        <input type="password" class="form-control" id="confirmAccountPassword" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                                    </div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update Password</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
            <div class="sidebar-overlay" data-sidebar-overlay></div>

            <!-- Main Content -->
            <div class="main-col">
                <div class="navbar-custom">
                        <div class="admin-header-row d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open menu" title="Open menu">
                                <i class="fas fa-bars"></i>
                            </button>
                            <h4 class="m-0">{{ $pageTitle }}</h4>
                        </div>
                        <div class="admin-header-actions d-flex align-items-center gap-2 flex-wrap justify-content-end">
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
            const profileMenu = document.querySelector('[data-profile-menu]');
            const profileToggle = document.querySelector('[data-profile-toggle]');
            const changePasswordModal = document.getElementById('changeOwnPasswordModal');

            if (changePasswordModal?.dataset.openOnLoad === 'true') {
                bootstrap.Modal.getOrCreateInstance(changePasswordModal).show();
            }

            if (profileMenu && profileToggle) {
                const setProfileOpen = function (isOpen) {
                    profileMenu.classList.toggle('is-open', isOpen);
                    profileToggle.setAttribute('aria-expanded', String(isOpen));
                };

                profileToggle.addEventListener('click', function () {
                    setProfileOpen(!profileMenu.classList.contains('is-open'));
                });
                profileMenu.addEventListener('click', function (event) {
                    if (event.target.closest('[data-profile-close]')) setProfileOpen(false);
                });
                document.addEventListener('click', function (event) {
                    if (!profileMenu.contains(event.target)) setProfileOpen(false);
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') setProfileOpen(false);
                });
            }

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
