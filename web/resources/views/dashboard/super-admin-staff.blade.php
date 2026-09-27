@extends('layouts.app')

@section('title', 'Staff Accounts')
@section('header', '')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">Staff Accounts</h2>
        <p class="text-muted mb-0">Staff accounts across all municipalities.</p>
    </div>
    <span class="badge rounded-pill text-bg-light border">{{ $staff->count() }} staff</span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Municipality</th>
                    <th scope="col">Login</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account->name }}</td>
                        <td>{{ $account->municipality?->name ?? 'Unassigned' }}</td>
                        <td class="text-break">{{ $account->username ?: $account->email }}</td>
                        <td><span class="badge {{ $account->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $account->is_active ? 'Active' : 'Disabled' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">No staff accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection