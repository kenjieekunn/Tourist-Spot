@extends('layouts.app')

@section('title', 'Municipality Admin Dashboard')
@section('header', '')
@section('content')
<style>
    :root {
        --dash-bg: #f4f5f7;
        --dash-card: #ffffff;
        --dash-card-border: #d9dde3;
        --dash-text: #111827;
        --dash-muted: #6b7280;
        --dash-accent: #111111;
    }
    .stat-card {
        position: relative;
        display: flex;
        border: 1px solid var(--dash-card-border);
        border-radius: 18px;
        padding: 1.35rem;
        background: linear-gradient(180deg, #ffffff 0%, #fbfbfb 100%);
        color: var(--dash-text);
        text-align: left;
        box-shadow: 0 12px 24px rgba(17, 24, 39, 0.06);
        margin-bottom: 0;
        overflow: hidden;
        min-height: 168px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        text-decoration: none;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(17, 24, 39, 0.10);
        border-color: #bfc5cd;
    }
    .stat-card .stat-value { color: #0f766e; }
    .stat-card.verified-card .stat-value { color: #3f7d42; }
    .stat-card.pending-card-stat .stat-value { color: #b7791f; }
    .stat-card.staff-card .stat-value { color: #3976a8; }
    .stat-card.review-card .stat-value { color: #b7791f; }
    .stat-card.rating-card .stat-value { color: #3f7d42; }
    .stat-card.rejected-card .stat-value { color: #b42318; }
    .stat-card.closed-card .stat-value { color: #52616b; }
    .stat-card.verified-card::before { background: #3f7d42; }
    .stat-card.pending-card-stat::before { background: #d69e2e; }
    .stat-card.staff-card::before { background: #3976a8; }
    .stat-card.review-card::before { background: #d69e2e; }
    .stat-card.rating-card::before { background: #3f7d42; }
    .stat-card.rejected-card::before { background: #b42318; }
    .stat-card.closed-card::before { background: #52616b; }
    .btn-tourism { background: #0f766e; border-color: #0f766e; color: #fff; }
    .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #111111 0%, #4b5563 100%);
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
    .municipality-banner {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #0f766e 0%, #155e75 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    .municipality-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .municipality-banner-copy { position: relative; z-index: 1; }
    .municipality-banner-subtitle { margin: 0; opacity: .9; }
    .pending-card {
        border-left: 4px solid #ff6b35;
        background-color: #fff9f5;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .verification-pending {
        background: #fff3cd;
        color: #856404;
    }
    .verification-approved {
        background: #d1e7dd;
        color: #0f5132;
    }
    .verification-rejected {
        background: #f8d7da;
        color: #842029;
    }
    .pending-card-header {
        min-width: 0;
    }
    .pending-card-header > div {
        min-width: 0;
    }
    .pending-card-header h6,
    .pending-card-header small,
    .pending-card .badge {
        overflow-wrap: anywhere;
    }
    @media (max-width: 575.98px) {
        .pending-card-header {
            flex-direction: column;
        }
        .pending-card-header .badge {
            align-self: flex-start;
        }
        .pending-verification-modal .modal-body {
            background: #f8fafc;
        }
        .pending-verification-item {
            border: 1px solid #e5e7eb;
            border-left: 4px solid #d69e2e;
            border-radius: 8px;
            padding: .85rem 1rem;
            background: #fff;
        }
        .pending-verification-item h6 {
            overflow-wrap: anywhere;
        }
    }
</style>

<!-- Municipality Banner -->
<div class="municipality-banner">
    <div class="municipality-info">
        <div class="municipality-banner-copy">
            <h2 class="mb-1">{{ $municipality->name }}</h2>
            <p class="municipality-banner-subtitle">Municipality in Pangasinan · 2nd District of Pangasinan</p>
        </div>
    </div>
</div>

@if(auth()->user()->hasPermission('manage_spots') && $revisionNotifications->isNotEmpty())
    <div class="card border-warning mb-4">
        <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Revision Requests from Super Admin</h5>
            <form action="{{ route('municipality-admin.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
            </form>
        </div>
        <div class="card-body">
            @foreach($revisionNotifications as $notification)
                <div class="pending-card mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h6 class="mb-1">{{ $notification->data['tourist_spot_name'] ?? 'Tourist spot revision requested' }}</h6>
                            <p class="mb-1 text-muted small">{{ $notification->data['message'] ?? 'Please review the requested changes.' }}</p>
                            <p class="mb-0"><strong>Super Admin note:</strong> {{ $notification->data['reason'] ?? 'No additional note provided.' }}</p>
                        </div>
                        <a href="{{ route('municipality-admin.notifications.open', $notification->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-pen-to-square me-1"></i> Open and Review</a>
                    </div>
                    <small class="text-muted d-block mt-2">{{ optional($notification->created_at)->diffForHumans() }}</small>
                </div>
            @endforeach
        </div>
    </div>
@endif

@php
    $canManageSpots = auth()->user()->hasPermission('manage_spots');
    $canManageReviews = auth()->user()->hasPermission('manage_reviews');
    $canManageStaff = auth()->user()->isMunicipalityAdmin() && auth()->user()->hasPermission('manage_staff');
@endphp
<div class="row g-3 mb-4 dashboard-stat-grid">
    @if($canManageSpots)
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.tourist-spots') }}" class="stat-card h-100 w-100" aria-label="View all tourist spots">
                <div class="stat-value">{{ $totalSpots }}</div><div class="stat-label">Total Tourist Spots</div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.tourist-spots', ['verification_status' => 'approved']) }}" class="stat-card verified-card h-100 w-100" aria-label="View verified tourist spots">
                <div class="stat-value">{{ $verifiedSpots }}</div><div class="stat-label">Verified Spots</div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <button type="button" class="stat-card pending-card-stat h-100 w-100 border-0" data-bs-toggle="modal" data-bs-target="#pendingVerificationModal" aria-label="View tourist spots pending verification">
                <div class="stat-value">{{ $pendingVerificationSpots }}</div><div class="stat-label">Pending Verification</div>
            </button>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.tourist-spots') }}" class="stat-card rejected-card h-100 w-100" aria-label="View rejected tourist spots">
                <div class="stat-value">{{ $rejectedSpots }}</div><div class="stat-label">Rejected Spots</div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.tourist-spots') }}" class="stat-card closed-card h-100 w-100" aria-label="View closed tourist spots">
                <div class="stat-value">{{ $closedSpots }}</div><div class="stat-label">Closed Spots</div>
            </a>
        </div>
    @endif
    @if($canManageStaff)
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.staff') }}" class="stat-card staff-card h-100 w-100" aria-label="Open staff accounts">
                <div class="stat-value">{{ $totalStaff }}</div><div class="stat-label">Staff Accounts</div>
            </a>
        </div>
    @endif
    @if($canManageReviews)
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.reviews') }}" class="stat-card review-card h-100 w-100" aria-label="View {{ $totalReviews }} tourist reviews">
                <div class="stat-value">{{ $totalReviews }}</div><div class="stat-label">Total Reviews</div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.reviews') }}" class="stat-card pending-card-stat h-100 w-100" aria-label="View {{ $pendingReviews }} pending reviews">
                <div class="stat-value">{{ $pendingReviews }}</div><div class="stat-label">Pending Reviews</div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('municipality-admin.reviews') }}" class="stat-card rating-card h-100 w-100" aria-label="Average tourist rating {{ number_format($averageRating, 1) }} out of 5">
                <div class="stat-value">{{ number_format($averageRating, 1) }} / 5</div><div class="stat-label">Average Tourist Rating</div>
            </a>
        </div>
    @endif
</div>

@if($canManageSpots)
<div class="modal fade pending-verification-modal" id="pendingVerificationModal" tabindex="-1" aria-labelledby="pendingVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title" id="pendingVerificationModalLabel"><i class="fas fa-clock text-warning me-2"></i>Pending Tourist Spots</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                @forelse($pendingSpots as $pendingSpot)
                    <div class="pending-verification-item mb-2">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="min-w-0">
                                <h6 class="mb-1">{{ $pendingSpot->name }}</h6>
                                <small class="text-muted">{{ $pendingSpot->address ?? 'No address provided' }}</small>
                            </div>
                            <span class="badge bg-warning text-dark flex-shrink-0">Pending</span>
                        </div>
                        <div class="small text-muted mt-2">Added {{ optional($pendingSpot->created_at)->diffForHumans() }}</div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-info" data-spot-preview
                                data-spot-id="{{ $pendingSpot->id }}"
                                data-spot-revision-requested="{{ str_starts_with((string) ($pendingSpot->rejection_reason ?? ''), 'Revision requested:') ? 'true' : 'false' }}"
                                data-spot-rejection-reason="{{ $pendingSpot->rejection_reason }}"
                                data-spot-name="{{ $pendingSpot->name }}"
                                data-spot-municipality="{{ $municipality->name }}"
                                data-spot-barangay="{{ $pendingSpot->barangay }}"
                                data-spot-category="{{ ucfirst($pendingSpot->category ?? 'nature') }}"
                                data-spot-status="Pending"
                                data-spot-description="{{ $pendingSpot->description }}"
                                data-spot-address="{{ $pendingSpot->address }}"
                                data-spot-hours="{{ $pendingSpot->opening_hours }}"
                                data-spot-fee="{{ $pendingSpot->entrance_fee === null ? '' : ($pendingSpot->entrance_fee == 0 ? 'Free' : 'PHP ' . number_format($pendingSpot->entrance_fee, 2)) }}"
                                data-spot-phone="{{ $pendingSpot->phone }}"
                                data-spot-website="{{ $pendingSpot->website }}"
                                data-spot-latitude="{{ $pendingSpot->latitude }}"
                                data-spot-longitude="{{ $pendingSpot->longitude }}"
                                data-spot-images="{{ json_encode($pendingSpot->image_urls) }}"
                                data-spot-image="{{ $pendingSpot->primary_image_url }}"
                                aria-label="View {{ $pendingSpot->name }} details"><i class="fas fa-eye"></i> View</button>
                            <a href="{{ route('tourist_spots.edit', $pendingSpot->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No pending tourist spots.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endif

<!-- Pending Reviews -->
@if(auth()->user()->hasPermission('manage_reviews') && $pendingReviews > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Reviews to Approve</h5>
                    <a href="{{ route('municipality-admin.reviews') }}" class="btn btn-sm btn-primary">View All Reviews</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Tourist Spot</th>
                                <th>Reviewer</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentReviews as $review)
                                <tr>
                                    <td>{{ $review->touristSpot->name }}</td>
                                    <td>{{ $review->user_name }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $review->rating }} ⭐</span>
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($review->comment, 50) }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('municipality-admin.reviews') }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No pending reviews</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('dashboard.partials.tourist-spot-preview-modal')
@endif

@endsection
