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
                        <td><strong>{{ $member->name }}</strong></td>
                        <td>{{ $member->email }}</td>
                        <td>{{ collect($member->permissions ?? [])->filter()->keys()->map(fn ($permission) => \Illuminate\Support\Str::headline($permission))->join(', ') ?: 'None' }}</td>
                        <td><span class="badge {{ $member->is_active ? 'bg-success' : 'bg-danger' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('municipality-admin.staff.edit', $member) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i> Edit</a>
                            <form action="{{ route('municipality-admin.staff.toggle-status', $member) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $member->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $member->is_active ? 'Disable' : 'Enable' }}</button>
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
                            <label class="form-label d-block">Account Status</label>
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="staff-modal-active" name="is_active" value="1" checked><label class="form-check-label" for="staff-modal-active">Active account</label></div>
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
