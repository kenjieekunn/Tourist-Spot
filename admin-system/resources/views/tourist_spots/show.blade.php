@extends('layouts.app')

@section('title', $spot->name)
@section('header', $spot->name)

@section('header_actions')
@php
    $backUrl = url()->previous();
    if (!$backUrl || $backUrl === url()->current()) {
        $backUrl = route('tourist_spots.index');
    }
    $currentUser = auth()->user();
    $isSuperAdminPendingSpot = $currentUser && $currentUser->isSuperAdmin() && $spot->isPendingVerification();
    $canEditSpot = $currentUser && $currentUser->isAdmin() && ! $currentUser->isSuperAdmin();
@endphp
<a href="{{ $backUrl }}" class="btn btn-sm btn-secondary">
    Back
</a>
@if($isSuperAdminPendingSpot)
    <form action="{{ route('super-admin.spots.approve', $spot) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fas fa-check"></i> Approve
        </button>
    </form>
@elseif($canEditSpot)
    <a href="{{ route('tourist_spots.edit', $spot->id) }}" class="btn btn-sm btn-warning">
        <i class="fas fa-edit"></i> Edit
    </a>
@endif
@endsection

@section('content')
@php
    $facilityList = collect($spot->nearby_facilities ?? []);
    $derivedDining = $facilityList->where('type', 'dining')->pluck('name')->unique()->implode(', ');
    $derivedGas = $facilityList->where('type', 'gas_station')->pluck('name')->unique()->implode(', ');
    $derivedRestrooms = $facilityList->where('type', 'restroom')->pluck('name')->unique()->implode(', ');
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

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">{{ $spot->name }}</h5>
                @php
                    $isParkSpot = ($spot->category ?? 'nature') === 'parks';
                @endphp

                @if($spotImages->isNotEmpty())
                    <div class="mb-4">
                        <h6 class="mb-3">Submitted Images</h6>
                        <div class="row g-2">
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
                                        <img src="{{ $resolvedSpotImage }}" alt="{{ $spot->name }} image" class="img-fluid rounded" style="height: 140px; width: 100%; object-fit: cover;">
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($spot->image_url)
                    @php $resolvedPrimaryImage = $resolveImageUrl($spot->image_url); @endphp
                    <div class="mb-3">
                        <img src="{{ $resolvedPrimaryImage }}" alt="{{ $spot->name }}" class="img-fluid rounded" style="max-height: 400px; object-fit: cover;">
                    </div>
                @endif

                <table class="table table-borderless">
                    <tr>
                        <td><strong>Municipality:</strong></td>
                        <td>{{ $spot->municipality->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td><span class="badge bg-info text-dark">{{ ucfirst($spot->category ?? 'nature') }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Address:</strong></td>
                        <td>{{ $spot->address }}</td>
                    </tr>
                    @if($isParkSpot)
                        <tr>
                            <td><strong>Opening Days:</strong></td>
                            <td>
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
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Opening Time:</strong></td>
                            <td>{{ $spot->opening_time ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Closing Time:</strong></td>
                            <td>{{ $spot->closing_time ?: 'N/A' }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Nearby Dining:</strong></td>
                        <td>{{ $spot->nearby_dining ?: ($derivedDining ?: 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Nearby Gas Stations:</strong></td>
                        <td>{{ $spot->nearby_gas_stations ?: ($derivedGas ?: 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Nearby Restrooms:</strong></td>
                        <td>{{ $derivedRestrooms ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            @php
                                $isOpen = in_array($spot->status, ['open', 'active']);
                            @endphp
                            <span class="badge bg-{{ $isOpen ? 'success' : 'danger' }}">
                                {{ $isOpen ? 'Open' : 'Closed' }}
                            </span>
                        </td>
                    </tr>
                </table>

                <hr>

                <h6>Description</h6>
                <p>{{ $spot->description }}</p>
            </div>
        </div>

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
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="m-0"><i class="fas fa-map-marked-alt"></i> Map</h5>
            </div>
            <div class="card-body p-3">
                @if(!empty($spot->latitude) && !empty($spot->longitude))
                    <div class="ratio ratio-4x3 rounded overflow-hidden border">
                        <iframe
                            src="{{ $googleMapEmbedUrl }}"
                            style="border:0;"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="{{ $spot->name }} location on Google Maps"
                        ></iframe>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <div class="text-muted small">Exact Google Maps view of this tourist spot.</div>
                        @if($googleMapViewUrl)
                            <a href="{{ $googleMapViewUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                Open in Google Maps
                            </a>
                        @endif
                    </div>
                @else
                    <div class="p-4 text-muted small">Location coordinates are not available for this spot.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="m-0"><i class="fas fa-stats"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    <strong>Average Rating:</strong><br>
                    <span style="font-size: 1.8rem; color: #ff6b35;">
                        <span id="spot-avg-rating">{{ number_format($spot->getAverageRating(), 1) }}</span>/5
                    </span>
                    <i class="fas fa-star" style="color: #ffc107;"></i>
                </p>
                <p>
                    <strong>Total Reviews:</strong> <span id="spot-reviews-total">{{ $spot->reviews->count() }}</span>
                </p>
            </div>
        </div>

    </div>
</div>

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
            <div class="modal-body text-center">
                <img id="spotModalImage" src="" alt="Tourist spot image" style="max-width: 100%; height: auto; border-radius: 4px;">
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.setSpotImage = function (trigger) {
        const image = trigger?.getAttribute('data-spot-image') || '';
        const name = trigger?.getAttribute('data-spot-name') || 'Tourist Spot Image';

        const imageEl = document.getElementById('spotModalImage');
        const titleEl = document.getElementById('spotModalImageTitle');

        if (imageEl) {
            imageEl.src = image;
        }

        if (titleEl) {
            titleEl.textContent = name;
        }
    };
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
                avgEl.textContent = avg.toFixed(1);
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
