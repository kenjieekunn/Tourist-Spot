@extends('layouts.app')

@section('title', 'View Municipality Admin - Super Admin')
@section('header', '')

@section('content')
@php
    $municipalityName = $admin->municipality->name ?? 'N/A';
    $staffLimit = (int) ($admin->max_staff_accounts ?? 0);
    $staffProgress = $staffLimit > 0 ? min(100, (int) round(($staffAccountsUsed / $staffLimit) * 100)) : 0;
@endphp
<style>
    .admin-show-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .admin-show-page .breadcrumb { --bs-breadcrumb-divider: '>'; font-size: .88rem; }
    .admin-show-page .breadcrumb a { color: var(--tourism-teal); text-decoration: none; }
    .admin-show-page .breadcrumb-bar { align-items: center; display: flex; gap: 1rem; justify-content: space-between; }
    .admin-show-page .page-heading { border: 1px solid #c8e5da; border-radius: 10px; padding: 1.1rem 1.25rem; background: linear-gradient(135deg, #effaf7, #f7fbf3); color: var(--tourism-ink); }
    .admin-show-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-show-page .card-header { color: var(--tourism-ink); border-bottom-color: #dce9e5; }
    .admin-show-page .detail-label { color: #6b7280; display: block; font-size: .76rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .admin-show-page .detail-value { color: var(--tourism-ink); font-size: 1.05rem; font-weight: 600; }
    .admin-show-page .tourism-mark { align-items: center; background: linear-gradient(145deg, var(--tourism-teal), var(--tourism-green)); border-radius: 50%; color: #fff; display: inline-flex; height: 42px; justify-content: center; width: 42px; }
    .admin-show-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .admin-show-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .admin-show-page .permission-item { background: #f0faf6; border: 1px solid #c8e5da; border-radius: 8px; color: var(--tourism-ink); padding: .7rem .85rem; }
    .admin-show-page .staff-progress { background: #e5efec; height: 9px; }
    .admin-show-page .staff-progress .progress-bar { background: linear-gradient(90deg, var(--tourism-teal), var(--tourism-green)); }
</style>

<div class="admin-show-page">
    <div class="breadcrumb-bar mb-3"><nav aria-label="Breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Provincial Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('super-admin.admins') }}">2nd District Municipalities</a></li><li class="breadcrumb-item active" aria-current="page">View Admin</li></ol></nav><a href="{{ route('super-admin.admins.edit', $admin) }}" class="btn btn-sm btn-tourism"><i class="fas fa-pen me-1"></i> Edit Admin</a></div>
    <div class="page-heading mb-4 d-flex align-items-center gap-3"><span class="tourism-mark"><i class="fas fa-user-shield"></i></span><div><div class="small text-uppercase fw-bold" style="color: var(--tourism-teal); letter-spacing: .06em;">Municipality Admin Profile</div><h2 class="h4 mb-0">{{ $admin->name }}</h2></div></div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center"><h5 class="mb-0">Admin Details</h5><span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $admin->is_active ? 'Active' : 'Disabled' }}</span></div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6"><span class="detail-label">Full Name</span><span class="detail-value">{{ $admin->name }}</span></div>
                        <div class="col-md-6"><span class="detail-label">Email / Username</span><span class="detail-value">{{ $admin->username ?: $admin->email }}</span></div>
                        <div class="col-md-6"><span class="detail-label">Municipality</span><span class="detail-value">{{ $municipalityName }}</span></div>
                        <div class="col-md-6"><span class="detail-label">Role</span><span class="badge bg-info text-dark">Municipality Admin</span></div>
                        <div class="col-md-6"><span class="detail-label">Created</span><span class="detail-value">{{ optional($admin->created_at)->format('M d, Y') }}</span></div>
                        <div class="col-md-6"><span class="detail-label">Last Updated</span><span class="detail-value">{{ optional($admin->updated_at)->format('M d, Y') }}</span></div>
                        <div class="col-md-6"><span class="detail-label">Last Login</span><span class="detail-value">{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y h:i A') : 'Never logged in' }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card mt-4"><div class="card-header bg-light"><h5 class="mb-0">Allowed Capabilities <span class="badge rounded-pill text-bg-light border">{{ count($enabledPermissions) }} of {{ count($permissions) }} enabled</span></h5></div><div class="card-body"><div class="row g-2">@foreach($permissions as $permission => $label)<div class="col-md-6"><div class="permission-item"><i class="fas fa-check-circle text-success me-2"></i>{{ $label }}</div></div>@endforeach</div></div></div>
        </div>

        <div class="col-lg-4">
            <div class="card"><div class="card-header bg-light"><h5 class="mb-0">Account Overview</h5></div><div class="card-body"><div class="mb-3"><span class="detail-label">Tourist Spots Managed</span><span class="detail-value">{{ $touristSpotsCount }} spots</span></div><div class="mb-3"><span class="detail-label">Staff Accounts</span><span class="detail-value">{{ $staffAccountsUsed }} of {{ $staffLimit }} used</span><div class="progress staff-progress mt-2"><div class="progress-bar" style="width: {{ $staffProgress }}%"></div></div></div><hr><div class="mb-0"><span class="detail-label">Account Access</span><span class="detail-value">{{ $admin->is_active ? 'Active login access' : 'Login disabled' }}</span></div></div></div>
        </div>
    </div>
</div>
@endsection
