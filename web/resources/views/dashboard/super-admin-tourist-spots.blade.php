@extends('layouts.app')

@section('title', 'All Tourist Spots - Super Admin')
@section('header', '')

@section('content')
@php
    $resolveImageUrl = function (?string $imagePath): ?string {
        if (!$imagePath) return null;
        if (preg_match('#^https?://#i', $imagePath)) return $imagePath;
        return str_starts_with($imagePath, '/storage/') || str_starts_with($imagePath, 'storage/')
            ? url('/' . ltrim($imagePath, '/'))
            : asset('storage/' . ltrim($imagePath, '/'));
    };
@endphp
<style>
    .verification-card { display: flex; flex-direction: column; width: 100%; height: 430px; padding: 0; color: inherit; text-align: left; font: inherit; text-decoration: none; border: 1px solid #e6e9ef; border-radius: 16px; overflow: hidden; background: #fff; cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; }
    .verification-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(15, 23, 42, .08); }
    .verification-card:focus-visible { outline: 3px solid rgba(255, 107, 53, .45); outline-offset: 3px; }
    .verification-gallery { display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: minmax(0, 1fr); gap: 2px; height: 190px; flex: 0 0 190px; overflow: hidden; background: #eef1f6; }
    .verification-gallery img { width: 100%; height: 100%; min-height: 0; object-fit: cover; display: block; }
    .verification-gallery.single-image { display: block; }
    .verification-gallery.single-image img { height: 190px; }
    .verification-no-image { height: 190px; min-height: 190px; flex: 0 0 190px; display: flex; align-items: center; justify-content: center; color: #8a94a6; background: #f2f4f8; }
    .spot-card-content { min-width: 0; overflow: hidden; }
    .spot-card-title { display: -webkit-box; min-height: 2.8rem; overflow: hidden; line-clamp: 2; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .spot-card-address { display: -webkit-box; overflow: hidden; line-clamp: 2; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .verification-meta { color: #6b7280; font-size: .9rem; }
    #all-tourist-spots.list-view > [class*="col-"] { width: 100%; }
    #all-tourist-spots.list-view .verification-card { height: 230px; flex-direction: row; }
    #all-tourist-spots.list-view .verification-gallery,
    #all-tourist-spots.list-view .verification-no-image { width: 240px; height: 230px; min-height: 230px; flex: 0 0 240px; }
    #all-tourist-spots.list-view .verification-gallery img,
    #all-tourist-spots.list-view .verification-gallery.single-image img { height: 230px; }
    .pending-spot-list { max-height: 65vh; overflow-y: auto; }
    @media (max-width: 575.98px) {
        #all-tourist-spots.list-view .verification-card { height: 430px; flex-direction: column; }
        #all-tourist-spots.list-view .verification-gallery,
        #all-tourist-spots.list-view .verification-no-image { width: 100%; flex-basis: 190px; height: 190px; min-height: 190px; }
        #all-tourist-spots.list-view .verification-gallery img,
        #all-tourist-spots.list-view .verification-gallery.single-image img { height: 190px; }
    }
</style>

@section('header_actions')
<div class="btn-group btn-group-sm" role="group" aria-label="Tourist spot display mode">
    <button type="button" class="btn btn-tourism" data-spot-view="grid" aria-pressed="true"><i class="fas fa-grip me-1"></i>Grid</button>
    <button type="button" class="btn btn-outline-secondary" data-spot-view="list" aria-pressed="false"><i class="fas fa-list me-1"></i>List</button>
</div>
<button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#pendingTouristSpotsModal">
    <i class="fas fa-clock me-1"></i>Pending <span class="badge bg-warning text-dark ms-1">{{ $pendingTouristSpots->count() }}</span>
</button>
@endsection

@section('header_bottom')
<div class="text-muted small">{{ $touristSpots->count() }} total spots</div>
@endsection

@if($touristSpots->isNotEmpty())
    <div class="row g-4" id="all-tourist-spots">
        @foreach($touristSpots as $spot)
            @php
                $spotImages = collect($spot->image_urls ?? [])->map(fn ($image) => $resolveImageUrl($image))->filter()->values();
                $verificationStatus = $hasVerificationStatus && $spot->verification_status
                    ? ($spot->verification_status === 'approved' ? 'Verified' : ucfirst($spot->verification_status))
                    : (in_array($spot->status, ['pending', 'inactive'], true) ? 'Pending' : 'Recorded');
                $verificationBadgeClass = $verificationStatus === 'Pending'
                    ? 'bg-warning text-dark'
                    : ($verificationStatus === 'Verified' ? 'bg-success' : ($verificationStatus === 'Rejected' ? 'bg-danger' : 'bg-secondary'));
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <button type="button" class="verification-card" data-spot-preview
                    data-spot-id="{{ $spot->id }}"
                    data-spot-return-to="tourist-spots"
                    data-spot-name="{{ $spot->name }}"
                    data-spot-municipality="{{ $spot->municipality->name ?? 'Unknown Municipality' }}"
                    data-spot-barangay="{{ $spot->barangay }}"
                    data-spot-category="{{ ucfirst($spot->category ?? 'nature') }}"
                    data-spot-status="{{ $verificationStatus }}"
                    data-spot-description="{{ $spot->description }}"
                    data-spot-submitter="{{ $spot->creator?->name }}"
                    data-spot-submitter-contact="{{ $spot->creator?->email }}"
                    data-spot-submitted="{{ optional($spot->created_at)->format('M d, Y h:i A') }}"
                    data-spot-approved="{{ optional($spot->approvalEvent?->created_at)->format('M d, Y h:i A') }}"
                    data-spot-address="{{ $spot->address }}"
                    data-spot-hours="{{ $spot->opening_hours }}"
                    data-spot-fee="{{ $spot->entrance_fee === null ? '' : ($spot->entrance_fee == 0 ? 'Free' : 'PHP ' . number_format($spot->entrance_fee, 2)) }}"
                    data-spot-phone="{{ $spot->phone }}"
                    data-spot-website="{{ $spot->website }}"
                    data-spot-latitude="{{ $spot->latitude }}"
                    data-spot-longitude="{{ $spot->longitude }}"
                    data-spot-images="{{ json_encode($spot->image_urls) }}"
                    data-spot-image="{{ $spotImages->first() }}"
                    aria-label="View details for {{ $spot->name }}">
                    @if($spotImages->isNotEmpty())
                        <div class="verification-gallery {{ $spotImages->count() === 1 ? 'single-image' : '' }}">
                            @foreach($spotImages as $image)
                                <img src="{{ $image }}" alt="{{ $spot->name }} image {{ $loop->iteration }}">
                            @endforeach
                        </div>
                    @else
                        <div class="verification-no-image"><i class="fas fa-image me-2"></i> No images uploaded</div>
                    @endif
                    <div class="spot-card-content p-3 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="spot-card-title mb-0">{{ $spot->name }}</h5>
                            <span class="badge {{ $verificationBadgeClass }}">{{ $verificationStatus }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark border">{{ ucfirst($spot->category ?? 'nature') }}</span>
                            <span class="badge bg-light text-dark border">{{ $spot->municipality->name ?? 'Unknown Municipality' }}</span>
                        </div>
                        <div class="verification-meta mb-1"><i class="fas fa-calendar me-2"></i>Created {{ optional($spot->created_at)->format('M d, Y h:i A') }}</div>
                        @if($spot->creator)
                            <div class="verification-meta"><i class="fas fa-user me-2"></i>Added by {{ $spot->creator->name }}</div>
                        @endif
                        @if($spot->address)
                            <div class="verification-meta spot-card-address mt-2"><i class="fas fa-location-dot me-2"></i>{{ Str::limit($spot->address, 90) }}</div>
                        @endif
                    </div>
                </button>
            </div>
        @endforeach
    </div>
@else
    <div class="card"><div class="card-body text-center py-5 text-muted"><i class="fas fa-map-location-dot fa-2x mb-3 text-success"></i><div>No tourist spots found.</div></div></div>
@endif

<div class="modal fade" id="pendingTouristSpotsModal" tabindex="-1" aria-labelledby="pendingTouristSpotsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="pendingTouristSpotsModalLabel">Pending Tourist Spots</h5>
                    <small class="text-muted">{{ $pendingTouristSpots->count() }} awaiting review</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pending-spot-list">
                @forelse($pendingTouristSpots as $pendingSpot)
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 py-3 text-start w-100" data-spot-preview
                        data-spot-id="{{ $pendingSpot->id }}"
                        data-spot-return-to="tourist-spots"
                        data-spot-name="{{ $pendingSpot->name }}"
                        data-spot-municipality="{{ $pendingSpot->municipality->name ?? 'Unknown Municipality' }}"
                        data-spot-barangay="{{ $pendingSpot->barangay }}"
                        data-spot-category="{{ ucfirst($pendingSpot->category ?? 'nature') }}"
                        data-spot-status="Pending"
                        data-spot-description="{{ $pendingSpot->description }}"
                        data-spot-submitter="{{ $pendingSpot->creator?->name }}"
                        data-spot-submitter-contact="{{ $pendingSpot->creator?->email }}"
                        data-spot-submitted="{{ optional($pendingSpot->created_at)->format('M d, Y h:i A') }}"
                        data-spot-approved="{{ optional($pendingSpot->approvalEvent?->created_at)->format('M d, Y h:i A') }}"
                        data-spot-address="{{ $pendingSpot->address }}"
                        data-spot-hours="{{ $pendingSpot->opening_hours }}"
                        data-spot-fee="{{ $pendingSpot->entrance_fee === null ? '' : ($pendingSpot->entrance_fee == 0 ? 'Free' : 'PHP ' . number_format($pendingSpot->entrance_fee, 2)) }}"
                        data-spot-phone="{{ $pendingSpot->phone }}"
                        data-spot-website="{{ $pendingSpot->website }}"
                        data-spot-latitude="{{ $pendingSpot->latitude }}"
                        data-spot-longitude="{{ $pendingSpot->longitude }}"
                        data-spot-images="{{ json_encode($pendingSpot->image_urls) }}"
                        data-spot-image="{{ $pendingSpot->primary_image_url }}">
                            <div class="fw-semibold">{{ $pendingSpot->name }}</div>
                            <div class="small text-muted">{{ $pendingSpot->municipality->name ?? 'Unknown Municipality' }} · {{ optional($pendingSpot->created_at)->format('M d, Y') }}</div>
                        <i class="fas fa-arrow-right text-muted" aria-hidden="true"></i>
                    </button>
                @empty
                    <div class="text-center text-muted py-5"><i class="fas fa-circle-check fa-2x mb-3 text-success"></i><div>No pending tourist spots.</div></div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@include('dashboard.partials.tourist-spot-preview-modal')

<script>
    const spotCollection = document.getElementById('all-tourist-spots');
    document.querySelectorAll('[data-spot-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const isListView = button.dataset.spotView === 'list';
            spotCollection?.classList.toggle('list-view', isListView);
            document.querySelectorAll('[data-spot-view]').forEach((viewButton) => {
                const isSelected = viewButton === button;
                viewButton.setAttribute('aria-pressed', String(isSelected));
                viewButton.classList.toggle('btn-tourism', isSelected);
                viewButton.classList.toggle('btn-outline-secondary', !isSelected);
            });
        });
    });
</script>
@endsection
