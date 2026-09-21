@extends('layouts.app')

@section('title', 'Municipality Admins - Super Admin')
@section('header', 'Municipality Admins Management')

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('super-admin.admins') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="admin-search" class="form-label">Search admin</label>
                <input type="search" class="form-control" id="admin-search" name="q" value="{{ $searchTerm }}" placeholder="Search by name, username, or email">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="municipality-filter" class="form-label">Municipality</label>
                <select class="form-select" id="municipality-filter" name="municipality_id">
                    <option value="0">All Municipalities</option>
                    @foreach($municipalities as $municipality)
                        <option value="{{ $municipality->id }}" @selected($municipalityId === $municipality->id)>{{ $municipality->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label for="status-filter" class="form-label">Status</label>
                <select class="form-select" id="status-filter" name="status">
                    <option value="all" @selected($status === 'all')>All Statuses</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <a href="{{ route('super-admin.admins') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Municipal Admins</h5>
        <div class="d-flex align-items-center gap-3">
            <small class="text-muted">{{ $admins->total() }} total</small>
            <a href="{{ route('super-admin.admins.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Admin</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Name</th>
                    <th>Municipality</th>
                    <th>Role</th>
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
                                <span class="badge bg-info text-dark">{{ $admin->municipality->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>Municipal Admin</td>
                        <td>
                            <span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $admin->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('super-admin.admins.edit', $admin) }}" class="btn btn-sm btn-outline-primary" title="View or edit admin"><i class="fas fa-eye"></i> View</a>
                            <a href="{{ route('super-admin.admins.edit', $admin) }}" class="btn btn-sm btn-outline-secondary" title="Edit admin"><i class="fas fa-edit"></i> Edit</a>
                            <form action="{{ route('super-admin.admins.toggle-status', $admin) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $admin->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $admin->is_active ? 'Disable' : 'Enable' }} admin" onclick="return confirm('Change this admin account status?');">
                                    <i class="fas fa-power-off"></i> {{ $admin->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No municipality admins found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($admins->hasPages())
    <nav class="mt-4">
        {{ $admins->links() }}
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
