@extends('layouts.app')

@section('title', 'Edit Municipality Admin - Super Admin')
@section('header', '')

@section('content')
@php
    $returnTo = url()->previous();
    if (!is_string($returnTo) || $returnTo === '') {
        $returnTo = route('super-admin.admins');
    } else {
        $isLocal = str_starts_with($returnTo, url('/'));
        $isSafeRoute = str_starts_with($returnTo, route('super-admin.admins'));

        if (! $isLocal && ! $isSafeRoute) {
            $returnTo = route('super-admin.admins');
        }
    }

    $municipalityName = $admin->municipality->name ?? 'N/A';
    $staffLimit = (int) old('max_staff_accounts', $admin->max_staff_accounts ?? 5);
    $staffUsed = (int) ($staffAccountsUsed ?? 0);
    $staffProgress = $staffLimit > 0 ? min(100, (int) round(($staffUsed / $staffLimit) * 100)) : 0;
@endphp

<style>
    .admin-edit-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .admin-edit-page .breadcrumb { --bs-breadcrumb-divider: '>'; font-size: .88rem; }
    .admin-edit-page .breadcrumb a { color: var(--tourism-teal); text-decoration: none; }
    .admin-edit-page .breadcrumb-bar { align-items: center; display: flex; gap: 1rem; justify-content: space-between; }
    .admin-edit-page .breadcrumb-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
    .admin-edit-page .btn-tourism { background: #0f766e; border-color: #0f766e; box-shadow: 0 3px 8px rgba(15, 118, 110, .24); color: #fff; font-weight: 600; }
    .admin-edit-page .btn-tourism:hover, .admin-edit-page .btn-tourism:focus { background: #115e59; border-color: #115e59; box-shadow: 0 4px 10px rgba(15, 118, 110, .32); color: #fff; }
    .admin-edit-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-edit-page .card-header { color: var(--tourism-ink); border-bottom-color: #dce9e5; }
    .admin-edit-page .section-label { color: var(--tourism-teal); letter-spacing: .04em; text-transform: uppercase; font-size: .74rem; font-weight: 700; }
    .admin-edit-page .capability-card { display: block; height: 100%; padding: .9rem; border: 1px solid #dce9e5; border-radius: 8px; cursor: pointer; transition: border-color .15s ease, background .15s ease; }
    .admin-edit-page .capability-card:has(input:checked) { border-color: #51a38d; background: #f0faf6; }
    .admin-edit-page .capability-card input { accent-color: var(--tourism-teal); }
    .admin-edit-page .capability-title { color: var(--tourism-ink); font-weight: 700; }
    .admin-edit-page .staff-progress { height: 9px; background: #e5efec; }
    .admin-edit-page .staff-progress .progress-bar { background: linear-gradient(90deg, var(--tourism-teal), var(--tourism-green)); }
    .admin-edit-page .tourism-mark { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff; background: linear-gradient(145deg, var(--tourism-teal), var(--tourism-green)); }
    .admin-edit-page .overview-link { color: var(--tourism-teal); font-weight: 600; text-decoration: none; }
</style>

<div class="admin-edit-page">
    <div class="breadcrumb-bar mb-3">
        <nav aria-label="Breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Provincial Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('super-admin.admins') }}">2nd District Municipalities</a></li><li class="breadcrumb-item"><a href="{{ route('super-admin.admins') }}">{{ $municipalityName }}</a></li><li class="breadcrumb-item active" aria-current="page">Edit Admin</li></ol></nav>
        <div class="breadcrumb-actions"><a href="{{ $returnTo }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancel</a><button type="submit" form="adminEditForm" class="btn btn-sm btn-tourism"><i class="fas fa-save me-1"></i> Save Changes</button></div>
    </div>
    <div class="d-flex align-items-center gap-3 mb-4">
        <span class="tourism-mark"><i class="fas fa-map-location-dot"></i></span>
        <div><div class="section-label">Pangasinan 2nd District</div><h2 class="mb-0">Edit Municipality Admin</h2></div>
    </div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Admin Details</h5>
                <span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-danger' }}">
                    {{ $admin->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="card-body">
                <form id="adminEditForm" action="{{ route('super-admin.admins.update', $admin) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    <input type="hidden" name="is_active" value="0">

                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            Set the admin's default login credentials here. The admin can later change only their password from their own profile after logging in.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name', $admin->name) }}"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="login" class="form-label">Email/Username</label>
                        <input
                            type="text"
                            class="form-control @error('login') is-invalid @enderror"
                            id="login"
                            name="login"
                            value="{{ old('login', $admin->username ?: $admin->email) }}"
                            required
                        >
                        <div class="form-text">Use either an email address or a username.</div>
                        @error('login')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="municipality" class="form-label">Municipality</label>
                        <input
                            type="text"
                            class="form-control"
                            id="municipality"
                            value="{{ $municipalityName }}"
                            readonly
                        >
                        <div class="form-text">Municipality assignment stays fixed for this account.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="is_active" class="form-label d-block">Account Status</label>
                        <div class="form-check form-switch mt-2">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="is_active"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $admin->is_active))
                            >
                            <label class="form-check-label" for="is_active">Active account</label>
                        </div>
                        <div class="form-text">Turn this off to deactivate login access.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="max_staff_accounts" class="form-label">Maximum Staff Accounts</label>
                        <input type="number" class="form-control @error('max_staff_accounts') is-invalid @enderror" id="max_staff_accounts" name="max_staff_accounts" value="{{ $staffLimit }}" min="0" max="10000" required>
                        <div class="progress staff-progress mt-3" role="progressbar" aria-label="Staff accounts used" aria-valuenow="{{ $staffUsed }}" aria-valuemin="0" aria-valuemax="{{ max(1, $staffLimit) }}">
                            <div class="progress-bar" style="width: {{ $staffProgress }}%"></div>
                        </div>
                        <div class="form-text">{{ $staffUsed }} of {{ $staffLimit }} staff accounts used. Use 0 to allow none.</div>
                        @error('max_staff_accounts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="password" class="form-label">Default Password</label>
                        <div class="input-group"><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" minlength="8" pattern="(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters, one uppercase letter, and one special character."><button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Show password"><i class="fas fa-eye"></i></button></div>
                        <div class="form-text">Leave blank to keep current. New password: 8+ chars, uppercase, special character.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <div class="input-group"><input type="password" class="form-control" id="password_confirmation" name="password_confirmation"><button class="btn btn-outline-secondary" type="button" data-toggle-password="password_confirmation" aria-label="Show password"><i class="fas fa-eye"></i></button></div>
                        <div class="form-text">Required when setting a new password.</div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Account Overview &amp; Actions</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Municipality</small>
                    <strong>{{ $municipalityName }}</strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Tourism Data</small>
                    <strong>{{ $touristSpotsCount }} tourist spots</strong>
                    <div class="mt-2"><a class="overview-link" href="{{ route('tourist_spots.index', ['municipality_id' => $admin->municipality_id]) }}"><i class="fas fa-location-dot me-1"></i> View {{ $municipalityName }}'s Tourist Spots</a></div>
                    <div class="mt-1"><a class="overview-link" href="{{ route('super-admin.reports', ['municipality_id' => $admin->municipality_id]) }}"><i class="fas fa-chart-line me-1"></i> View Reports Submitted</a></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Current Login</small>
                    <strong>{{ $admin->email }}</strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Role</small>
                    <span class="badge bg-info text-dark">Municipality Admin</span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Created</small>
                    <strong>{{ optional($admin->created_at)->format('M d, Y') }}</strong>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block">Last Updated</small>
                    <strong>{{ optional($admin->updated_at)->format('M d, Y') }}</strong>
                </div>
                <div class="mb-4">
                    <small class="text-muted d-block">Last Login</small>
                    <strong>{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y h:i A') : 'Never logged in' }}</strong>
                </div>
                <hr>
                <p class="text-muted mb-3">Use this action to change the account's login access.</p>
                <form action="{{ route('super-admin.admins.toggle-status', $admin) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button
                        type="{{ $admin->is_active ? 'button' : 'submit' }}"
                        class="btn w-100 {{ $admin->is_active ? 'btn-danger' : 'btn-success' }}"
                        @if($admin->is_active) data-bs-toggle="modal" data-bs-target="#deactivateAccountModal" @endif
                    >
                        <i class="fas fa-power-off"></i>
                        {{ $admin->is_active ? 'Deactivate Account' : 'Activate Account' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

@if($admin->is_active)
<div class="modal fade" id="deactivateAccountModal" tabindex="-1" aria-labelledby="deactivateAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="deactivateAccountModalLabel">Deactivate {{ $municipalityName }} admin?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">This will remove login access for the municipality admin and its team. You can activate the account again later.</div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="confirmDeactivate">Deactivate Account</button></div>
        </div>
    </div>
</div>
@endif

<script>
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.togglePassword);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.querySelector('i').className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    const confirmDeactivate = document.getElementById('confirmDeactivate');
    if (confirmDeactivate) {
        confirmDeactivate.addEventListener('click', function () {
            const form = document.querySelector('form[action*="/status"]');
            if (form) form.submit();
        });
    }
</script>
@endsection
