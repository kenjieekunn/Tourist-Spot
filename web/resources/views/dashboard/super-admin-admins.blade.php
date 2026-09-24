@extends('layouts.app')

@section('title', 'Municipality Admins - Super Admin')
@section('header', '')

@section('content')
<style>
    .admin-list-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .admin-list-page .breadcrumb { --bs-breadcrumb-divider: '>'; font-size: .88rem; }
    .admin-list-page .breadcrumb a { color: var(--tourism-teal); text-decoration: none; }
    .admin-list-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-list-page .card-header { color: var(--tourism-ink); border-bottom-color: #dce9e5; }
    .admin-list-page .coverage-panel { background: linear-gradient(135deg, #effaf7, #f7fbf3); border: 1px solid #c8e5da; border-radius: 10px; }
    .admin-list-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .admin-list-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .admin-list-page .table { --bs-table-striped-bg: #fbfcfc; }
    .admin-list-page .table tbody tr { transition: background-color .15s ease; }
    .admin-list-page .table tbody tr:hover { --bs-table-hover-bg: #eef9f6; }
    .admin-list-page .municipality-badge { background: #d9f3ec; color: #12665f; border: 1px solid #b8e3d7; }
    .admin-list-page .permission-dots { display: inline-flex; gap: 3px; vertical-align: middle; }
    .admin-list-page .permission-dot { width: 8px; height: 8px; border-radius: 50%; background: #d9e3e1; display: inline-block; }
    .admin-list-page .permission-dot.enabled { background: #159a87; }
    .admin-list-page .metric { white-space: nowrap; }
</style>

<div class="admin-list-page">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Provincial Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">2nd District Municipalities</li>
        </ol>
    </nav>

    <div class="coverage-panel p-3 p-md-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="text-uppercase small fw-bold" style="color: var(--tourism-teal); letter-spacing: .06em;">Pangasinan 2nd District</div>
            <h2 class="h4 mb-1">Municipality Admins</h2>
            <p class="mb-0 text-muted">{{ $municipalitiesWithAdmins }} of {{ $municipalityCount }} municipalities sa 2nd District</p>
        </div>
        <div class="text-end"><span class="badge rounded-pill text-bg-light border">{{ $admins->total() }} admins</span></div>
    </div>

    <div class="card">
    <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="mb-1">Municipal Admin Directory</h5><small class="text-muted">Manage access and monitor tourism coverage by municipality.</small></div>
        <a href="{{ route('super-admin.admins.create') }}" class="btn btn-tourism btn-sm"><i class="fas fa-plus me-1"></i> Add 2nd District Admin</a>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('super-admin.admins') }}" class="row g-2 align-items-end">
            <div class="col-md-7 col-lg-8"><label for="adminSearch" class="form-label small fw-semibold">Search municipalities or admins</label><input type="search" class="form-control" id="adminSearch" name="search" value="{{ $search }}" placeholder="Search by municipality, name, email, or username"></div>
            <div class="col-md-3 col-lg-2"><label for="adminStatus" class="form-label small fw-semibold">Status</label><select class="form-select" id="adminStatus" name="status"><option value="all" @selected($status === 'all')>All statuses</option><option value="active" @selected($status === 'active')>Active</option><option value="disabled" @selected($status === 'disabled')>Disabled</option></select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i> Filter</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Name</th>
                    <th>Municipality</th>
                    <th>Tourist Spots Managed</th>
                    <th>Staff Accounts</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td>
                            <strong>{{ $admin->name }}</strong>
                        </td>
                        <td>
                            @if($admin->municipality)
                                <span class="badge municipality-badge">{{ $admin->municipality->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><span class="metric"><i class="fas fa-location-dot me-1" style="color: var(--tourism-teal);"></i>{{ $admin->municipality?->tourist_spots_count ?? 0 }} spots</span></td>
                        <td><span class="metric"><i class="fas fa-users me-1 text-muted"></i>{{ $admin->municipality?->staff_accounts_count ?? 0 }}/{{ $admin->max_staff_accounts ?? 0 }} staff</span></td>
                        <td>
                            @php $permissionKeys = ['manage_spots', 'manage_reviews', 'view_reports', 'manage_staff']; $enabledPermissions = $admin->permissions === null ? 4 : collect($admin->permissions)->filter()->count(); @endphp
                            <span class="permission-dots" title="{{ $enabledPermissions }} of 4 capabilities enabled" aria-label="{{ $enabledPermissions }} of 4 capabilities enabled">@foreach($permissionKeys as $permissionKey)<span class="permission-dot {{ $admin->permissions === null || ($admin->permissions[$permissionKey] ?? false) ? 'enabled' : '' }}"></span>@endforeach</span>
                            <span class="small text-muted ms-1">{{ $enabledPermissions }}/4</span>
                        </td>
                        <td>
                            <span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $admin->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('super-admin.admins.show', $admin) }}" class="btn btn-sm btn-outline-primary" title="View admin details"><i class="fas fa-eye"></i> View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No municipality admins found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($admins->hasPages())
    <nav class="mt-4">
        {{ $admins->appends(['search' => $search, 'status' => $status])->links() }}
    </nav>
@endif

<!-- Credentials Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1" aria-labelledby="credentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="credentialsModalLabel">Admin Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="credentialName" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="text" class="form-control" id="credentialEmail" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="credentialPassword" readonly style="background-color: #fff;">
                        <button class="btn btn-outline-secondary" type="button" id="copyPasswordBtn" onclick="copyPassword()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showCredentials(adminId, name, email) {
    document.getElementById('credentialName').value = name;
    document.getElementById('credentialEmail').value = email;
    
    fetch(`/super-admin/admins/${adminId}/password`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('credentialPassword').value = data.password;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('credentialPassword').value = 'Error loading password';
        });
}

function copyPassword() {
    const passwordInput = document.getElementById('credentialPassword');
    passwordInput.select();
    document.execCommand('copy');
    
    const btn = document.getElementById('copyPasswordBtn');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => {
        btn.innerHTML = originalHTML;
    }, 2000);
}

</script>

@endsection
