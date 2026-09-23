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
        <i class="fas fa-bell"></i><span class="notification-count" data-notification-count>0</span>
    </summary>
    <div class="notification-panel">
        <div class="d-flex justify-content-between align-items-center mb-2"><strong>Notifications</strong></div>
        <a href="{{ route('super-admin.tourist-spots') }}" class="notification-item priority" data-notification="notifySpotSubmission" data-count="{{ $pendingVerificationSpots }}"><i class="fas fa-hourglass-half me-2"></i><strong>{{ $pendingVerificationSpots }}</strong> spot submission{{ $pendingVerificationSpots === 1 ? '' : 's' }} pending verification</a>
        <a href="{{ route('reviews.index') }}" class="notification-item" data-notification="notifyTouristReview" data-count="{{ $pendingReviews }}"><i class="fas fa-star text-info me-2"></i><strong>{{ $pendingReviews }}</strong> new tourist review{{ $pendingReviews === 1 ? '' : 's' }} to review</a>
        <a href="{{ route('super-admin.tourist-spots') }}" class="notification-item" data-notification="notifyReportedSpot" data-count="0"><i class="fas fa-flag text-danger me-2"></i>No reported tourist spots</a>
        <a href="{{ route('reviews.index') }}" class="notification-item" data-notification="notifyReportedReview" data-count="0"><i class="fas fa-comment-slash text-danger me-2"></i>No reported reviews</a>
        <a href="{{ route('super-admin.admins') }}" class="notification-item" data-notification="notifyAdminActivity" data-count="{{ $totalAdmins }}"><i class="fas fa-users text-success me-2"></i><strong>{{ $totalAdmins }}</strong> municipal admin account{{ $totalAdmins === 1 ? '' : 's' }} managed</a>
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
    .stat-card.priority-stat { background: #fff8df; border-color: #f1d487; }
    .stat-card.priority-stat::before { background: linear-gradient(90deg, #d99a17, #f0bd45); }
    .stat-card.priority-stat .stat-icon { background: #ffedb5; color: #9a6b05; }
    .stat-card.priority-stat .stat-value { color: #9a6b05; }
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
    .activity-item { border-bottom: 1px solid #edf1ef; padding: .85rem 0; }
    .activity-item:last-child { border-bottom: 0; }
    .activity-icon { width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #e5f5f1; color: #0f766e; flex: 0 0 auto; }
</style>

<!-- Statistics Row -->
<div class="dashboard-shell">
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('tourist_spots.index') }}" class="stat-card h-100 w-100" aria-label="View all tourist spots">
            <span class="stat-icon"><i class="fas fa-location-dot"></i></span>
            <div class="stat-value">{{ $totalSpots }}</div>
            <div class="stat-label">Total Tourist Spots</div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('municipalities.index') }}" class="stat-card h-100 w-100" aria-label="View all municipalities">
            <span class="stat-icon"><i class="fas fa-map"></i></span>
            <div class="stat-value">{{ $totalMunicipalities }}</div>
            <div class="stat-label">Municipalities</div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('super-admin.admins') }}" class="stat-card h-100 w-100" aria-label="Manage municipality admins">
            <span class="stat-icon"><i class="fas fa-users"></i></span>
            <div class="stat-value">{{ $totalAdmins }}</div>
            <div class="stat-label">Municipality Admins</div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('super-admin.tourist-spots') }}" class="stat-card priority-stat h-100 w-100" aria-label="View tourist spots pending verification">
            <span class="stat-icon"><i class="fas fa-hourglass-half"></i></span>
            <div class="stat-value">{{ $pendingVerificationSpots }}</div>
            <div class="stat-label">Pending Spot Verification</div>
        </a>
    </div>
</div>

<div class="row mt-4">
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
                                            <div class="municipality-meta">
                                                {{ $municipality->description ? \Illuminate\Support\Str::limit($municipality->description, 90) : 'No description available' }}
                                            </div>
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
                                            {{ $approvedSpots }}/{{ $municipality->tourist_spots_count }} Approved
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

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center"><h5 class="mb-0">Recent Activity</h5><span class="small text-muted">Latest system updates</span></div>
            <div class="card-body">
                @forelse($recentActivity as $activity)
                    <a href="{{ $activity['url'] }}" class="activity-item d-flex align-items-center gap-3 text-decoration-none text-reset">
                        <span class="activity-icon"><i class="fas {{ $activity['icon'] }}"></i></span>
                        <span class="flex-grow-1"><strong class="d-block">{{ $activity['title'] }}</strong><small class="text-muted">{{ $activity['description'] }}</small></span>
                        <small class="text-muted text-nowrap">{{ optional($activity['created_at'])->diffForHumans() }}</small>
                    </a>
                @empty
                    <div class="text-muted py-3">No recent activity yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-light"><h5 class="mb-0">Quick Actions</h5></div>
            <div class="card-body d-grid gap-2 align-content-start">
                <a href="{{ route('super-admin.admins') }}" class="btn btn-outline-secondary text-start"><i class="fas fa-users me-2"></i> 2nd District Municipalities</a>
                <a href="{{ route('super-admin.tourist-spots') }}" class="btn btn-outline-secondary text-start"><i class="fas fa-check-circle me-2"></i> Review Spot Submissions</a>
                <a href="{{ route('super-admin.reports') }}" class="btn btn-outline-secondary text-start"><i class="fas fa-chart-column me-2"></i> Open Tourism Reports</a>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const preferences = JSON.parse(localStorage.getItem('touristSpotAdminPreferences') || '{}');
        const items = document.querySelectorAll('[data-notification]');
        let visibleCount = 0;

        items.forEach(function (item) {
            const enabled = preferences[item.dataset.notification] !== false;
            const hasActivity = Number(item.dataset.count || 0) > 0;
            item.classList.toggle('is-disabled', !enabled || !hasActivity);
            if (enabled && hasActivity) visibleCount += 1;
        });

        document.querySelector('[data-notification-count]').textContent = visibleCount;
        document.querySelector('[data-notification-empty]').style.display = visibleCount ? 'none' : 'block';
    });
</script>

@endsection
