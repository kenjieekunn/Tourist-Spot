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
                                <div class="spot-card h-100 d-flex flex-column">
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
                                        <div class="mt-auto pt-3">
                                            <a href="{{ route('tourist_spots.show', $spot->id) }}" class="btn btn-sm btn-tourism">
                                                <i class="fas fa-eye"></i> View Spot
                                            </a>
                                        </div>
                                    </div>
                                </div>
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
@endsection
