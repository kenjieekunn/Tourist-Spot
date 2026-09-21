@extends('layouts.app')

@section('title', 'Edit Municipality Admin - Super Admin')
@section('header', 'Edit Municipality Admin')

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
@endphp

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
                <form action="{{ route('super-admin.admins.update', $admin) }}" method="POST" class="row g-3">
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
                        <label for="username" class="form-label">Default Username</label>
                        <input
                            type="text"
                            class="form-control @error('username') is-invalid @enderror"
                            id="username"
                            name="username"
                            value="{{ old('username', $admin->username) }}"
                            required
                        >
                        <div class="form-text">This is the username the admin will use to log in.</div>
                        @error('username')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="municipality" class="form-label">Municipality</label>
                        <input
                            type="text"
                            class="form-control"
                            id="municipality"
                            value="{{ $admin->municipality->name ?? 'N/A' }}"
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

                    <div class="col-12">
                        <hr>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Default Password</label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                        >
                        <div class="form-text">Leave blank to keep the current password. The admin can change it later on their own profile page.</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                        <a href="{{ $returnTo }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Account Overview</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Municipality</small>
                    <strong>{{ $admin->municipality->name ?? 'N/A' }}</strong>
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
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Quick Action</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Use the toggle below to quickly change the account's login access.
                </p>
                <form action="{{ route('super-admin.admins.toggle-status', $admin) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button
                        type="submit"
                        class="btn w-100 {{ $admin->is_active ? 'btn-danger' : 'btn-success' }}"
                        onclick="return confirm('Are you sure you want to {{ $admin->is_active ? 'deactivate' : 'activate' }} this admin account?');"
                    >
                        <i class="fas fa-power-off"></i>
                        {{ $admin->is_active ? 'Deactivate Account' : 'Activate Account' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
