@extends('layouts.app')

@section('title', $municipality->name . ' Tourist Spots')
@section('header', '')

@section('content')
<style>
    .municipality-details-page {
        --tourism-teal: #0f766e;
        --tourism-green: #3f7d42;
        --tourism-ink: #173f43;
        --tourism-mint: #e5f5f1;
        --tourism-border: #dce9e5;
    }
    .municipality-detail-header {
        border: 1px solid var(--tourism-border);
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
    }
    .municipality-detail-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        background: #eef5f2;
    }
    .municipality-detail-placeholder {
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #effaf7 0%, #f7fbf3 100%);
        color: #8a94a6;
    }
    .spot-category-section {
        border: 1px solid var(--tourism-border);
        border-radius: 14px;
        background: #ffffff;
        overflow: hidden;
    }
    .spot-category-header {
        background: linear-gradient(135deg, #effaf7 0%, #f7fbf3 100%);
        border-bottom: 1px solid var(--tourism-border);
        color: var(--tourism-ink);
    }
    .spot-card {
        border: 1px solid var(--tourism-border);
        border-radius: 12px;
        background: #ffffff;
        overflow: hidden;
    }
    .spot-card-trigger { width: 100%; padding: 0; color: inherit; font: inherit; text-align: left; cursor: pointer; }
    .spot-card-trigger:hover { box-shadow: 0 8px 20px rgba(23, 63, 67, .12); }
    .spot-card-trigger:focus-visible { outline: 3px solid rgba(15, 118, 110, .35); outline-offset: 3px; }
    .spot-image,
    .spot-image-placeholder {
        width: 100%;
        height: 160px;
        display: block;
    }
    .spot-image {
        object-fit: cover;
        background: #eef5f2;
    }
    .spot-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #effaf7 0%, #f7fbf3 100%);
        color: #8a94a6;
    }
    .spot-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .municipality-details-page h3,
    .municipality-details-page h5,
    .municipality-details-page h6 {
        color: var(--tourism-ink);
    }
    .municipality-spots-badge,
    .municipality-details-page .btn-tourism {
        background: var(--tourism-teal);
        border-color: var(--tourism-teal);
        color: #fff;
    }
    .municipality-details-page .btn-tourism:hover {
        background: #115e59;
        border-color: #115e59;
        color: #fff;
    }
</style>

<div class="municipality-details-page">
<nav aria-label="Breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ auth()->user()?->isSuperAdmin() ? route('super-admin.dashboard') . '#dashboard-municipalities' : route('municipality-admin.dashboard') }}">Municipalities</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $municipality->name }}</li>
    </ol>
</nav>
<div class="municipality-detail-header mb-4">
    @if($municipality->image_url)
        <img
            src="{{ preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url) }}"
            alt="{{ $municipality->name }} photo"
            class="municipality-detail-image"
        >
    @else
        <div class="municipality-detail-placeholder">No image available</div>
    @endif
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="mb-1">{{ $municipality->name }}</h3>
                <p class="text-muted mb-0">{{ $municipality->description ?: 'Tourist spots in ' . $municipality->name }}</p>
            </div>
            <span class="badge municipality-spots-badge">{{ $groupedSpots->sum(fn ($spots) => $spots->count()) }} spots</span>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-4" role="tablist" aria-label="Municipality details">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="municipality-overview-tab" data-bs-toggle="tab" data-bs-target="#municipality-overview" type="button" role="tab" aria-controls="municipality-overview" aria-selected="true">Overview</button>
    </li>
    @if($canViewSubmissionSource)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="municipality-history-tab" data-bs-toggle="tab" data-bs-target="#municipality-history" type="button" role="tab" aria-controls="municipality-history" aria-selected="false">History</button>
        </li>
    @endif
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="municipality-overview" role="tabpanel" aria-labelledby="municipality-overview-tab" tabindex="0">
@php
    $hasSpots = $groupedSpots->contains(fn ($spots) => $spots->isNotEmpty());
@endphp

@if($hasSpots)
    @foreach($spotCategories as $categoryKey => $categoryLabel)
        @php($categorySpots = $groupedSpots[$categoryKey] ?? collect())
        @if($categorySpots->isNotEmpty())
            <section class="spot-category-section mb-4">
                <div class="spot-category-header d-flex justify-content-between align-items-center py-3 px-4">
                    <h5 class="mb-0">{{ $categoryLabel }}</h5>
                    <span class="text-muted small">{{ $categorySpots->count() }} spot(s)</span>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        @foreach($categorySpots as $spot)
                            <div class="col-12 col-md-6 col-xl-4">
                                @php($verificationStatus = $spot->verification_status === 'approved' ? 'Verified' : ($spot->verification_status ? ucfirst($spot->verification_status) : 'Recorded'))
                                <button type="button" class="spot-card spot-card-trigger h-100 d-flex flex-column" data-spot-preview data-spot-id="{{ $spot->id }}"
                                    data-spot-name="{{ $spot->name }}"
                                    data-spot-municipality="{{ $municipality->name }}"
                                    data-spot-barangay="{{ $spot->barangay }}"
                                    data-spot-category="{{ ucfirst($spot->category ?? 'nature') }}"
                                    data-spot-status="{{ $verificationStatus }}"
                                    data-spot-description="{{ $spot->description }}"
                                    data-spot-submitter="{{ $canViewSubmissionSource ? $spot->creator?->name : '' }}"
                                    data-spot-submitter-contact="{{ $canViewSubmissionSource ? $spot->creator?->email : '' }}"
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
                                    data-spot-image="{{ $spot->primary_image_url }}"
                                    aria-label="View details for {{ $spot->name }}">
                                    @if($spot->image_url)
                                        <img
                                            src="{{ preg_match('#^https?://#i', $spot->image_url) ? $spot->image_url : url($spot->image_url) }}"
                                            alt="{{ $spot->name }} image"
                                            class="spot-image"
                                        >
                                    @else
                                        <div class="spot-image-placeholder">No image available</div>
                                    @endif
                                    <div class="p-3 d-flex flex-column h-100">
                                        <h6 class="mb-1">{{ $spot->name }}</h6>
                                        <div class="spot-meta">{{ Str::limit($spot->address ?: 'No address available', 70) }}</div>
                                        <div class="mt-2">
                                            <span class="badge bg-{{ in_array($spot->status, ['open', 'active']) ? 'success' : 'secondary' }}">
                                                {{ in_array($spot->status, ['open', 'active']) ? 'Open' : 'Closed' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach
@else
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            No tourist spots found in {{ $municipality->name }}.
        </div>
    </div>
@endif
</div>
@if($canViewSubmissionSource)
    <div class="tab-pane fade" id="municipality-history" role="tabpanel" aria-labelledby="municipality-history-tab" tabindex="0">
        <div class="card">
            <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Added Tourist Spots</h5>
                <span class="text-muted small">{{ $spots->count() }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr><th>Tourist Spot</th><th>Added By</th><th>Date and Time Added</th></tr>
                    </thead>
                    <tbody>
                        @forelse($spots->sortByDesc('created_at') as $spot)
                            <tr>
                                <td><strong>{{ $spot->name }}</strong><div class="small text-muted">{{ ucfirst($spot->category ?? 'nature') }}</div></td>
                                <td>{{ $spot->creator?->name ?? 'Unknown' }}</td>
                                <td class="text-nowrap">{{ $spot->created_at?->format('M d, Y h:i A') ?? 'Not available' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No tourist spots have been added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
</div>

@include('dashboard.partials.tourist-spot-preview-modal')
@endsection
