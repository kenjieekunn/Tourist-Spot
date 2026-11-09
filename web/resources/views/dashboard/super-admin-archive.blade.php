@extends('layouts.app')

@section('title', 'Archive - Super Admin')
@section('header', 'Archive')

@section('content')
<ul class="nav nav-tabs mb-4" id="archiveTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="spots-tab" data-bs-toggle="tab" data-bs-target="#archived-spots" type="button" role="tab" aria-controls="archived-spots" aria-selected="true">
            Deleted Tourist Spots <span class="badge text-bg-secondary ms-1">{{ $archivedSpots->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="accounts-tab" data-bs-toggle="tab" data-bs-target="#archived-accounts" type="button" role="tab" aria-controls="archived-accounts" aria-selected="false">
            Deleted Admin and Staff Accounts <span class="badge text-bg-secondary ms-1">{{ $archivedAccounts->count() }}</span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <section class="tab-pane fade show active" id="archived-spots" role="tabpanel" aria-labelledby="spots-tab" tabindex="0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Tourist Spot</th><th>Municipality</th><th>Category</th><th>Archived</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($archivedSpots as $spot)
                        <tr>
                            <td class="fw-semibold">{{ $spot->name }}</td>
                            <td>{{ $spot->municipality?->name ?? 'Unknown Municipality' }}</td>
                            <td>{{ ucfirst($spot->category ?? 'nature') }}</td>
                            <td>{{ optional($spot->deleted_at)->format('M d, Y h:i A') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('super-admin.archive.spots.restore', $spot->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success"><i class="fas fa-rotate-left me-1"></i>Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No deleted tourist spots.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="tab-pane fade" id="archived-accounts" role="tabpanel" aria-labelledby="accounts-tab" tabindex="0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Name</th><th>Email</th><th>Account Type</th><th>Municipality</th><th>Archived</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($archivedAccounts as $account)
                        <tr>
                            <td class="fw-semibold">{{ $account->name }}</td>
                            <td>{{ $account->email }}</td>
                            <td>{{ $account->isMunicipalityAdmin() ? 'Municipality Admin' : 'Staff' }}</td>
                            <td>{{ $account->municipality?->name ?? 'Unknown Municipality' }}</td>
                            <td>{{ optional($account->deleted_at)->format('M d, Y h:i A') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('super-admin.archive.accounts.restore', $account->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success"><i class="fas fa-rotate-left me-1"></i>Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No deleted municipality-admin or staff accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection