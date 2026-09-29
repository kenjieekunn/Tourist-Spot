@extends('layouts.app')

@section('title', 'Super Admin Dashboard')
@section('header', '')

@section('header_actions')
<style>
    .dashboard-shell { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .notification-menu { position: relative; }
    .notification-menu summary { list-style: none; cursor: pointer; position: relative; }
    .notification-menu summary::-webkit-details-marker { display: none; }
    .notification-bell { width: 2.5rem; height: 2.5rem; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #d9dde3; border-radius: 50%; background: #fff; color: #164e63; }
    .notification-count { position: absolute; top: -0.25rem; right: -0.2rem; min-width: 1.15rem; height: 1.15rem; padding: 0 0.25rem; border-radius: 999px; background: #dc3545; color: #fff; font-size: 0.68rem; line-height: 1.15rem; text-align: center; }
    .notification-panel { position: absolute; z-index: 1050; top: 3rem; right: 0; width: min(22rem, calc(100vw - 2rem)); padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16); }
    .notification-item { display: block; padding: 0.7rem; border-radius: 8px; color: #1f2937; text-decoration: none; }
    .notification-item:hover { background: #f3f4f6; }
    .notification-item.is-disabled { display: none; }
    .notification-empty { color: #6b7280; font-size: 0.9rem; padding: 0.75rem; }
    .notification-item.priority { background: #fff8df; border: 1px solid #f1d487; color: #765b13; }
</style>
<details class="notification-menu">
    <summary class="notification-bell" aria-label="Open notifications" title="Notifications">
        <i class="fas fa-bell"></i><span class="notification-count" data-notification-count hidden></span>
    </summary>
    <div class="notification-panel">
        <div class="d-flex justify-content-between align-items-center mb-2"><strong>Notifications</strong></div>
        <a href="{{ route('super-admin.tourist-spots') }}" class="notification-item priority" data-notification="notifySpotSubmission" data-count="{{ $pendingVerificationSpots }}"><i class="fas fa-hourglass-half me-2"></i><strong>{{ $pendingVerificationSpots }}</strong> spot submission{{ $pendingVerificationSpots === 1 ? '' : 's' }} pending verification</a>
        <a href="{{ route('reviews.index') }}" class="notification-item" data-notification="notifyTouristReview" data-count="{{ $pendingReviews }}"><i class="fas fa-star text-info me-2"></i><strong>{{ $pendingReviews }}</strong> new tourist review{{ $pendingReviews === 1 ? '' : 's' }} to review</a>
        <a href="{{ route('super-admin.tourist-spots') }}" class="notification-item" data-notification="notifyReportedSpot" data-count="0"><i class="fas fa-flag text-danger me-2"></i>No reported tourist spots</a>
        <a href="{{ route('reviews.index') }}" class="notification-item" data-notification="notifyReportedReview" data-count="0"><i class="fas fa-comment-slash text-danger me-2"></i>No reported reviews</a>
        <div class="notification-empty" data-notification-empty>No new notifications</div>
    </div>
</details>
@endsection

@section('content')
<style>
    :root {
        --dash-bg: #f4f5f7;
        --dash-card: #ffffff;
        --dash-card-border: #d9dde3;
        --dash-text: #111827;
        --dash-muted: #6b7280;
        --dash-accent: #111111;
        --dash-accent-soft: #f3f4f6;
    }
    .dashboard-shell {
        background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%);
        padding: 1px 0 0;
    }
    .stat-card {
        display: block;
        position: relative;
        border: 1px solid var(--dash-card-border);
        border-radius: 18px;
        padding: 1.35rem;
        background: linear-gradient(180deg, #ffffff 0%, #fbfbfb 100%);
        color: var(--dash-text);
        box-shadow: 0 12px 24px rgba(17, 24, 39, 0.06);
        margin-bottom: 1.5rem;
        overflow: hidden;
        min-height: 158px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        color: var(--dash-text);
        text-decoration: none;
    }
    #dashboard-municipalities { scroll-margin-top: 1.5rem; }
    .pending-spots-trigger { cursor: pointer; font-family: inherit; text-align: left; }
    .stat-card:focus-visible {
        outline: 3px solid rgba(15, 118, 110, 0.35);
        outline-offset: 3px;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(17, 24, 39, 0.10);
        border-color: #bfc5cd;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #0f766e 0%, #3f7d42 100%);
    }
    .stat-value {
        font-size: 2.85rem;
        font-weight: 800;
        margin: 0;
        line-height: 1;
        letter-spacing: -0.04em;
    }
    .stat-label {
        font-size: 0.95rem;
        color: var(--dash-muted);
        margin-top: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
    }
    .stat-icon { width: 2.6rem; height: 2.6rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: #e5f5f1; color: #0f766e; font-size: 1.15rem; margin-bottom: 1rem; }
    .stat-card.priority-stat.has-pending { background: #fff5f3; border-color: #f2aaa3; }
    .stat-card.priority-stat.has-pending::before { background: linear-gradient(90deg, #c9342f, #ef6a5b); }
    .stat-card.priority-stat.has-pending .stat-icon { background: #ffe1dc; color: #b42318; }
    .stat-card.priority-stat.has-pending .stat-value { color: #b42318; }
    .pending-action-cue { position: absolute; top: .75rem; right: .75rem; display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .25rem .55rem; background: #b42318; color: #fff; font-size: .7rem; font-weight: 700; line-height: 1; }
    .stat-value { color: #0f766e; }
    .status-badge-active {
        background-color: #28a745;
    }
    .status-badge-inactive {
        background-color: #ffc107;
    }
    .admin-info {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }
    .municipality-card {
        position: relative;
        border: 1px solid #e6e9ef;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .municipality-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.06);
    }
    .municipality-image-edit { position: absolute; right: .65rem; top: .65rem; z-index: 2; }
    #municipalityImageModal [hidden] { display: none !important; }
    #municipalityImagePreview { object-fit: cover; }
    .municipality-image {
        width: 100%;
        aspect-ratio: 16 / 9;
        height: auto;
        object-fit: cover;
        background: #f2f4f8;
    }
    .municipality-image-placeholder {
        aspect-ratio: 16 / 9;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #eef1f6 0%, #f9fafc 100%);
        color: #8a94a6;
        font-size: 0.9rem;
    }
    .municipality-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .municipality-badge {
        font-size: 0.8rem;
        font-weight: 600;
    }
    .municipality-admin-chip {
        background: #eef4ff;
        color: #3b5bdb;
        border: 1px solid #dbe4ff;
        font-weight: 600;
    }
    .municipality-count-chip {
        background: #f8f9fa;
        color: #495057;
        border: 1px solid #e9ecef;
    }
    .municipality-attention { color: #946c10; border: 1px solid #e9c76a; background: #fff9e8; }
</style>

<!-- Statistics Row -->
<div class="dashboard-shell">
<div class="row g-3 mb-4">
    <div class="col">
        <a href="{{ route('super-admin.tourist-spots') }}" class="stat-card h-100 w-100" aria-label="View all tourist spots">
            <span class="stat-icon"><i class="fas fa-location-dot"></i></span>
            <div class="stat-value">{{ $totalSpots }}</div>
            <div class="stat-label">Total Tourist Spots</div>
        </a>
    </div>
    <div class="col">
        <a href="#dashboard-municipalities" class="stat-card h-100 w-100" aria-label="Scroll to dashboard municipalities">
            <span class="stat-icon"><i class="fas fa-map"></i></span>
            <div class="stat-value">{{ $totalMunicipalities }}</div>
            <div class="stat-label">Municipalities</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('super-admin.admins') }}" class="stat-card h-100 w-100" aria-label="Manage municipality admins">
            <span class="stat-icon"><i class="fas fa-users"></i></span>
            <div class="stat-value">{{ $totalAdmins }}</div>
            <div class="stat-label">Municipality Admins</div>
        </a>
    </div>
    <div class="col">
        <button type="button" class="stat-card priority-stat {{ $pendingVerificationSpots > 0 ? 'has-pending' : '' }} pending-spots-trigger h-100 w-100 border-0" data-bs-toggle="modal" data-bs-target="#dashboardPendingSpotsModal" aria-label="View tourist spots pending verification">
            @if($pendingVerificationSpots > 0)
                <span class="pending-action-cue"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> Needs review</span>
            @endif
            <span class="stat-icon"><i class="fas fa-hourglass-half"></i></span>
            <div class="stat-value">{{ $pendingVerificationSpots }}</div>
            <div class="stat-label">Pending Spot Verification</div>
        </button>
    </div>
</div>

<div class="row mt-4" id="dashboard-municipalities">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Municipalities</h5>
                <span class="text-muted small">{{ $municipalities->count() }} total</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($municipalities as $municipality)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="municipality-card h-100 d-flex flex-column position-relative">
                                <a href="{{ route('municipalities.show', $municipality->id) }}" class="stretched-link" aria-label="View {{ $municipality->name }} municipality"></a>
                                <button type="button" class="btn btn-light btn-sm municipality-image-edit shadow-sm" data-bs-toggle="modal" data-bs-target="#municipalityImageModal" data-update-url="{{ route('municipalities.update', $municipality) }}" data-name="{{ $municipality->name }}" data-description="{{ $municipality->description }}" data-active="{{ ($municipality->is_active ?? true) ? 1 : 0 }}" data-image-url="{{ $municipality->image_url ? (preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url)) : '' }}" aria-label="Edit {{ $municipality->name }} image" title="Edit municipality image"><i class="fas fa-pen-to-square" aria-hidden="true"></i></button>
                                @if($municipality->image_url)
                                    <img
                                        src="{{ preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url) }}"
                                        alt="{{ $municipality->name }}"
                                        class="municipality-image"
                                    >
                                @else
                                    <div class="municipality-image-placeholder">No image available</div>
                                @endif

                                <div class="p-3 d-flex flex-column h-100">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="me-2">
                                            <h6 class="mb-1">{{ $municipality->name }}</h6>
                                        </div>
                                        <span class="badge municipality-badge municipality-count-chip">
                                            {{ $municipality->tourist_spots_count }} spots
                                        </span>
                                    </div>

                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <span class="badge municipality-badge municipality-admin-chip">
                                            Admins: {{ $municipality->admins_count }}
                                        </span>
                                        @php
                                            $approvedSpots = $municipality->approved_spots_count ?? $municipality->touristSpots->where('verification_status', 'approved')->count();
                                            $pendingSpots = $municipality->pending_spots_count ?? $municipality->touristSpots->where('verification_status', 'pending')->count();
                                        @endphp
                                        <span class="badge municipality-badge bg-success">
                                            {{ $approvedSpots }}/{{ $municipality->tourist_spots_count }} Verified
                                        </span>
                                        @if($pendingSpots > 0)
                                            <span class="badge municipality-badge bg-warning text-dark">{{ $pendingSpots }} Pending</span>
                                        @endif
                                        @if($municipality->tourist_spots_count === 0)
                                            <span class="badge municipality-attention">No spots yet</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center text-muted py-4">No municipalities found</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<div class="modal fade" id="municipalityImageModal" tabindex="-1" aria-labelledby="municipalityImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="municipalityImageForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" id="municipalityImageName">
                <input type="hidden" name="description" id="municipalityImageDescription">
                <input type="hidden" name="is_active" id="municipalityImageActive">
                <input type="hidden" name="return_to_dashboard" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="municipalityImageModalLabel">Edit Municipality Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9 bg-light rounded overflow-hidden mb-3">
                        <img id="municipalityImagePreview" class="w-100 h-100 object-fit-cover" alt="Municipality image preview" hidden>
                        <div id="municipalityImagePlaceholder" class="d-flex align-items-center justify-content-center text-muted"><i class="fas fa-image me-2"></i>No image uploaded</div>
                    </div>
                    <label for="municipalityImageFile" class="form-label">Replace image</label>
                    <input type="file" class="form-control" id="municipalityImageFile" name="image" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG, PNG, or WebP, maximum 2MB.</div>
                    <div class="form-check mt-3" id="municipalityRemoveImageWrap" hidden>
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="municipalityRemoveImage">
                        <label class="form-check-label" for="municipalityRemoveImage">Remove current image</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="dashboardPendingSpotsModal" tabindex="-1" aria-labelledby="dashboardPendingSpotsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="dashboardPendingSpotsModalLabel">Pending Tourist Spots</h5>
                    <small class="text-muted">{{ $pendingTouristSpots->count() }} awaiting review</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                @forelse($pendingTouristSpots as $pendingSpot)
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 px-4 py-3 text-start w-100" data-spot-preview
                        data-spot-id="{{ $pendingSpot->id }}"
                        data-spot-return-to="dashboard"
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
                        <span class="min-w-0">
                            <strong class="d-block">{{ $pendingSpot->name }}</strong>
                            <small class="text-muted">{{ $pendingSpot->municipality->name ?? 'Unknown Municipality' }} · {{ optional($pendingSpot->created_at)->format('M d, Y') }}</small>
                        </span>
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
    document.addEventListener('DOMContentLoaded', function () {
        const imageModal = document.getElementById('municipalityImageModal');
        const imageForm = document.getElementById('municipalityImageForm');
        const imagePreview = document.getElementById('municipalityImagePreview');
        const imagePlaceholder = document.getElementById('municipalityImagePlaceholder');
        const imageFile = document.getElementById('municipalityImageFile');
        let previewObjectUrl = null;

        imageModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            imageForm.action = trigger.dataset.updateUrl;
            document.getElementById('municipalityImageName').value = trigger.dataset.name;
            document.getElementById('municipalityImageDescription').value = trigger.dataset.description;
            document.getElementById('municipalityImageActive').value = trigger.dataset.active;
            imageFile.value = '';
            document.getElementById('municipalityRemoveImage').checked = false;
            document.getElementById('municipalityRemoveImageWrap').hidden = !trigger.dataset.imageUrl;
            imagePreview.src = trigger.dataset.imageUrl;
            imagePreview.hidden = !trigger.dataset.imageUrl;
            imagePlaceholder.hidden = Boolean(trigger.dataset.imageUrl);
        });

        imageFile.addEventListener('change', function () {
            if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
            if (!imageFile.files.length) return;
            previewObjectUrl = URL.createObjectURL(imageFile.files[0]);
            imagePreview.src = previewObjectUrl;
            imagePreview.hidden = false;
            imagePlaceholder.hidden = true;
            document.getElementById('municipalityRemoveImage').checked = false;
        });

        const preferences = JSON.parse(localStorage.getItem('touristSpotAdminPreferences') || '{}');
        const items = document.querySelectorAll('[data-notification]');
        const spotSubmissionItem = document.querySelector('[data-notification="notifySpotSubmission"]');
        const badge = document.querySelector('[data-notification-count]');
        let visibleCount = 0;

        items.forEach(function (item) {
            const enabled = preferences[item.dataset.notification] !== false;
            const hasActivity = Number(item.dataset.count || 0) > 0;
            item.classList.toggle('is-disabled', !enabled || !hasActivity);
            if (enabled && hasActivity) visibleCount += 1;
        });

        const spotSubmissionCount = preferences.notifySpotSubmission === false ? 0 : Number(spotSubmissionItem.dataset.count || 0);
        badge.textContent = spotSubmissionCount;
        badge.hidden = spotSubmissionCount === 0;
        document.querySelector('[data-notification-empty]').style.display = visibleCount ? 'none' : 'block';
    });
</script>

@endsection
