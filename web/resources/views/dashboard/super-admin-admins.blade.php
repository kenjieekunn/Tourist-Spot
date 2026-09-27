@extends('layouts.app')

@section('title', 'Municipality Admins - Super Admin')
@section('header', '')

@section('content')
<style>
    .admin-list-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .admin-list-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-list-page .card-header { color: var(--tourism-ink); border-bottom-color: #dce9e5; }
    .admin-list-page .coverage-panel { background: linear-gradient(135deg, #effaf7, #f7fbf3); border: 1px solid #c8e5da; border-radius: 10px; }
    .admin-list-page .btn-tourism, .create-admin-modal .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .admin-list-page .btn-tourism:hover, .create-admin-modal .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .create-admin-modal { --tourism-teal: #0f766e; --tourism-ink: #173f43; }
    .create-admin-modal .scope-note { background: #effaf7; border: 1px solid #c8e5da; color: var(--tourism-ink); border-radius: 8px; }
    .admin-list-page .table { --bs-table-striped-bg: #fbfcfc; }
    .admin-list-page .admin-table thead th { padding: .85rem .75rem; color: #52666a; font-size: .76rem; line-height: 1.2; vertical-align: middle; white-space: normal; }
    .admin-list-page .admin-table th:nth-child(1) { min-width: 180px; }
    .admin-list-page .admin-table th:nth-child(2) { min-width: 140px; }
    .admin-list-page .admin-table th:nth-child(3) { min-width: 140px; }
    .admin-list-page .admin-table th:nth-child(4) { min-width: 110px; }
    .admin-list-page .admin-table th:nth-child(5) { min-width: 90px; }
    .admin-list-page .admin-table th:nth-child(6) { min-width: 125px; }
    .admin-list-page .table tbody tr { transition: background-color .15s ease; }
    .admin-list-page .table tbody tr:hover { --bs-table-hover-bg: #eef9f6; }
    .admin-list-page .admin-name-cell { width: 18%; min-width: 180px; max-width: 260px; white-space: normal; }
    .admin-list-page .admin-name { display: block; line-height: 1.35; overflow-wrap: anywhere; }
    .admin-list-page .municipality-badge { background: #d9f3ec; color: #12665f; border: 1px solid #b8e3d7; }
    .admin-list-page .metric { white-space: nowrap; }
</style>

<div class="admin-list-page">
    <div class="coverage-panel p-3 p-md-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="text-uppercase small fw-bold" style="color: var(--tourism-teal); letter-spacing: .06em;">Pangasinan 2nd District</div>
            <h2 class="h4 mb-1">Municipality Admins</h2>
            <p class="mb-0 text-muted">{{ $municipalitiesWithAdmins }} of {{ $municipalityCount }} municipalities sa 2nd District</p>
        </div>
        <div class="text-end"><span class="badge rounded-pill text-bg-light border">{{ $admins->count() }} admins</span></div>
    </div>

    <div class="card">
    <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="mb-1">Municipal Admin Directory</h5><small class="text-muted">Manage access and monitor tourism coverage by municipality.</small></div>
        <button type="button" class="btn btn-tourism btn-sm" data-bs-toggle="modal" data-bs-target="#createMunicipalityAdminModal"><i class="fas fa-plus me-1"></i> Add 2nd District Admin</button>
    </div>
    <div class="table-responsive">
        <table class="table admin-table table-striped table-hover mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Municipality</th>
                    <th scope="col" class="text-center">Tourist Spots<br>Managed</th>
                    <th scope="col" class="text-center">Staff<br>Accounts</th>
                    <th scope="col" class="text-center">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td class="admin-name-cell">
                            <strong class="admin-name">{{ $admin->name }}</strong>
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
                            <span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $admin->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-show-admin-credentials data-admin-id="{{ $admin->id }}" data-admin-name="{{ $admin->name }}" data-admin-email="{{ $admin->email }}" data-bs-toggle="modal" data-bs-target="#credentialsModal" title="Show temporary credentials"><i class="fas fa-key"></i> Credentials</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No municipality admins found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Credentials Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1" aria-labelledby="credentialsModalLabel" aria-hidden="true" data-initial-password="{{ session('temporary_password') }}" data-initial-name="{{ session('temporary_admin_name') }}" data-initial-email="{{ session('temporary_admin_email') }}">
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

<div class="modal fade" id="createMunicipalityAdminModal" tabindex="-1" aria-labelledby="createMunicipalityAdminModalLabel" aria-hidden="true" data-open-on-load="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content create-admin-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="createMunicipalityAdminModalLabel">Add 2nd District Admin</h5>
                    <small class="text-muted">Create an account for a municipality without an assigned admin.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('dashboard.partials.municipality-admin-form', [
                    'municipalityOptions' => $availableMunicipalities,
                    'formPrefix' => 'modal-admin',
                    'selectedMunicipalityId' => $selectedMunicipalityId,
                ])
            </div>
        </div>
    </div>
</div>

<script>
function showCredentials(adminId, name, email) {
    document.getElementById('credentialName').value = name;
    document.getElementById('credentialEmail').value = email;

    const passwordInput = document.getElementById('credentialPassword');
    passwordInput.value = 'Loading...';
    fetch(`/super-admin/admins/${adminId}/password`)
        .then(response => {
            if (!response.ok) throw new Error('Unable to load credentials.');
            return response.json();
        })
        .then(data => {
            passwordInput.value = data.password || 'Password not available';
        })
        .catch(error => {
            console.error('Error:', error);
            passwordInput.value = 'Error loading password';
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

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-show-admin-credentials]').forEach(function (button) {
        button.addEventListener('click', function () {
            showCredentials(button.dataset.adminId, button.dataset.adminName, button.dataset.adminEmail);
        });
    });

    const credentialsModal = document.getElementById('credentialsModal');
    if (credentialsModal?.dataset.initialPassword) {
        document.getElementById('credentialName').value = credentialsModal.dataset.initialName;
        document.getElementById('credentialEmail').value = credentialsModal.dataset.initialEmail;
        document.getElementById('credentialPassword').value = credentialsModal.dataset.initialPassword;
        bootstrap.Modal.getOrCreateInstance(credentialsModal).show();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const createAdminModal = document.getElementById('createMunicipalityAdminModal');
    if (createAdminModal?.dataset.openOnLoad === 'true') {
        bootstrap.Modal.getOrCreateInstance(createAdminModal).show();
    }
});
</script>

@endsection
