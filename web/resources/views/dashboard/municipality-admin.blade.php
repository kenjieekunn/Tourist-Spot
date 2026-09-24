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
        margin-bottom: 1.5rem;
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
    .stat-card.verified-card::before { background: #3f7d42; }
    .stat-card.pending-card-stat::before { background: #d69e2e; }
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
    .municipality-banner-eyebrow { font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; opacity: .82; }
    .municipality-banner-subtitle { margin: 0; opacity: .9; }
    .municipality-banner-logo { width: 92px; height: 92px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,.75); box-shadow: 0 8px 20px rgba(0,0,0,.18); }
    .spot-row-thumbnail { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; flex: 0 0 auto; background: #eef5f2; }
    .spot-row-placeholder { display: inline-flex; align-items: center; justify-content: center; color: #0f766e; border: 1px solid #c8e5da; }
    .verification-status-badge { background: #fff3cd; color: #856404; border: 1px solid #f1d487; }
    .operational-status-badge { background: transparent; color: #6b7280; border: 1px solid #9ca3af; }
    .approval-caption { color: #55736e; font-size: .85rem; }
    .pending-card.is-overdue { border-left-color: #c53030; background-color: #fff5f5; }
    .pending-card {
        border-left: 4px solid #ff6b35;
        background-color: #fff9f5;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .spot-status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .status-open {
        background-color: #d4edda;
        color: #155724;
    }
    .status-closed {
        background-color: #fff3cd;
        color: #856404;
    }
    .verification-pill {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
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
    .spot-panels > [class*="col-"] {
        display: flex;
    }
    .spot-panels .card {
        width: 100%;
    }
    .spot-row {
        min-width: 0;
    }
    .spot-row-details {
        min-width: 0;
    }
    .spot-row-details h6,
    .spot-row-details small {
        overflow-wrap: anywhere;
    }
    .spot-row-status {
        flex: 0 0 auto;
        min-width: 92px;
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
        .spot-panel-header {
            align-items: flex-start !important;
            flex-direction: column;
            gap: .75rem;
        }
        .spot-panel-actions {
            width: 100%;
        }
        .spot-panel-actions .btn {
            flex: 1 1 auto;
        }
        .spot-row {
            align-items: flex-start;
            flex-wrap: wrap;
            row-gap: .75rem;
        }
        .spot-row-status {
            flex: 1 1 100%;
            min-width: 0;
            padding-left: 76px;
            text-align: left !important;
        }
        .spot-row-status .mt-2 {
            margin-top: .35rem !important;
        }
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
            <div class="municipality-banner-eyebrow">
                @if(auth()->user()->isMunicipalityStaff())
                    Welcome Back, {{ auth()->user()->name }}
                @else
                    Welcome back, {{ $municipality->name }} Admin
                @endif
            </div>
            <h2 class="mb-1">{{ $municipality->name }}</h2>
            <p class="municipality-banner-subtitle">Municipality in Pangasinan · 2nd District of Pangasinan</p>
        </div>
        @if($municipality->image_url)
            <img class="municipality-banner-logo" src="{{ preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url) }}" alt="{{ $municipality->name }} municipality seal">
        @endif
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

@if(auth()->user()->hasPermission('manage_spots'))
<!-- Statistics Row -->
<div class="row mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <a href="{{ route('municipality-admin.tourist-spots') }}" class="stat-card h-100 w-100" aria-label="View all tourist spots">
            <div class="stat-value">{{ $totalSpots }}</div>
            <div class="stat-label">Total Tourist Spots</div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <a href="{{ route('municipality-admin.tourist-spots', ['verification_status' => 'approved']) }}" class="stat-card verified-card h-100 w-100" aria-label="View verified tourist spots">
            <div class="stat-value">{{ $verifiedSpots }}</div>
            <div class="stat-label">Verified Spots</div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <button type="button" class="stat-card pending-card-stat h-100 w-100 border-0" data-bs-toggle="modal" data-bs-target="#pendingVerificationModal" aria-label="View tourist spots pending verification">
            <div class="stat-value">{{ $pendingVerificationSpots }}</div>
            <div class="stat-label">Pending Verification</div>
        </button>
    </div>
</div>

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
                            <a href="{{ route('tourist_spots.show', $pendingSpot->id) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
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

<div class="row g-4 spot-panels">
    <!-- Recent Tourist Spots -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center spot-panel-header">
                    <h5 class="mb-0">Recent Tourist Spots</h5>
                    <div class="d-flex gap-2 spot-panel-actions">
                        <a href="{{ route('municipality-admin.tourist-spots') }}" class="btn btn-sm btn-tourism">View All</a>
                        @if(auth()->user()->hasPermission('manage_spots'))
                            <a href="{{ route('tourist_spots.create') }}" class="btn btn-sm btn-outline-success"><i class="fas fa-plus me-1"></i> Add New Spot</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                @forelse($recentSpots as $spot)
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3 pb-3 border-bottom spot-row">
                        <div class="d-flex align-items-start gap-3">
                            @if($spot->image_url)
                                <img class="spot-row-thumbnail" src="{{ preg_match('#^https?://#i', $spot->image_url) ? $spot->image_url : url($spot->image_url) }}" alt="{{ $spot->name }} thumbnail">
                            @else
                                <span class="spot-row-thumbnail spot-row-placeholder"><i class="fas fa-image"></i></span>
                            @endif
                            <div class="spot-row-details">
                            <h6 class="mb-1">{{ $spot->name }}</h6>
                            <small class="text-muted">{{ $spot->address ?? 'No address provided' }}</small>
                            </div>
                        </div>
                        <div class="text-end spot-row-status">
                            <span class="verification-pill verification-status-badge">
                                {{ ucfirst($spot->verification_status ?? 'Pending') }}
                            </span>
                            <div class="mt-2">
                                <span class="spot-status-badge operational-status-badge">
                                    {{ in_array($spot->status, ['open', 'active']) ? 'Open' : 'Closed' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No tourist spots added yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Pending Spots Waiting for Super Admin Approval -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Waiting for Approval</h5>
                <div class="approval-caption mt-1">Submitted spots will be reviewed by the Provincial Tourism Office.</div>
            </div>
            <div class="card-body">
                @forelse($pendingSpots as $spot)
                    <div class="pending-card {{ $spot->created_at && $spot->created_at->diffInDays(now()) >= 3 ? 'is-overdue' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-2 pending-card-header">
                            <div>
                                <h6 class="mb-1">{{ $spot->name }}</h6>
                                <small class="text-muted">Added {{ $spot->created_at->diffForHumans() }}</small>
                            </div>
                            <span class="badge bg-warning text-dark">Pending for {{ $spot->created_at?->diffForHumans() ?? 'some time' }}</span>
                        </div>
                        <p class="text-muted small mb-0">{{ Str::limit($spot->description, 100) }}</p>
                    </div>
                @empty
                    <p class="text-muted">All your spots have been approved or are currently closed.</p>
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
@endif

@endsection
