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
            <a href="{{ route('municipality-admin.staff.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add Staff</a>
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
@endsection
