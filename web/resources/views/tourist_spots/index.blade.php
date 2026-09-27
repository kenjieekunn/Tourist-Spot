@extends('layouts.app')

@section('title', 'Tourist Spots')
@section('header', '')

@section('header_bottom')
<div class="spot-header-bottom">
    <form method="GET" action="{{ route('tourist_spots.index') }}" class="row g-2 align-items-end mx-0">
        <div class="col-12 col-lg-4"><label for="spot-search" class="form-label small fw-semibold">Search</label><input type="search" class="form-control" id="spot-search" name="q" value="{{ $searchTerm }}" placeholder="Search spot or municipality"></div>
        <div class="col-12 col-md-4 col-lg-2"><label for="municipality-filter" class="form-label small fw-semibold">Municipality</label><select class="form-select" id="municipality-filter" name="municipality_id"><option value="0" @selected($municipalityId === 0)>All Municipalities</option>@foreach($municipalities as $municipality)<option value="{{ $municipality->id }}" @selected($municipalityId === $municipality->id)>{{ $municipality->name }}</option>@endforeach</select></div>
        <div class="col-12 col-md-4 col-lg-2"><label for="status-filter" class="form-label small fw-semibold">Status</label><select class="form-select" id="status-filter" name="status"><option value="all" @selected($statusFilter === 'all')>All Statuses</option><option value="approved" @selected($statusFilter === 'approved')>Verified</option><option value="pending" @selected($statusFilter === 'pending')>Pending</option><option value="closed" @selected($statusFilter === 'closed')>Closed</option></select></div>
        <input type="hidden" name="view" value="{{ $viewMode }}">
        <div class="col-6 col-lg-2"><button type="submit" class="btn btn-tourism w-100"><i class="fas fa-filter me-1"></i> Apply Filters</button></div>
        <div class="col-6 col-lg-2"><a href="{{ route('tourist_spots.index') }}" class="btn btn-outline-secondary w-100">Reset Filters</a></div>
    </form>
</div>
@endsection

@section('content')
@php
    $totalVisibleSpots = $spots->count();
    $queryParams = ['q' => $searchTerm, 'municipality_id' => $municipalityId, 'status' => $statusFilter];
@endphp
<style>
    .spots-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .spots-page .spot-header { border: 1px solid #c8e5da; border-radius: 10px; padding: 1rem 1.25rem; background: linear-gradient(135deg, #effaf7, #f7fbf3); }
    .spots-page .spot-category-section { border: 1px solid #dce9e5; border-radius: 10px; background: #fff; overflow: hidden; }
    .spots-page .spot-category-header { background: #f0faf6; border-bottom: 1px solid #dce9e5; color: var(--tourism-ink); cursor: pointer; }
    .spots-page .spot-card { border: 1px solid #dce9e5; border-radius: 8px; transition: transform .15s ease, box-shadow .15s ease; background: #fff; overflow: hidden; position: relative; }
    .spots-page .spot-card:hover { transform: translateY(-2px); box-shadow: 0 10px 18px rgba(23, 63, 67, .1); }
    .spots-page .spot-card-trigger { border: 0; padding: 0; color: inherit; background: transparent; font: inherit; cursor: pointer; }
    .spots-page .spot-image, .spots-page .spot-image-placeholder { width: 100%; height: 145px; object-fit: cover; display: flex; align-items: center; justify-content: center; }
    .spots-page .spot-image-placeholder { background: #fff8df; color: #946c10; flex-direction: column; gap: .35rem; }
    .spots-page .spot-image-placeholder i { font-size: 1.45rem; }
    .spots-page .spot-meta { color: #6b7280; font-size: .82rem; }
    .spots-page .category-badge { background: #d9f3ec; color: #12665f; border: 1px solid #b8e3d7; }
    .spots-page .municipality-badge { background: #d9f3ec; color: #12665f; border: 1px solid #b8e3d7; }
    .spots-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .spots-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .spots-page .status-operational { background: transparent; border: 1px solid #6b7280; color: #4b5563; }
    .spots-page .spots-table th { background: #eef8f5; color: var(--tourism-ink); }
    .spots-page .spots-table tbody tr:hover { background: #f0faf6; }
</style>

<div class="spots-page">
    <div class="spot-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h2 class="h4 mb-1">Tourist Spots <span class="badge rounded-pill text-bg-light border">{{ $totalVisibleSpots }} total spots</span></h2><p class="text-muted mb-0">Cross-municipality tourism listings and verification status.</p></div>
        <div class="d-flex gap-2 align-items-center"><div class="btn-group btn-group-sm" role="group" aria-label="Spot view"><a href="{{ route('tourist_spots.index', $queryParams + ['view' => 'grid']) }}" class="btn {{ $viewMode === 'grid' ? 'btn-tourism' : 'btn-outline-secondary' }}"><i class="fas fa-grip"></i> Grid</a><a href="{{ route('tourist_spots.index', $queryParams + ['view' => 'list']) }}" class="btn {{ $viewMode === 'list' ? 'btn-tourism' : 'btn-outline-secondary' }}"><i class="fas fa-list"></i> List</a></div></div>
    </div>

    @if($totalVisibleSpots > 0 && $viewMode === 'grid')
        @foreach($spotCategories as $categoryKey => $categoryLabel)
            @php $categorySpots = $groupedSpots[$categoryKey] ?? collect(); @endphp
            @if($categorySpots->isNotEmpty())
                <div class="spot-category-section mb-3">
                    <button class="spot-category-header w-100 border-0 d-flex justify-content-between align-items-center py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#category-{{ $categoryKey }}" aria-expanded="true" aria-controls="category-{{ $categoryKey }}"><span><span class="badge category-badge me-2">{{ $categoryLabel }}</span><small class="text-muted">{{ $categorySpots->count() }} spot(s)</small></span><i class="fas fa-chevron-up"></i></button>
                    <div class="collapse show" id="category-{{ $categoryKey }}"><div class="p-3"><div class="row g-3">@foreach($categorySpots as $spot)<div class="col-12 col-md-6 col-xl-3"><button type="button" class="spot-card-trigger h-100 w-100 d-flex flex-column text-start" data-spot-preview data-spot-id="{{ $spot->id }}" data-spot-name="{{ $spot->name }}" data-spot-municipality="{{ $spot->municipality->name ?? 'Unknown Municipality' }}" data-spot-barangay="{{ $spot->barangay }}" data-spot-category="{{ ucfirst($spot->category ?? 'nature') }}" data-spot-status="{{ $spot->verification_status === 'approved' ? 'Verified' : ucfirst($spot->verification_status ?? 'Recorded') }}" data-spot-description="{{ $spot->description }}" data-spot-address="{{ $spot->address }}" data-spot-hours="{{ $spot->opening_hours }}" data-spot-fee="{{ $spot->entrance_fee === null ? '' : ($spot->entrance_fee == 0 ? 'Free' : 'PHP ' . number_format($spot->entrance_fee, 2)) }}" data-spot-phone="{{ $spot->phone }}" data-spot-website="{{ $spot->website }}" data-spot-latitude="{{ $spot->latitude }}" data-spot-longitude="{{ $spot->longitude }}" data-spot-images="{{ json_encode($spot->image_urls) }}" data-spot-image="{{ $spot->primary_image_url }}" aria-label="View {{ $spot->name }} details"><div class="spot-card h-100 d-flex flex-column">@if($spot->image_url)<img src="{{ preg_match('#^https?://#i', $spot->image_url) ? $spot->image_url : url($spot->image_url) }}" alt="{{ $spot->name }} image" class="spot-image">@else<div class="spot-image-placeholder"><i class="fas fa-camera"></i><span>No image available</span></div>@endif<div class="p-3 d-flex flex-column h-100"><h6 class="mb-1">{{ $spot->name }}</h6><div class="spot-meta mb-2">{{ Str::limit($spot->address, 55) }}</div><div class="d-flex flex-wrap gap-1 mb-2"><span class="badge municipality-badge">{{ $spot->municipality->name ?? 'Unknown Municipality' }}</span><span class="badge category-badge">{{ $categoryLabel }}</span></div><div class="d-flex flex-wrap gap-1"><span class="badge {{ $spot->verification_status === 'approved' ? 'bg-success' : ($spot->verification_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ $spot->verification_status === 'approved' ? 'Verified' : ucfirst($spot->verification_status ?? 'Recorded') }}</span><span class="badge status-operational">{{ in_array($spot->status, ['open', 'active']) ? 'Open' : 'Closed' }}</span></div></div></div></button></div>@endforeach</div></div></div>
                </div>
            @endif
        @endforeach
    @elseif($totalVisibleSpots > 0)
        <div class="card"><div class="table-responsive"><table class="table spots-table table-hover align-middle mb-0"><thead><tr><th>Tourist Spot</th><th>Municipality</th><th>Category</th><th>Verification</th><th>Operational</th></tr></thead><tbody>@foreach($spots as $spot)<tr><td><button type="button" class="btn btn-link p-0 text-start text-decoration-none fw-bold" data-spot-preview data-spot-id="{{ $spot->id }}" data-spot-name="{{ $spot->name }}" data-spot-municipality="{{ $spot->municipality->name ?? 'Unknown Municipality' }}" data-spot-barangay="{{ $spot->barangay }}" data-spot-category="{{ ucfirst($spot->category ?? 'nature') }}" data-spot-status="{{ $spot->verification_status === 'approved' ? 'Verified' : ucfirst($spot->verification_status ?? 'Recorded') }}" data-spot-description="{{ $spot->description }}" data-spot-address="{{ $spot->address }}" data-spot-hours="{{ $spot->opening_hours }}" data-spot-fee="{{ $spot->entrance_fee === null ? '' : ($spot->entrance_fee == 0 ? 'Free' : 'PHP ' . number_format($spot->entrance_fee, 2)) }}" data-spot-phone="{{ $spot->phone }}" data-spot-website="{{ $spot->website }}" data-spot-latitude="{{ $spot->latitude }}" data-spot-longitude="{{ $spot->longitude }}" data-spot-images="{{ json_encode($spot->image_urls) }}" data-spot-image="{{ $spot->primary_image_url }}" aria-label="View {{ $spot->name }} details">{{ $spot->name }}</button><div class="small text-muted">{{ Str::limit($spot->address, 70) }}</div></td><td><span class="badge municipality-badge">{{ $spot->municipality->name ?? 'Unknown' }}</span></td><td>{{ ucfirst($spot->category ?? 'nature') }}</td><td><span class="badge {{ $spot->verification_status === 'approved' ? 'bg-success' : ($spot->verification_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ $spot->verification_status === 'approved' ? 'Verified' : ucfirst($spot->verification_status ?? 'Recorded') }}</span></td><td><span class="badge status-operational">{{ in_array($spot->status, ['open', 'active']) ? 'Open' : 'Closed' }}</span></td></tr>@endforeach</tbody></table></div></div>
    @else
        <div class="card"><div class="card-body text-center py-5"><i class="fas fa-location-dot fa-2x text-muted mb-3"></i><h5>No tourist spots found</h5><p class="text-muted mb-0">Try changing the municipality or status filters.</p></div></div>
    @endif
</div>
@include('dashboard.partials.tourist-spot-preview-modal')
<script>
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (button) { button.addEventListener('click', function () { const icon = button.querySelector('i'); setTimeout(function () { icon.className = button.getAttribute('aria-expanded') === 'true' ? 'fas fa-chevron-up' : 'fas fa-chevron-down'; }, 0); }); });
</script>
@endsection
