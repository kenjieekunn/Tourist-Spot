@extends('layouts.app')

@section('title', $municipality->name . ' Tourist Spots')
@section('header', '')

@section('content')
<style>
    .municipality-detail-header {
        border: 1px solid #e6e9ef;
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
    }
    .municipality-detail-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        background: #f2f4f8;
    }
    .municipality-detail-placeholder {
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #eef1f6 0%, #f9fafc 100%);
        color: #8a94a6;
    }
    .spot-category-section {
        border: 1px solid #e6e9ef;
        border-radius: 14px;
        background: #ffffff;
        overflow: hidden;
    }
    .spot-category-header {
        background: linear-gradient(135deg, #f7f9fc 0%, #eef4ff 100%);
        border-bottom: 1px solid #eef1f6;
    }
    .spot-card {
        border: 1px solid #eef1f6;
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
        background: #f2f4f8;
    }
    .spot-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #eef1f6 0%, #f9fafc 100%);
        color: #8a94a6;
    }
    .spot-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
</style>

<div class="mb-3">
    <a href="{{ route('super-admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

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
            <span class="badge bg-info">{{ $groupedSpots->sum(fn ($spots) => $spots->count()) }} spots</span>
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
                                            <a href="{{ route('tourist_spots.show', $spot->id) }}" class="btn btn-sm btn-info">
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
@endsection
