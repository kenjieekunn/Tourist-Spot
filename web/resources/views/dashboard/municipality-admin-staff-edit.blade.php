@extends('layouts.app')

@section('title', 'Edit Staff Account')
@section('header', 'Edit Staff Account')

@section('content')
<div class="row justify-content-center"><div class="col-xl-9"><div class="card"><div class="card-header bg-light"><h5 class="mb-1">{{ $staff->name }}</h5><small class="text-muted">Update this staff member's access.</small></div><div class="card-body">
<form action="{{ route('municipality-admin.staff.update', $staff) }}" method="POST" class="row g-3">
@csrf
@method('PUT')
<div class="col-md-6"><label for="name" class="form-label">Full Name</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $staff->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label for="login" class="form-label">Email/Username</label><input class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login', $staff->username ?: $staff->email) }}" required>@error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label for="password" class="form-label">New Password</label><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" minlength="8">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-6"><label for="password_confirmation" class="form-label">Confirm New Password</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8"></div>
<div class="col-md-6"><label class="form-label d-block">Account Status</label><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $staff->is_active))><label class="form-check-label" for="is_active">Active account</label></div></div>
@php
	$savedPermissions = collect($staff->permissions ?? [])->filter()->keys()->all();
	$selectedPermissions = old('permissions', $savedPermissions);
@endphp
<div class="col-12"><label class="form-label d-block">Staff Capabilities</label>@foreach($permissions as $permission => $label)<div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="permission_{{ $permission }}" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $selectedPermissions, true))><label class="form-check-label" for="permission_{{ $permission }}">{{ $label }}</label></div>@endforeach</div>
<div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('municipality-admin.staff') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Changes</button></div>
</form></div></div></div></div>
@endsection
