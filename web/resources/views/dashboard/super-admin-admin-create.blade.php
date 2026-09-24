@extends('layouts.app')

@section('title', 'Add Municipality Admin - Super Admin')
@section('header', 'Add Municipal Admin')

@section('content')
<style>
    .admin-create-page { --tourism-teal: #0f766e; --tourism-ink: #173f43; }
    .admin-create-page .breadcrumb { --bs-breadcrumb-divider: '>'; font-size: .88rem; }
    .admin-create-page .breadcrumb a { color: var(--tourism-teal); text-decoration: none; }
    .admin-create-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-create-page .scope-note { background: #effaf7; border: 1px solid #c8e5da; color: var(--tourism-ink); border-radius: 8px; }
    .admin-create-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .admin-create-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
</style>
<div class="admin-create-page">
<nav aria-label="Breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Provincial Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('super-admin.admins') }}">2nd District Municipalities</a></li>
        <li class="breadcrumb-item active" aria-current="page">Add Admin</li>
    </ol>
</nav>
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-1">Add Municipal Admin</h5>
                <small class="text-muted">Create an account for one municipality in Pangasinan 2nd District.</small>
            </div>
            <div class="card-body">
                <div class="scope-note p-3 mb-3"><i class="fas fa-lock me-2"></i><strong>2nd District scope locked</strong><div class="small mt-1">Only municipalities covered by the Pangasinan 2nd District admin system are available. Municipality assignment stays fixed after creation.</div></div>
                <form action="{{ route('super-admin.admins.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="login" class="form-label">Email/Username</label>
                        <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" placeholder="admin@example.com or admin_username" required>
                        @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="municipality_id" class="form-label">Municipality <span class="badge rounded-pill text-bg-light border"><i class="fas fa-lock me-1"></i>2nd District</span></label>
                        <select class="form-select @error('municipality_id') is-invalid @enderror" id="municipality_id" name="municipality_id" required>
                            @if($municipalities->isEmpty())
                                <option value="">All municipalities already have an admin</option>
                            @else
                                <option value="">Select Municipality</option>
                                @foreach($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" @selected((string) old('municipality_id', $selectedMunicipalityId) === (string) $municipality->id)>{{ $municipality->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('municipality_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($municipalities->isEmpty())<div class="form-text text-warning">No duplicate municipality admin can be created.</div>@endif
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Temporary Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" minlength="8" pattern="(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters, one uppercase letter, and one special character." required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">At least 8 characters, one uppercase letter, and one special character.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Account Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">Active account</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="max_staff_accounts" class="form-label">Maximum Staff Accounts</label>
                        <input type="number" class="form-control @error('max_staff_accounts') is-invalid @enderror" id="max_staff_accounts" name="max_staff_accounts" value="{{ old('max_staff_accounts', 5) }}" min="0" max="10000" required>
                        <div class="form-text">Set how many staff accounts this admin may create. Use 0 to allow none.</div>
                        @error('max_staff_accounts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                        <a href="{{ route('super-admin.admins') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-tourism"><i class="fas fa-user-plus"></i> Create 2nd District Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
