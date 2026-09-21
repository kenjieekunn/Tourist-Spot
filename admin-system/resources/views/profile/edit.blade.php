@extends('layouts.app')

@section('title', 'Settings')
@section('header', 'Settings')

@section('content')
    <style>
        .settings-page {
            width: min(100%, 1100px);
            margin: 0 auto;
        }
        .settings-intro { color: #6b7280; margin-bottom: 1.25rem; }
        .settings-tabs { border-bottom: 1px solid #dee2e6; margin-bottom: 1.5rem; }
        .settings-tabs .nav-link { color: #4b5563; font-weight: 600; }
        .settings-tabs .nav-link.active { color: #164e63; border-bottom: 3px solid #164e63; }
        .readonly-field { background: #f3f4f6; color: #4b5563; }
        .settings-close {
            color: #6b7280;
            font-size: 1.25rem;
            text-decoration: none;
        }
        .settings-close:hover { color: #111827; }
    </style>

    <div class="settings-page">
        <div class="d-flex justify-content-between align-items-start">
            <p class="settings-intro">Manage your account and system preferences.</p>
            <a href="{{ $user->isSuperAdmin() ? route('super-admin.dashboard') : route('municipality-admin.dashboard') }}" class="settings-close" aria-label="Close settings" title="Close settings">
                <i class="fas fa-times"></i>
            </a>
        </div>

        <ul class="nav settings-tabs" aria-label="Settings sections">
            @if($user->isSuperAdmin())
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('section', 'profile') === 'profile' ? 'active' : '' }}" href="{{ route('profile.edit', ['section' => 'profile']) }}">
                        <i class="fas fa-user"></i> Super Admin Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('section') === 'admins' ? 'active' : '' }}" href="{{ route('profile.edit', ['section' => 'admins']) }}">
                        <i class="fas fa-users-cog"></i> Admin Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('section') === 'preferences' ? 'active' : '' }}" href="{{ route('profile.edit', ['section' => 'preferences']) }}">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('section', 'profile') === 'profile' ? 'active' : '' }}" href="{{ route('profile.edit', ['section' => 'profile']) }}">
                        <i class="fas fa-user"></i> Admin Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('section') === 'account' ? 'active' : '' }}" href="{{ route('profile.edit', ['section' => 'account']) }}">
                        <i class="fas fa-lock"></i> Account Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('section') === 'preferences' ? 'active' : '' }}" href="{{ route('profile.edit', ['section' => 'preferences']) }}">
                        <i class="fas fa-sliders"></i> Preferences
                    </a>
                </li>
            @endif
        </ul>

        @if($user->isSuperAdmin() && request()->query('section') === 'admins')
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Admin Management</h5>
                        <small class="text-muted">Manage municipal administrators without leaving Settings.</small>
                    </div>
                    <a href="{{ route('super-admin.admins.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Admin</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light"><tr><th>Name</th><th>Municipality</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($managedAdmins as $admin)
                                <tr>
                                    <td><strong>{{ $admin->name }}</strong><br><small class="text-muted">{{ $admin->email }}</small></td>
                                    <td>{{ $admin->municipality->name ?? 'Unassigned' }}</td>
                                    <td>Municipal Admin</td>
                                    <td><span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-danger' }}">{{ $admin->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end"><a href="{{ route('super-admin.admins.edit', $admin) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i> Edit</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No municipal admins found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif(request()->query('section') === 'account')
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Account Settings</h5>
                    <small class="text-muted">Manage your account security.</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="account">

                        <div class="col-md-6">
                            <label for="account_username" class="form-label">Username</label>
                            <input type="text" class="form-control readonly-field" id="account_username" value="{{ $hasUsernameColumn ? $user->username : $user->email }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="account_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control readonly-field" id="account_email" value="{{ $user->email }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="account_status" class="form-label">Account Status</label>
                            <input type="text" class="form-control readonly-field" id="account_status" value="{{ $user->is_active ? 'Active' : 'Inactive' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" minlength="8" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Last login: Not recorded in the current account data.</small>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{ route('profile.edit', ['section' => 'profile']) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif(request()->query('section') === 'preferences')
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">System Preferences</h5>
                    <small class="text-muted">{{ $user->isSuperAdmin() ? 'Choose which system events generate notifications.' : 'Control notifications and display preferences for this admin browser.' }}</small>
                </div>
                <div class="card-body">
                    <form id="preferences-form" class="row g-4">
                        <div class="col-12 col-lg-6">
                            <h6 class="mb-3">Notifications</h6>
                            @if($user->isSuperAdmin())
                                @foreach([
                                    'notifySpotSubmission' => 'New Tourist Spot Submission',
                                    'notifyPendingVerification' => 'Tourist Spot Pending Verification',
                                    'notifyTouristReview' => 'New Tourist Review',
                                    'notifyReportedSpot' => 'Reported Tourist Spot',
                                    'notifyReportedReview' => 'Reported Review',
                                    'notifyAdminActivity' => 'New Admin Account Activity',
                                ] as $preference => $label)
                                    <div class="form-check mb-3">
                                        <input class="form-check-input preference-notification" type="checkbox" id="{{ $preference }}" data-preference="{{ $preference }}" checked>
                                        <label class="form-check-label" for="{{ $preference }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            @else
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-notification" type="checkbox" id="notify-submissions" data-preference="notifySubmissions" checked>
                                    <label class="form-check-label" for="notify-submissions">New tourist spot submissions</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-notification" type="checkbox" id="notify-verification" data-preference="notifyVerification" checked>
                                    <label class="form-check-label" for="notify-verification">Verification updates</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-notification" type="checkbox" id="notify-reviews" data-preference="notifyReviews" checked>
                                    <label class="form-check-label" for="notify-reviews">New tourist reviews</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-notification" type="checkbox" id="notify-announcements" data-preference="notifyAnnouncements">
                                    <label class="form-check-label" for="notify-announcements">System announcements</label>
                                </div>
                            @endif
                        </div>
                        <div class="col-12 col-lg-6">
                            <h6 class="mb-3">Display</h6>
                            <fieldset>
                                <legend class="form-label">Theme</legend>
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-theme" type="radio" name="theme" id="theme-light" value="light">
                                    <label class="form-check-label" for="theme-light">Light</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-theme" type="radio" name="theme" id="theme-dark" value="dark">
                                    <label class="form-check-label" for="theme-dark">Dark</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input preference-theme" type="radio" name="theme" id="theme-system" value="system">
                                    <label class="form-check-label" for="theme-system">System Default</label>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{ route('profile.edit', ['section' => 'profile']) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
        <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3" data-profile-preview>
                        @if($user->profile_image_url)
                            <img
                                src="{{ $user->profile_image_url }}"
                                alt="{{ $user->name }} profile photo"
                                class="rounded-circle border"
                                style="width: 140px; height: 140px; object-fit: cover;"
                            >
                        @else
                            <div
                                class="rounded-circle d-inline-flex align-items-center justify-content-center border"
                                style="width: 140px; height: 140px; background: linear-gradient(135deg, #ff6b35, #ff9f68); color: #fff; font-size: 3rem;"
                            >
                                <i class="fas fa-user"></i>
                            </div>
                        @endif
                    </div>

                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-3">{{ $user->isSuperAdmin() ? 'Super Admin' : 'Municipal Tourism Officer' }}</p>

                    <div class="text-start">
                        <div class="mb-2">
                            <small class="text-muted d-block">Email</small>
                            <strong>{{ $user->email }}</strong>
                        </div>
                        @unless($user->isSuperAdmin())
                            <div class="mb-2">
                                <small class="text-muted d-block">Municipality</small>
                                <strong>{{ $user->municipality->name ?? 'Not assigned' }}</strong>
                            </div>
                        @endunless
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                            <h5 class="mb-0">{{ $user->isSuperAdmin() ? 'Super Admin Profile' : 'Admin Profile' }}</h5>
                            <small class="text-muted">Update your profile photo and personal information.</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="profile">

                        <div class="col-12">
                            <label for="name" class="form-label">Name</label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                required
                            >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control readonly-field" id="username" value="{{ $hasUsernameColumn ? $user->username : $user->email }}" readonly>
                            <div class="form-text">Username is managed by the Super Admin.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label">Position / Role</label>
                            <input type="text" class="form-control readonly-field" id="role" value="{{ $user->isSuperAdmin() ? 'Super Admin' : 'Municipal Tourism Officer' }}" readonly>
                        </div>

                        @unless($user->isSuperAdmin())
                            <div class="col-md-6">
                                <label for="municipality" class="form-label">Municipality</label>
                                <input type="text" class="form-control readonly-field" id="municipality" value="{{ $user->municipality->name ?? 'Not assigned' }}" readonly>
                            </div>
                        @endunless

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control readonly-field" id="email" value="{{ $user->email }}" readonly>
                        </div>

                        @unless($user->isSuperAdmin())
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control readonly-field" id="contact_number" value="Not provided" readonly>
                                <div class="form-text">Contact number storage is not enabled for admin accounts.</div>
                            </div>
                        @endunless

                        <div class="col-md-6">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input
                                type="file"
                                class="form-control @error('profile_image') is-invalid @enderror"
                                id="profile_image"
                                name="profile_image"
                                accept="image/*"
                            >
                            <div class="form-text">JPG, PNG, or WEBP up to 2MB.</div>
                            @error('profile_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary"
                                onclick="window.location.href = '{{ $user->isSuperAdmin() ? route('super-admin.dashboard') : route('municipality-admin.dashboard') }}'"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const preferenceKey = 'touristSpotAdminPreferences';
            const storedPreferences = JSON.parse(localStorage.getItem(preferenceKey) || '{}');
            const preferenceForm = document.getElementById('preferences-form');
            const themeInputs = document.querySelectorAll('.preference-theme');

            document.querySelectorAll('.preference-notification').forEach(function (input) {
                const preferenceName = input.dataset.preference;
                input.checked = storedPreferences[preferenceName] ?? input.checked;
            });

            const selectedTheme = storedPreferences.theme || 'system';
            themeInputs.forEach(function (input) {
                input.checked = input.value === selectedTheme;
            });

            if (preferenceForm) {
                preferenceForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const preferences = { theme: document.querySelector('.preference-theme:checked')?.value || 'system' };
                    document.querySelectorAll('.preference-notification').forEach(function (input) {
                        preferences[input.dataset.preference] = input.checked;
                    });
                    localStorage.setItem(preferenceKey, JSON.stringify(preferences));
                    applyTheme(preferences.theme);
                    alert('{{ $user->isSuperAdmin() ? 'Notification preferences' : 'Preferences' }} saved successfully.');
                });
            }

            function applyTheme(theme) {
                const useDarkTheme = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.adminTheme = useDarkTheme ? 'dark' : 'light';
            }

            applyTheme(selectedTheme);

            const fileInput = document.getElementById('profile_image');
            const previewContainer = document.querySelector('[data-profile-preview]');

            if (!fileInput || !previewContainer) {
                return;
            }

            fileInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    previewContainer.innerHTML = '<img src="' + event.target.result + '" alt="Preview" class="rounded-circle border" style="width: 140px; height: 140px; object-fit: cover;">';
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endsection
