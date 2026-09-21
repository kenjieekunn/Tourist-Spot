@extends('layouts.app')

@section('title', 'Municipality Admin Dashboard')
@section('header', $municipality->name . ' Admin Dashboard')
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
</style>

<!-- Municipality Banner -->
<div class="municipality-banner">
    <div class="municipality-info">
        <div>
            <h2 class="mb-1">{{ $municipality->name }}</h2>
        </div>
    </div>
</div>

<!-- Statistics Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card h-100 w-100">
            <div class="stat-value">{{ $totalSpots }}</div>
            <div class="stat-label">Total Tourist Spots</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card h-100 w-100">
            <div class="stat-value">{{ $verifiedSpots }}</div>
            <div class="stat-label">Verified Spots</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card h-100 w-100">
            <div class="stat-value">{{ $pendingVerificationSpots }}</div>
            <div class="stat-label">Pending Verification</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card h-100 w-100">
            <div class="stat-value">{{ $closedSpots }}</div>
            <div class="stat-label">Archived Spots</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Tourist Spots -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Tourist Spots</h5>
                    <a href="{{ route('municipality-admin.tourist-spots') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body">
                @forelse($recentSpots as $spot)
                    <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">{{ $spot->name }}</h6>
                            <small class="text-muted">{{ $spot->address ?? 'No address provided' }}</small>
                        </div>
                        <div class="text-end">
                            <span class="verification-pill verification-{{ $spot->verification_status }}">
                                {{ ucfirst($spot->verification_status) }}
                            </span>
                            <div class="mt-2">
                                <span class="spot-status-badge {{ in_array($spot->status, ['open', 'active']) ? 'status-open' : 'status-closed' }}">
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
                <h5 class="mb-0">Awaiting Super Admin Approval</h5>
            </div>
            <div class="card-body">
                @forelse($pendingSpots as $spot)
                    <div class="pending-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">{{ $spot->name }}</h6>
                                <small class="text-muted">Added {{ $spot->created_at->diffForHumans() }}</small>
                            </div>
                            <span class="badge bg-warning text-dark">Pending</span>
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

<!-- Pending Reviews -->
@if($pendingReviews > 0)
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
