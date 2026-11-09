@extends('layouts.app')

@section('title', 'Archive - Super Admin')
@section('header', '')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="mb-1">Archive</h4>
        <p class="text-muted mb-0">Archived municipality accounts and deleted tourist spots.</p>
    </div>
    <span class="badge text-bg-secondary">{{ $archivedUsers->count() + $archivedTouristSpots->count() }} records</span>
</div>

<section class="mb-4" aria-labelledby="archived-accounts-heading">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 id="archived-accounts-heading" class="mb-0">Admin and Staff Accounts</h5>
        <span class="text-muted small">{{ $archivedUsers->count() }} archived</span>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Account type</th>
                        <th scope="col">Municipality</th>
                        <th scope="col">Archived</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archivedUsers as $archivedUser)
                        <tr>
                            <td>{{ $archivedUser->name }}<div class="small text-muted">{{ $archivedUser->email }}</div></td>
                            <td>{{ $archivedUser->role === 'municipality-admin' ? 'Municipality Admin' : 'Staff' }}</td>
                            <td>{{ $archivedUser->municipality?->name ?? '-' }}</td>
                            <td>{{ $archivedUser->deleted_at?->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No archived admin or staff accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section aria-labelledby="archived-spots-heading">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 id="archived-spots-heading" class="mb-0">Deleted Tourist Spots</h5>
        <span class="text-muted small">{{ $archivedTouristSpots->count() }} archived</span>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Tourist spot</th>
                        <th scope="col">Municipality</th>
                        <th scope="col">Category</th>
                        <th scope="col">Archived</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archivedTouristSpots as $archivedSpot)
                        <tr>
                            <td>{{ $archivedSpot->name }}</td>
                            <td>{{ $archivedSpot->municipality?->name ?? '-' }}</td>
                            <td>{{ ucfirst($archivedSpot->category ?? 'Uncategorized') }}</td>
                            <td>{{ $archivedSpot->deleted_at?->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No deleted tourist spots.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection