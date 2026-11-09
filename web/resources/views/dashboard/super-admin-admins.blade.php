@extends('layouts.app')

@section('title', 'Municipality Admins - Super Admin')
@section('header', '')

@section('content')
<style>
    .admin-list-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .admin-list-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .admin-list-page .card-header { color: var(--tourism-ink); border-bottom-color: #dce9e5; }
    .admin-list-page .btn-tourism, .create-admin-modal .btn-tourism, #credentialsModal .btn-tourism { background: var(--tourism-teal, #0f766e); border-color: var(--tourism-teal, #0f766e); color: #fff; }
    .admin-list-page .btn-tourism:hover, .admin-list-page .btn-tourism:focus-visible,
    .create-admin-modal .btn-tourism:hover, .create-admin-modal .btn-tourism:focus-visible,
    #credentialsModal .btn-tourism:hover, #credentialsModal .btn-tourism:focus-visible { background: #115e59; border-color: #115e59; color: #fff; }
    .create-admin-modal { --tourism-teal: #0f766e; --tourism-ink: #173f43; }
    .create-admin-modal .scope-note { background: #effaf7; border: 1px solid #c8e5da; color: var(--tourism-ink); border-radius: 8px; }
    .admin-list-page .table { --bs-table-striped-bg: #fbfcfc; }
    .admin-list-page .admin-table thead th { padding: .85rem .75rem; color: #52666a; font-size: .76rem; line-height: 1.2; vertical-align: middle; white-space: normal; }
    .admin-list-page .admin-table th:nth-child(1) { min-width: 180px; }
    .admin-list-page .admin-table th:nth-child(2) { min-width: 140px; }
    .admin-list-page .admin-table th:nth-child(3) { min-width: 140px; }
    .admin-list-page .admin-table th:nth-child(4) { min-width: 110px; }
    .admin-list-page .admin-table th:nth-child(5) { min-width: 90px; }
    .admin-list-page .admin-table th:nth-child(6) { min-width: 70px; }
    .admin-list-page .table tbody tr { transition: background-color .15s ease; }
    .admin-list-page .table tbody tr:hover { --bs-table-hover-bg: #eef9f6; }
    .admin-list-page .admin-name-cell { width: 18%; min-width: 180px; max-width: 260px; white-space: normal; }
    .admin-list-page .admin-name { display: block; line-height: 1.35; overflow-wrap: anywhere; }
    .admin-list-page .municipality-badge { background: #d9f3ec; color: #12665f; border: 1px solid #b8e3d7; }
    .admin-list-page .metric { white-space: nowrap; }
</style>

<div class="admin-list-page">
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
                        <td class="text-center"><span class="metric"><i class="fas fa-location-dot me-1" style="color: var(--tourism-teal);"></i>{{ $admin->municipality?->tourist_spots_count ?? 0 }} spots</span></td>
                        <td class="text-center"><span class="metric"><i class="fas fa-users me-1 text-muted"></i>{{ $admin->municipality?->staff_accounts_count ?? 0 }}/{{ $admin->max_staff_accounts ?? 0 }} staff</span></td>
                        <td class="text-center">
                            <span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $admin->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#adminActivityModal" data-activity-url="{{ route('super-admin.admins.activity', $admin) }}" data-admin-name="{{ $admin->name }}" aria-label="View activity for {{ $admin->name }}" title="View admin activity"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></button>
                            <form action="{{ route('super-admin.admins.destroy', $admin) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive the municipality admin account for {{ addslashes($admin->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Deactivate and archive {{ $admin->name }} account" title="Deactivate and archive admin account"><i class="fas fa-user-slash" aria-hidden="true"></i></button>
                            </form>
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

<div class="modal fade" id="adminActivityModal" tabindex="-1" aria-labelledby="adminActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminActivityModalLabel">Municipality Admin Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger py-2" data-admin-activity-error role="alert" hidden></div>
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="admin-overview-tab" data-bs-toggle="tab" data-bs-target="#admin-overview-panel" type="button" role="tab" aria-controls="admin-overview-panel" aria-selected="true">Overview</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="admin-history-tab" data-bs-toggle="tab" data-bs-target="#admin-history-panel" type="button" role="tab" aria-controls="admin-history-panel" aria-selected="false">Activity History</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="admin-overview-panel" role="tabpanel" aria-labelledby="admin-overview-tab" tabindex="0">
                        <dl class="row mb-3">
                            <dt class="col-5">Login</dt><dd class="col-7 mb-2" data-admin-activity-email>Loading...</dd>
                            <dt class="col-5">Municipality</dt><dd class="col-7 mb-2" data-admin-activity-municipality>Loading...</dd>
                            <dt class="col-5">Status</dt><dd class="col-7 mb-2" data-admin-activity-status>Loading...</dd>
                            <dt class="col-5">Account created</dt><dd class="col-7 mb-2" data-admin-activity-created>Loading...</dd>
                            <dt class="col-5">Last login</dt><dd class="col-7 mb-0" data-admin-activity-last-login>Loading...</dd>
                        </dl>
                        <h6 class="border-top pt-3">Recent activity</h6>
                        <ol class="list-group list-group-flush" data-admin-activity-history>
                            <li class="list-group-item px-0 text-muted">Loading activity...</li>
                        </ol>
                    </div>
                    <div class="tab-pane fade" id="admin-history-panel" role="tabpanel" aria-labelledby="admin-history-tab" tabindex="0">
                        <ol class="list-group list-group-flush" data-admin-activity-log>
                            <li class="list-group-item px-0 text-muted">Loading history...</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
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
                        <button class="btn btn-tourism" type="button" id="copyPasswordBtn" onclick="copyPassword()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-tourism" data-bs-dismiss="modal">Close</button>
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
    const activityModal = document.getElementById('adminActivityModal');
    const activityError = activityModal?.querySelector('[data-admin-activity-error]');
    const activityHistory = activityModal?.querySelector('[data-admin-activity-history]');
    const activityLog = activityModal?.querySelector('[data-admin-activity-log]');

    activityModal?.addEventListener('show.bs.modal', async function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        activityError.hidden = true;
        activityModal.querySelector('[data-admin-activity-email]').textContent = 'Loading...';
        activityModal.querySelector('[data-admin-activity-municipality]').textContent = 'Loading...';
        activityModal.querySelector('[data-admin-activity-status]').textContent = 'Loading...';
        activityModal.querySelector('[data-admin-activity-created]').textContent = 'Loading...';
        activityModal.querySelector('[data-admin-activity-last-login]').textContent = 'Loading...';
        activityHistory.replaceChildren();
        activityLog.replaceChildren();
        bootstrap.Tab.getOrCreateInstance(activityModal.querySelector('#admin-overview-tab')).show();
        const loadingItem = document.createElement('li');
        loadingItem.className = 'list-group-item px-0 text-muted';
        loadingItem.textContent = 'Loading activity...';
        activityHistory.appendChild(loadingItem);
        activityModal.querySelector('.modal-title').textContent = `Admin Activity: ${trigger.dataset.adminName}`;

        try {
            const response = await fetch(trigger.dataset.activityUrl, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Unable to load admin activity.');

            activityModal.querySelector('[data-admin-activity-email]').textContent = result.email || 'Not available';
            activityModal.querySelector('[data-admin-activity-municipality]').textContent = result.municipality || 'Not assigned';
            activityModal.querySelector('[data-admin-activity-status]').textContent = result.is_active ? 'Active' : 'Disabled';
            activityModal.querySelector('[data-admin-activity-created]').textContent = result.created_at || 'Not available';
            activityModal.querySelector('[data-admin-activity-last-login]').textContent = result.last_login_at || 'No successful login recorded';
            activityHistory.replaceChildren();

            if (!result.activity_history?.length) {
                const emptyItem = document.createElement('li');
                emptyItem.className = 'list-group-item px-0 text-muted';
                emptyItem.textContent = 'No activity recorded yet.';
                activityHistory.appendChild(emptyItem);
            } else {
                result.activity_history.forEach((entry) => {
                    const item = document.createElement('li');
                    item.className = 'list-group-item px-0';
                    item.textContent = `${entry.occurred_at} · ${entry.description}`;
                    activityHistory.appendChild(item);
                });
            }

            if (!result.activity_log?.length) {
                const emptyItem = document.createElement('li');
                emptyItem.className = 'list-group-item px-0 text-muted';
                emptyItem.textContent = 'No activity history recorded yet.';
                activityLog.appendChild(emptyItem);
                return;
            }

            result.activity_log.forEach((entry) => {
                const item = document.createElement('li');
                item.className = 'list-group-item px-0';
                const row = document.createElement('div');
                row.className = 'd-flex flex-column flex-sm-row justify-content-between gap-2';
                const details = document.createElement('div');
                const type = document.createElement('strong');
                type.textContent = entry.type || 'Activity';
                const description = document.createElement('div');
                description.className = 'text-muted small';
                description.textContent = entry.description || 'Activity recorded';
                details.append(type, description);
                const metadata = document.createElement('div');
                metadata.className = 'text-sm-end text-nowrap';
                const date = document.createElement('div');
                date.className = 'small text-muted';
                date.textContent = entry.occurred_at || 'Date unavailable';
                metadata.appendChild(date);
                if (entry.status) {
                    const status = document.createElement('span');
                    status.className = `badge ${entry.status === 'Verified' ? 'bg-success' : entry.status === 'Pending' ? 'bg-warning text-dark' : entry.status === 'Rejected' ? 'bg-danger' : 'bg-secondary'}`;
                    status.textContent = entry.status;
                    metadata.appendChild(status);
                }
                row.append(details, metadata);
                item.appendChild(row);
                activityLog.appendChild(item);
            });
        } catch (error) {
            activityHistory.replaceChildren();
            activityLog.replaceChildren();
            activityError.textContent = error.message || 'Unable to load admin activity.';
            activityError.hidden = false;
        }
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
