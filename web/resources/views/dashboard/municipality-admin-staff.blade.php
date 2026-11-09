@extends('layouts.app')

@section('title', 'Staff Accounts')
@section('header', '')

@section('content')
<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Municipality Staff</h5>
            <small class="text-muted">Staff accounts can only access the capabilities assigned to them.</small>
        </div>
        @if($staff->count() < (auth()->user()->max_staff_accounts ?? PHP_INT_MAX))
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createStaffAccountModal"><i class="fas fa-user-plus"></i> Add Staff</button>
        @else
            <span class="badge bg-warning text-dark">Staff limit reached</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="bg-light"><tr><th>Name</th><th>Login</th><th>Capabilities</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($staff as $member)
                    <tr>
                        @php($hasTemporaryCredential = $temporaryCredentialIds->contains((int) $member->id))
                        <td><strong>{{ $member->name }}</strong></td>
                        <td>{{ $member->email }}</td>
                        <td>{{ collect($member->permissions ?? [])->filter()->keys()->map(fn ($permission) => \Illuminate\Support\Str::headline($permission))->join(', ') ?: 'None' }}</td>
                        <td><span class="badge {{ $member->is_active ? 'bg-success' : 'bg-danger' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @if($hasTemporaryCredential)
                                <button type="button" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#staffCredentialsModal" data-credentials-url="{{ route('municipality-admin.staff.credentials', $member) }}" aria-label="View temporary credentials for {{ $member->name }}" title="View temporary credentials"><i class="fas fa-key" aria-hidden="true"></i></button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#staffActivityModal" data-activity-url="{{ route('municipality-admin.staff.activity', $member) }}" data-staff-name="{{ $member->name }}" aria-label="View activity for {{ $member->name }}" title="View staff activity"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></button>
                            <form action="{{ route('municipality-admin.staff.destroy', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive the staff account for {{ addslashes($member->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Archive {{ $member->name }}" title="Archive staff account"><i class="fas fa-box-archive" aria-hidden="true"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No staff accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="staffCredentialsModal" tabindex="-1" aria-labelledby="staffCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staffCredentialsModalLabel">Temporary Staff Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-1" aria-hidden="true"></i>Share these details securely. They are hidden after the staff member changes the temporary password.</div>
                <div class="alert alert-danger py-2" data-staff-credentials-error role="alert" hidden></div>
                <div class="mb-3"><label for="staffCredentialName" class="form-label">Name</label><input class="form-control" id="staffCredentialName" readonly></div>
                <div class="mb-3"><label for="staffCredentialEmail" class="form-label">Email</label><input class="form-control" id="staffCredentialEmail" readonly></div>
                <div><label for="staffCredentialPassword" class="form-label">Temporary password</label><div class="input-group"><input class="form-control" id="staffCredentialPassword" readonly autocomplete="off"><button class="btn btn-success" type="button" data-copy-staff-credential aria-label="Copy temporary password"><i class="fas fa-copy" aria-hidden="true"></i></button></div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="staffActivityModal" tabindex="-1" aria-labelledby="staffActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staffActivityModalLabel">Staff Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger py-2" data-staff-activity-error role="alert" hidden></div>
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="staff-overview-tab" data-bs-toggle="tab" data-bs-target="#staff-overview-panel" type="button" role="tab" aria-controls="staff-overview-panel" aria-selected="true">Overview</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="staff-history-tab" data-bs-toggle="tab" data-bs-target="#staff-history-panel" type="button" role="tab" aria-controls="staff-history-panel" aria-selected="false">Activity History</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="staff-overview-panel" role="tabpanel" aria-labelledby="staff-overview-tab" tabindex="0">
                        <dl class="row mb-3">
                            <dt class="col-5">Login</dt><dd class="col-7 mb-2" data-staff-activity-email>Loading...</dd>
                            <dt class="col-5">Status</dt><dd class="col-7 mb-2" data-staff-activity-status>Loading...</dd>
                            <dt class="col-5">Account created</dt><dd class="col-7 mb-2" data-staff-activity-created>Loading...</dd>
                            <dt class="col-5">Last login</dt><dd class="col-7 mb-0" data-staff-activity-last-login>Loading...</dd>
                        </dl>
                        <h6 class="border-top pt-3">Recent activity</h6>
                        <ol class="list-group list-group-flush" data-staff-activity-history>
                            <li class="list-group-item px-0 text-muted">Loading activity...</li>
                        </ol>
                    </div>
                    <div class="tab-pane fade" id="staff-history-panel" role="tabpanel" aria-labelledby="staff-history-tab" tabindex="0">
                        <ol class="list-group list-group-flush" data-staff-activity-log>
                            <li class="list-group-item px-0 text-muted">Loading history...</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="createStaffAccountModal" tabindex="-1" aria-labelledby="createStaffAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="createStaffAccountModalLabel">Create Municipality Staff</h5>
                    <small class="text-muted">A temporary password will be generated automatically.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="staff-create-modal-form" action="{{ route('municipality-admin.staff.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger py-2" data-staff-create-errors role="alert" hidden></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="staff-modal-name" class="form-label">Full Name</label>
                            <input class="form-control" id="staff-modal-name" name="name" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label for="staff-modal-login" class="form-label">Email/Username</label>
                            <input class="form-control" id="staff-modal-login" name="login" placeholder="staff@example.com or staff_username" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Staff Capabilities</label>
                            @forelse($permissions as $permission => $label)
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="staff-modal-permission-{{ $permission }}" name="permissions[]" value="{{ $permission }}"><label class="form-check-label" for="staff-modal-permission-{{ $permission }}">{{ $label }}</label></div>
                            @empty
                                <div class="text-muted small">No additional capabilities are available for staff accounts.</div>
                            @endforelse
                        </div>
                        <div class="col-12"><div class="alert alert-info mb-0"><i class="fas fa-key me-2"></i>The temporary login details will appear after the account is created. Staff must change the password after signing in.</div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" data-staff-create-submit><i class="fas fa-user-plus me-1" aria-hidden="true"></i>Create Staff Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="newStaffCredentialsModal" tabindex="-1" aria-labelledby="newStaffCredentialsModalLabel" aria-hidden="true" data-initial-name="{{ session('temporary_staff_name') }}" data-initial-login="{{ session('temporary_staff_login') }}" data-initial-password="{{ session('temporary_staff_password') }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newStaffCredentialsModalLabel">Staff Login Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-1"></i>Share these details securely. The staff member will be required to change the temporary password after signing in.</div>
                <div class="mb-3"><label for="newStaffCredentialName" class="form-label">Name</label><input class="form-control" id="newStaffCredentialName" readonly></div>
                <div class="mb-3"><label for="newStaffCredentialLogin" class="form-label">Login</label><input class="form-control" id="newStaffCredentialLogin" readonly></div>
                <div><label for="newStaffCredentialPassword" class="form-label">Temporary password</label><div class="input-group"><input class="form-control" id="newStaffCredentialPassword" readonly autocomplete="off"><button class="btn btn-success" type="button" data-copy-staff-password aria-label="Copy temporary password"><i class="fas fa-copy" aria-hidden="true"></i></button></div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-success" data-bs-dismiss="modal">Done</button></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const staffCredentialsModal = document.getElementById('staffCredentialsModal');
        const credentialsError = staffCredentialsModal?.querySelector('[data-staff-credentials-error]');
        staffCredentialsModal?.addEventListener('show.bs.modal', async function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;

            credentialsError.hidden = true;
            document.getElementById('staffCredentialName').value = '';
            document.getElementById('staffCredentialEmail').value = '';
            document.getElementById('staffCredentialPassword').value = 'Loading...';
            try {
                const response = await fetch(trigger.dataset.credentialsUrl, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Temporary credentials are no longer available.');
                document.getElementById('staffCredentialName').value = result.name;
                document.getElementById('staffCredentialEmail').value = result.email;
                document.getElementById('staffCredentialPassword').value = result.password;
            } catch (error) {
                document.getElementById('staffCredentialPassword').value = '';
                credentialsError.textContent = error.message || 'Unable to load temporary credentials.';
                credentialsError.hidden = false;
            }
        });

        const activityModal = document.getElementById('staffActivityModal');
        const activityError = activityModal?.querySelector('[data-staff-activity-error]');
        const activityHistory = activityModal?.querySelector('[data-staff-activity-history]');
        const activityLog = activityModal?.querySelector('[data-staff-activity-log]');
        activityModal?.addEventListener('show.bs.modal', async function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;

            activityError.hidden = true;
            activityModal.querySelector('[data-staff-activity-email]').textContent = 'Loading...';
            activityModal.querySelector('[data-staff-activity-status]').textContent = 'Loading...';
            activityModal.querySelector('[data-staff-activity-created]').textContent = 'Loading...';
            activityModal.querySelector('[data-staff-activity-last-login]').textContent = 'Loading...';
            activityHistory.replaceChildren();
            activityLog.replaceChildren();
            bootstrap.Tab.getOrCreateInstance(activityModal.querySelector('#staff-overview-tab')).show();
            const loadingItem = document.createElement('li');
            loadingItem.className = 'list-group-item px-0 text-muted';
            loadingItem.textContent = 'Loading activity...';
            activityHistory.appendChild(loadingItem);
            activityModal.querySelector('.modal-title').textContent = `Staff Activity: ${trigger.dataset.staffName}`;

            try {
                const response = await fetch(trigger.dataset.activityUrl, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Unable to load staff activity.');

                activityModal.querySelector('[data-staff-activity-email]').textContent = result.email || 'Not available';
                activityModal.querySelector('[data-staff-activity-status]').textContent = result.is_active ? 'Active' : 'Inactive';
                activityModal.querySelector('[data-staff-activity-created]').textContent = result.created_at || 'Not available';
                activityModal.querySelector('[data-staff-activity-last-login]').textContent = result.last_login_at || 'No successful login recorded';
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
                activityError.textContent = error.message || 'Unable to load staff activity.';
                activityError.hidden = false;
            }
        });

        staffCredentialsModal?.querySelector('[data-copy-staff-credential]')?.addEventListener('click', async function () {
            const password = document.getElementById('staffCredentialPassword').value;
            if (!password || password === 'Loading...') return;
            try {
                await navigator.clipboard.writeText(password);
                this.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
            } catch (error) {
                const input = document.getElementById('staffCredentialPassword');
                input.select();
                document.execCommand('copy');
            }
        });

        const credentialsModal = document.getElementById('newStaffCredentialsModal');
        const createModal = document.getElementById('createStaffAccountModal');
        const createForm = document.getElementById('staff-create-modal-form');
        const createErrors = document.querySelector('[data-staff-create-errors]');
        const createSubmit = document.querySelector('[data-staff-create-submit]');
        let refreshAfterCredentials = false;

        const showCredentials = function (name, login, password, shouldRefresh) {
            document.getElementById('newStaffCredentialName').value = name;
            document.getElementById('newStaffCredentialLogin').value = login;
            document.getElementById('newStaffCredentialPassword').value = password;
            refreshAfterCredentials = shouldRefresh;
            bootstrap.Modal.getOrCreateInstance(credentialsModal).show();
        };

        if (credentialsModal?.dataset.initialPassword) {
            showCredentials(
                credentialsModal.dataset.initialName,
                credentialsModal.dataset.initialLogin,
                credentialsModal.dataset.initialPassword,
                false
            );
        }

        credentialsModal?.addEventListener('hidden.bs.modal', function () {
            if (refreshAfterCredentials) window.location.reload();
        });

        credentialsModal?.querySelector('[data-copy-staff-password]')?.addEventListener('click', async function () {
            const password = document.getElementById('newStaffCredentialPassword').value;
            try {
                await navigator.clipboard.writeText(password);
                this.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
            } catch (error) {
                const input = document.getElementById('newStaffCredentialPassword');
                input.select();
                document.execCommand('copy');
            }
        });

        createForm?.addEventListener('submit', async function (event) {
            event.preventDefault();
            createErrors.hidden = true;
            createErrors.textContent = '';
            createSubmit.disabled = true;
            createSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1" aria-hidden="true"></i>Creating';

            try {
                const response = await fetch(createForm.action, {
                    method: 'POST',
                    body: new FormData(createForm),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                if (!response.ok) {
                    const messages = Object.values(result.errors || {}).flat();
                    throw new Error(messages.join(' ') || result.message || 'Unable to create staff account.');
                }

                createForm.reset();
                createModal.addEventListener('hidden.bs.modal', function () {
                    showCredentials(result.name, result.login, result.password, true);
                }, { once: true });
                bootstrap.Modal.getOrCreateInstance(createModal).hide();
            } catch (error) {
                createErrors.textContent = error.message || 'Unable to create staff account.';
                createErrors.hidden = false;
            } finally {
                createSubmit.disabled = false;
                createSubmit.innerHTML = '<i class="fas fa-user-plus me-1" aria-hidden="true"></i>Create Staff Account';
            }
        });
    });
</script>
@endsection
