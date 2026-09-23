@extends('layouts.app')

@section('title', 'Tourist Spot Verification: ' . $spot->name)
@section('header', 'Tourist Spot Verification: ' . $spot->name)

@section('header_actions')
@php
    $currentUser = auth()->user();
    $isSuperAdminPendingSpot = $currentUser && $currentUser->isSuperAdmin() && $spot->isPendingVerification();
    $canEditSpot = $currentUser && $currentUser->isAdmin() && ! $currentUser->isSuperAdmin();
@endphp
@if($isSuperAdminPendingSpot)
    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveSpotModal"><i class="fas fa-check"></i> Verify</button>
    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#revisionSpotModal"><i class="fas fa-rotate-left"></i> Request Revision</button>
@elseif($canEditSpot)
    <a href="{{ route('tourist_spots.edit', $spot->id) }}" class="btn btn-sm btn-warning">
        <i class="fas fa-edit"></i> Edit
    </a>
@endif
@endsection

@section('content')
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}"></script>
<style>
    .verification-image-gallery {
        padding: 0.75rem;
        border: 1px solid #e6e9ef;
        border-radius: 14px;
        background: #f8fafc;
    }
    .verification-page { --tourism-teal: #0f766e; --tourism-ink: #173f43; }
    .verification-page .page-heading { border: 1px solid #f1d487; border-radius: 10px; padding: 1rem 1.25rem; background: linear-gradient(135deg, #fff8df, #fff); color: var(--tourism-ink); }
    .verification-page .gallery-heading { color: var(--tourism-teal); }
    .verification-page .no-photos { border: 1px solid #f1d487; border-radius: 10px; background: #fff8df; color: #765b13; padding: 1.25rem; }
    .spot-hero-image {
        width: 100%;
        max-height: 430px;
        object-fit: contain;
        background: #eef1f6;
        border-radius: 12px;
    }
    .verification-image-gallery button {
        display: block;
        overflow: hidden;
        border-radius: 10px !important;
    }
    .verification-image-gallery img {
        transition: transform 0.2s ease, filter 0.2s ease;
    }
    .verification-image-gallery button:hover img {
        transform: scale(1.04);
        filter: brightness(0.9);
    }
    .spot-lightbox-image { max-height: 72vh; object-fit: contain; }
    .spot-lightbox-nav { align-items: center; background: rgba(15, 118, 110, .9); border: 0; border-radius: 50%; color: #fff; display: inline-flex; height: 2.5rem; justify-content: center; width: 2.5rem; }
    .spot-details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }
    .spot-detail-item {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        padding: 0.85rem;
        border: 1px solid #e6e9ef;
        border-radius: 10px;
        background: #fbfcfe;
    }
    .spot-detail-item i { width: 1.25rem; color: #0f766e; text-align: center; margin-top: 0.15rem; }
    .spot-detail-label { display: block; color: #6b7280; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
    .spot-status-chip { display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 999px; padding: 0.4rem 0.75rem; font-weight: 700; }
    .spot-status-chip.open { color: #166534; background: #dcfce7; }
    .spot-status-chip.closed { color: #374151; background: #e5e7eb; }
    .metric-card { border: 1px solid #e6e9ef; border-radius: 12px; padding: 1rem; background: #fbfcfe; height: 100%; }
    .metric-value { font-size: 1.7rem; font-weight: 800; color: #164e63; }
    .metric-label { color: #6b7280; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; }
    .verification-timeline { border-left: 2px solid #b8e3d7; margin-left: .6rem; padding-left: 1.2rem; }
    .verification-event { position: relative; padding-bottom: 1rem; }
    .verification-event::before { background: #0f766e; border: 3px solid #e5f5f1; border-radius: 50%; content: ''; height: .8rem; left: -1.65rem; position: absolute; top: .25rem; width: .8rem; }
    .map-frame { min-height: 360px; }
    #verificationMap { min-height: 360px; width: 100%; }
    .map-legend { color: #526b67; font-size: .8rem; }
    @media (max-width: 767.98px) { .spot-details-grid { grid-template-columns: 1fr; } }
</style>
@php
    $facilityList = collect($spot->nearby_facilities ?? []);
    $derivedDining = $facilityList->where('type', 'dining')->pluck('name')->unique()->implode(', ');
    $derivedGas = $facilityList->where('type', 'gas_station')->pluck('name')->unique()->implode(', ');
    $spotImages = collect($spot->image_urls ?? []);
    $googleMapEmbedUrl = (!empty($spot->latitude) && !empty($spot->longitude))
        ? 'https://www.google.com/maps?q=' . urlencode($spot->latitude . ',' . $spot->longitude) . '&z=16&output=embed'
        : null;
    $googleMapViewUrl = (!empty($spot->latitude) && !empty($spot->longitude))
        ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($spot->latitude . ',' . $spot->longitude)
        : null;
    $resolveImageUrl = function (?string $imagePath): ?string {
        if (!$imagePath) {
            return null;
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (str_starts_with($imagePath, '/storage/')) {
            return url($imagePath);
        }

        if (str_starts_with($imagePath, 'storage/')) {
            return url('/' . ltrim($imagePath, '/'));
        }

        return asset('storage/' . ltrim($imagePath, '/'));
    };
@endphp

<div class="verification-page">
    <div class="page-heading mb-4"><div class="small text-uppercase fw-bold" style="color: var(--tourism-teal); letter-spacing: .06em;">Pangasinan 2nd District</div><h2 class="h4 mb-0">Tourist Spot Verification: {{ $spot->name }}</h2></div>
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">{{ $spot->name }}</h4>
                        <span class="badge bg-light text-dark border">{{ ucfirst($spot->category ?? 'nature') }}</span>
                    </div>
                    @php $verificationLabel = $spot->isPendingVerification() && str_starts_with((string) ($spot->rejection_reason ?? ''), 'Revision requested:') ? 'Revision Requested' : ($spot->isPendingVerification() ? 'Pending Verification' : ucfirst($spot->verification_status ?? 'Recorded')); @endphp
                    <span class="badge {{ $verificationLabel === 'Revision Requested' ? 'bg-warning text-dark' : ($spot->isPendingVerification() ? 'bg-warning text-dark' : ($spot->isVerified() ? 'bg-success' : 'bg-danger')) }}">{{ $verificationLabel }}</span>
                </div>
                @php
                    $isParkSpot = ($spot->category ?? 'nature') === 'parks';
                @endphp

                @if($spotImages->isNotEmpty())
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 gallery-heading">Uploaded Photos for Verification</h6>
                            <span class="badge bg-info text-dark">{{ $spotImages->count() }} image(s)</span>
                        </div>
                        <div class="verification-image-gallery row g-2">
                            @foreach($spotImages as $spotImage)
                                @php $resolvedSpotImage = $resolveImageUrl($spotImage); @endphp
                                <div class="col-6 col-md-4">
                                    <button
                                        type="button"
                                        class="btn p-0 border-0 w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#spotImageModal"
                                        data-spot-image="{{ $resolvedSpotImage }}"
                                        data-spot-name="{{ $spot->name }}"
                                        onclick="setSpotImage(this)"
                                    >
                                        <img src="{{ $resolvedSpotImage }}" alt="{{ $spot->name }} image" class="img-fluid rounded" style="height: 155px; width: 100%; object-fit: cover;">
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($spot->image_url)
                    @php $resolvedPrimaryImage = $resolveImageUrl($spot->image_url); @endphp
                    <div class="mb-3">
                        <img src="{{ $resolvedPrimaryImage }}" alt="{{ $spot->name }}" class="spot-hero-image">
                    </div>
                @else
                    <div class="no-photos mb-4"><i class="fas fa-triangle-exclamation me-2"></i><strong>No photos uploaded.</strong> Photo evidence is required before this spot can be confidently verified.</div>
                @endif
                @if($spotImages->isNotEmpty() || $spot->image_url)
                    <div class="small text-muted mb-4"><i class="fas fa-camera me-1"></i> Uploaded by {{ $spot->creator?->name ?? $spot->municipality?->admins?->first()?->name ?? 'Unassigned municipality admin' }} on {{ optional($spot->created_at)->format('M d, Y') }}</div>
                @endif
                @if($spot->rejection_reason)
                    <div class="alert {{ str_starts_with($spot->rejection_reason, 'Revision requested:') ? 'alert-warning' : 'alert-danger' }} py-2"><strong>{{ str_starts_with($spot->rejection_reason, 'Revision requested:') ? 'Revision note:' : 'Rejection reason:' }}</strong> {{ str_replace('Revision requested: ', '', $spot->rejection_reason) }}</div>
                @endif

                <div class="spot-details-grid mb-4">
                    <div class="spot-detail-item"><i class="fas fa-location-dot"></i><div><span class="spot-detail-label">Municipality</span>{{ $spot->municipality->name ?? 'N/A' }}</div></div>
                    <div class="spot-detail-item"><i class="fas fa-map-pin"></i><div><span class="spot-detail-label">Address</span>{{ collect([$spot->barangay, $spot->address, $spot->municipality->name ?? null])->filter()->implode(', ') ?: 'N/A' }}<small class="d-block text-muted">{{ $spot->latitude }}, {{ $spot->longitude }}</small></div></div>
                    <div class="spot-detail-item"><i class="fas fa-utensils"></i><div><span class="spot-detail-label">Nearby Dining</span>{{ $spot->nearby_dining ?: ($derivedDining ?: 'N/A') }}</div></div>
                    <div class="spot-detail-item"><i class="fas fa-gas-pump"></i><div><span class="spot-detail-label">Nearby Gas Stations</span>{{ $spot->nearby_gas_stations ?: ($derivedGas ?: 'N/A') }}</div></div>
                    <div class="spot-detail-item"><i class="fas fa-door-open"></i><div><span class="spot-detail-label">Status</span>@php $isOpen = in_array($spot->status, ['open', 'active']); $statusLabel = ['under_maintenance' => 'Under Maintenance', 'seasonal' => 'Seasonal'][$spot->status] ?? ($isOpen ? 'Open' : 'Closed'); @endphp<span class="spot-status-chip {{ $isOpen ? 'open' : 'closed' }}" title="{{ $spot->status_reason ?: ($isOpen ? 'Currently open for visitors' : 'Not currently listed as open') }}"><i class="fas fa-circle"></i>{{ $statusLabel }}</span>@if($spot->status_reason)<small class="d-block text-muted mt-1">{{ $spot->status_reason }}</small>@endif</div></div>
                    @if($isParkSpot)
                        <div class="spot-detail-item"><i class="fas fa-calendar-days"></i><div><span class="spot-detail-label">Opening Days</span>
                                @if($spot->opening_days)
                                    @php
                                        $dayLabels = [
                                            'mon' => 'Monday',
                                            'tue' => 'Tuesday',
                                            'wed' => 'Wednesday',
                                            'thu' => 'Thursday',
                                            'fri' => 'Friday',
                                            'sat' => 'Saturday',
                                            'sun' => 'Sunday'
                                        ];
                                        $days = is_array($spot->opening_days) ? $spot->opening_days : json_decode($spot->opening_days, true) ?? [];
                                    @endphp
                                    {{ collect($days)->map(fn($d) => $dayLabels[$d] ?? ucfirst($d))->implode(', ') }}
                                @else
                                    N/A
                                @endif
                        </div></div>
                        <div class="spot-detail-item"><i class="fas fa-clock"></i><div><span class="spot-detail-label">Hours</span>{{ $spot->opening_time ?: 'N/A' }} - {{ $spot->closing_time ?: 'N/A' }}</div></div>
                    @endif
                </div>

                <hr>

                <h5 class="text-teal">Description</h5>
                <p class="fs-5 lh-lg">{{ $spot->description }}</p>
            </div>
        </div>

        @if($spot->isVerified())
        <div class="card" id="spot-reviews-card" data-spot-id="{{ $spot->id }}">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="m-0"><i class="fas fa-comments"></i> Reviews (<span id="spot-reviews-count">{{ $spot->reviews->count() }}</span>)</h5>
            </div>
            <div class="card-body p-4" id="spot-reviews-body">
                @if($spot->reviews->count() > 0)
                    @foreach($spot->reviews as $review)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>{{ $review->user_name }}</strong>
                                    <br>
                                    <small class="text-warning">
                                        @for($i = 0; $i < $review->rating; $i++)
                                            <i class="fas fa-star"></i>
                                        @endfor
                                        ({{ $review->rating }}/5)
                                    </small>
                                </div>
                                <small class="text-muted">{{ $review->created_at->format('M d, Y') }}</small>
                            </div>
                            <p class="mb-2">{{ $review->comment }}</p>
                            
                            <!-- Display Review Images -->
                            @if($review->images && count($review->images) > 0)
                                <div class="mb-2">
                                    <div class="row g-2">
                                        @foreach($review->images as $imagePath)
                                            <div class="col-auto">
                                                <img src="{{ asset('storage/' . $imagePath) }}" 
                                                     alt="Review image" 
                                                     class="review-image-thumbnail"
                                                     style="max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#imageModal"
                                                     onclick="document.getElementById('modalImage').src = this.src">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <small class="text-muted">
                                Status: <span class="badge bg-{{ $review->status === 'approved' ? 'success' : ($review->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($review->status) }}
                                </span>
                            </small>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted">No reviews yet.</div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="m-0"><i class="fas fa-map-marked-alt"></i> Map</h5>
            </div>
            <div class="card-body p-3">
                @if(!empty($spot->latitude) && !empty($spot->longitude))
                    <div id="verificationMap" class="ratio ratio-16x9 map-frame rounded overflow-hidden border" aria-label="Map showing {{ $spot->name }} and nearby facilities"></div>
                    @if($facilityList->isNotEmpty())<div class="map-legend mt-2"><i class="fas fa-location-dot text-danger me-1"></i> Main spot <span class="ms-3"><i class="fas fa-utensils me-1" style="color:#ff6b35"></i> Dining</span> <span class="ms-3"><i class="fas fa-gas-pump text-primary me-1"></i> Gas station</span></div>@endif
                    <div class="mt-3">
                        @if($googleMapViewUrl)
                            <a href="{{ $googleMapViewUrl }}" target="_blank" rel="noopener" class="btn btn-success w-100">
                                <i class="fas fa-map-location-dot"></i> Open in Google Maps
                            </a>
                        @endif
                    </div>
                @else
                    <div class="p-4 text-muted small">Location coordinates are not available for this spot.</div>
                @endif
            </div>
        </div>

        @if($spot->isVerified())
        <div class="card">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="m-0"><i class="fas fa-stats"></i> Statistics</h5>
            </div>
            <div class="card-body">
                @php $averageRating = $spot->getAverageRating(); $reviewCount = $spot->reviews->count(); @endphp
                <div class="row g-3">
                    <div class="col-12">
                        <div class="metric-card">
                            <div class="metric-label"><i class="fas fa-star me-1"></i> Average Rating</div>
                            <div class="metric-value" id="spot-avg-rating">{{ $reviewCount ? number_format($averageRating, 1) . '/5' : 'No ratings yet' }}</div>
                            <div class="text-warning" aria-label="{{ $reviewCount ? number_format($averageRating, 1) . ' out of 5 stars' : 'No ratings yet' }}">
                                @for($star = 1; $star <= 5; $star++)<i class="{{ $reviewCount && $star <= round($averageRating) ? 'fas' : 'far' }} fa-star"></i>@endfor
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="metric-card">
                            <div class="metric-label"><i class="fas fa-comments me-1"></i> Total Reviews</div>
                            <div class="metric-value" id="spot-reviews-total">{{ $reviewCount }}</div>
                            <div class="text-muted small">Reviews submitted for this spot</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-white border-bottom p-4"><h5 class="m-0"><i class="fas fa-timeline text-success me-2"></i>Verification History / Notes</h5></div>
    <div class="card-body p-4">
        @if(isset($verificationEvents) && $verificationEvents->isNotEmpty())
            <div class="verification-timeline">@foreach($verificationEvents as $event)<div class="verification-event"><strong>{{ ucwords(str_replace('_', ' ', $event->action)) }}</strong><div class="small text-muted">{{ optional($event->created_at)->format('M d, Y h:i A') }}</div>@if($event->note)<div class="mt-1">{{ $event->note }}</div>@endif</div>@endforeach</div>
        @else
            <div class="text-muted">No verification history recorded yet. This may be the first review of this submission.</div>
        @endif
    </div>
</div>
</div>

@if($isSuperAdminPendingSpot)
<div class="modal fade" id="approveSpotModal" tabindex="-1" aria-labelledby="approveSpotModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="approveSpotModalLabel">Verify this tourist spot?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">This will publish {{ $spot->name }} as an approved tourist spot.</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><form action="{{ route('super-admin.spots.approve', $spot) }}" method="POST">@csrf<button type="submit" class="btn btn-success">Verify Spot</button></form></div></div></div></div>
<div class="modal fade" id="revisionSpotModal" tabindex="-1" aria-labelledby="revisionSpotModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="{{ route('super-admin.spots.request-revision', $spot) }}" method="POST">@csrf<div class="modal-header"><h5 class="modal-title" id="revisionSpotModalLabel">Request revision</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><label for="revisionReason" class="form-label">What needs more detail?</label><textarea class="form-control" id="revisionReason" name="reason" rows="4" required placeholder="Example: Please upload clearer beach photos and add the barangay address."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning">Request Revision</button></div></form></div></div></div>
@endif

<!-- Image Modal for Review Images -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Review Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Review image" style="max-width: 100%; height: auto; border-radius: 4px;">
            </div>
        </div>
    </div>
</div>

<!-- Image Modal for Tourist Spot Images -->
<div class="modal fade" id="spotImageModal" tabindex="-1" aria-labelledby="spotImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="spotImageModalLabel"><span id="spotModalImageTitle">Tourist Spot Image</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center position-relative">
                <button type="button" class="spot-lightbox-nav position-absolute start-0 top-50 translate-middle-y" id="spotImagePrevious" aria-label="Previous photo"><i class="fas fa-chevron-left"></i></button>
                <img id="spotModalImage" src="" alt="Tourist spot image" class="spot-lightbox-image" style="max-width: 90%; height: auto; border-radius: 4px;">
                <button type="button" class="spot-lightbox-nav position-absolute end-0 top-50 translate-middle-y" id="spotImageNext" aria-label="Next photo"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.initVerificationMap = function () {
        const mapElement = document.getElementById('verificationMap');
        if (!mapElement || !window.google?.maps) return;

        const spot = { lat: {{ (float) $spot->latitude }}, lng: {{ (float) $spot->longitude }} };
        const facilities = @json($facilityList->filter(fn ($facility) => isset($facility['latitude'], $facility['longitude'], $facility['name']))->values());
        const map = new google.maps.Map(mapElement, {
            center: spot,
            zoom: facilities.length ? 15 : 16,
            mapTypeControl: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            streetViewControl: false
        });
        const bounds = new google.maps.LatLngBounds();
        const infoWindow = new google.maps.InfoWindow();
        const mainMarker = new google.maps.Marker({
            position: spot,
            map,
            title: @json($spot->name),
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#dc2626', fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2 }
        });
        mainMarker.addListener('click', () => {
            infoWindow.setContent('<strong>' + @json($spot->name) + '</strong><br>Main tourist spot');
            infoWindow.open({ map, anchor: mainMarker });
        });
        bounds.extend(spot);

        facilities.forEach((facility) => {
            const position = { lat: Number(facility.latitude), lng: Number(facility.longitude) };
            if (!Number.isFinite(position.lat) || !Number.isFinite(position.lng)) return;
            const isDining = facility.type === 'dining';
            const marker = new google.maps.Marker({
                position,
                map,
                title: facility.name,
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: isDining ? '#ff6b35' : '#0d6efd', fillOpacity: .9, strokeColor: '#fff', strokeWeight: 2 }
            });
            marker.addListener('click', () => {
                const safeName = String(facility.name).replace(/[&<>]/g, '');
                infoWindow.setContent('<strong>' + safeName + '</strong><br>' + (isDining ? 'Nearby dining' : 'Nearby gas station'));
                infoWindow.open({ map, anchor: marker });
            });
            bounds.extend(position);
        });
        if (facilities.length) map.fitBounds(bounds, 48);
    };
    window.addEventListener('load', window.initVerificationMap);
</script>
<script>
    window.spotGalleryImages = @json($spotImages->map(fn ($image) => $resolveImageUrl($image))->values());
    window.spotGalleryIndex = 0;
    window.setSpotImage = function (trigger) {
        const image = trigger?.getAttribute('data-spot-image') || '';
        const name = trigger?.getAttribute('data-spot-name') || 'Tourist Spot Image';
        const index = window.spotGalleryImages.indexOf(image);
        window.spotGalleryIndex = index >= 0 ? index : 0;

        const imageEl = document.getElementById('spotModalImage');
        const titleEl = document.getElementById('spotModalImageTitle');

        if (imageEl) {
            imageEl.src = image;
        }

        if (titleEl) {
            titleEl.textContent = name;
        }
    };
    window.stepSpotImage = function (step) {
        const images = window.spotGalleryImages || [];
        if (!images.length) return;
        window.spotGalleryIndex = (window.spotGalleryIndex + step + images.length) % images.length;
        document.getElementById('spotModalImage').src = images[window.spotGalleryIndex];
    };
    document.getElementById('spotImagePrevious')?.addEventListener('click', () => window.stepSpotImage(-1));
    document.getElementById('spotImageNext')?.addEventListener('click', () => window.stepSpotImage(1));
    document.getElementById('spotImageModal')?.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') window.stepSpotImage(-1);
        if (event.key === 'ArrowRight') window.stepSpotImage(1);
    });
</script>
<script>
    (() => {
        const card = document.getElementById('spot-reviews-card');
        const body = document.getElementById('spot-reviews-body');
        const countEl = document.getElementById('spot-reviews-count');
        const totalEl = document.getElementById('spot-reviews-total');
        const avgEl = document.getElementById('spot-avg-rating');
        if (!card || !body || !countEl) return;

        const endpoint = "{{ route('tourist_spots.reviews_json', $spot->id) }}";
        let isFetching = false;

        const escapeHtml = (value) => {
            const safe = String(value);
            return safe.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const formatDate = (value) => {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '';
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
            });
        };

        const renderReviews = (reviews) => {
            countEl.textContent = reviews.length;
            if (totalEl) totalEl.textContent = reviews.length;
            if (avgEl) {
                const avg = reviews.length
                    ? reviews.reduce((sum, review) => sum + (review.rating || 0), 0) / reviews.length
                    : 0;
                avgEl.textContent = reviews.length ? `${avg.toFixed(1)}/5` : 'No ratings yet';
            }

            if (!reviews.length) {
                body.innerHTML = '<div class="text-muted">No reviews yet.</div>';
                return;
            }

            body.innerHTML = reviews.map((review) => {
                const stars = Array.from({ length: review.rating || 0 })
                    .map(() => '<i class="fas fa-star"></i>')
                    .join('');
                const status = (review.status || '').toLowerCase();
                const statusLabel = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
                const badgeClass = status === 'approved'
                    ? 'success'
                    : (status === 'pending' ? 'warning' : 'danger');
                return `
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>${escapeHtml(review.user_name || 'Anonymous')}</strong>
                                <br>
                                <small class="text-warning">
                                    ${stars}
                                    (${review.rating || 0}/5)
                                </small>
                            </div>
                            <small class="text-muted">${formatDate(review.created_at)}</small>
                        </div>
                        <p class="mb-0">${escapeHtml(review.comment || '')}</p>
                        <small class="text-muted">
                            Status: <span class="badge bg-${badgeClass}">
                                ${escapeHtml(statusLabel)}
                            </span>
                        </small>
                    </div>
                `;
            }).join('');
        };

        const fetchReviews = async () => {
            if (isFetching) return;
            isFetching = true;
            try {
                const response = await fetch(endpoint, { cache: 'no-store' });
                if (!response.ok) throw new Error('Failed to fetch reviews');
                const payload = await response.json();
                const reviews = payload.data || [];
                renderReviews(reviews);
            } catch (_) {
                // Keep existing UI if polling fails.
            } finally {
                isFetching = false;
            }
        };

        fetchReviews();
        setInterval(fetchReviews, 15000);
    })();
</script>
@endsection
