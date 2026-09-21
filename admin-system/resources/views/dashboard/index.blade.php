@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<style>
    .municipality-card {
        border: 1px solid #e6e9ef;
        border-radius: 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        background: #ffffff;
        overflow: hidden;
    }
    .municipality-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
    }
    .municipality-count {
        font-weight: 600;
    }
    .municipality-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        background: #f2f4f8;
        display: block;
    }
    .municipality-image-placeholder {
        height: 180px;
        background: linear-gradient(135deg, #e9edf5 0%, #f7f9fc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8a94a6;
        font-size: 0.95rem;
        letter-spacing: 0.2px;
    }
</style>

<div class="row">
    <div class="col-md-6 col-lg-4">
        <div class="card stat-card">
            <div class="stat-value">{{ $totalMunicipalities }}</div>
            <div class="stat-label">Municipalities</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card stat-card">
            <div class="stat-value">{{ $totalSpots }}</div>
            <div class="stat-label">Tourist Spots</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card stat-card">
            <div class="stat-value">{{ $totalReviews }}</div>
            <div class="stat-label">Total Reviews</div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h5 class="m-0">Municipalities</h5>
</div>

<div class="row g-3">
    @forelse($municipalities as $municipality)
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card municipality-card h-100 position-relative">
                <a href="{{ route('municipalities.show', $municipality->id) }}" class="stretched-link" aria-label="View spots in {{ $municipality->name }}"></a>
                @if($municipality->image_url)
                    <img class="municipality-image" src="{{ preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url) }}" alt="{{ $municipality->name }} photo">
                @else
                    <div class="municipality-image-placeholder">
                        No photo available
                    </div>
                @endif
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <h5 class="card-title mb-1">{{ $municipality->name }}</h5>
                            @if($municipality->description)
                                <p class="card-text text-muted mb-0">{{ Str::limit($municipality->description, 90) }}</p>
                            @endif
                        </div>
                        <span class="badge bg-info municipality-count">
                            {{ $municipality->touristSpots->count() }} spots
                        </span>
                    </div>

                    <div class="mt-auto pt-3 d-flex gap-2">
                        <a href="{{ route('municipalities.edit', $municipality->id) }}" class="btn btn-sm btn-warning position-relative z-2" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">No municipalities found</div>
            </div>
        </div>
    @endforelse
</div>

@php
    // Recent Tourist Spots section removed from dashboard per request.
@endphp
@endsection
