@extends('layouts.app')

@section('title', 'Add Staff Account')
@section('header', 'Add Staff Account')

@section('content')
<div class="row justify-content-center"><div class="col-xl-9"><div class="card"><div class="card-header bg-light"><h5 class="mb-1">Create Municipality Staff</h5><small class="text-muted">Assign only the capabilities this staff member needs.</small></div><div class="card-body">
<div class="alert alert-info">Staff accounts: <strong>{{ $staffCount }}</strong> of <strong>{{ $staffLimit ?? 'unlimited' }}</strong> used.</div>
<form action="{{ route('municipality-admin.staff.store') }}" method="POST" class="row g-3">
@csrf
<div class="col-md-6"><label for="name" class="form-label">Full Name</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label for="login" class="form-label">Email/Username</label><input class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" placeholder="staff@example.com or staff_username" required>@error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label for="password" class="form-label">Temporary Password</label><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" minlength="8" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label for="password_confirmation" class="form-label">Confirm Password</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8" required></div>
<div class="col-md-6"><label class="form-label d-block">Account Status</label><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))><label class="form-check-label" for="is_active">Active account</label></div></div>
<div class="col-12"><label class="form-label d-block">Staff Capabilities</label>@foreach($permissions as $permission => $label)<div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="permission_{{ $permission }}" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', []), true))><label class="form-check-label" for="permission_{{ $permission }}">{{ $label }}</label></div>@endforeach</div>
<div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('municipality-admin.staff') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit"><i class="fas fa-user-plus"></i> Create Staff Account</button></div>
</form></div></div></div></div>
@endsection
