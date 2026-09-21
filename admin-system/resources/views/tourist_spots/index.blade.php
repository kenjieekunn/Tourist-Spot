@extends('layouts.app')

@section('title', 'Tourist Spots')
@section('header', auth()->user()->isAdmin() ? 'Manage Tourist Spots' : 'Tourist Spots')

@section('styles')
<style>
    /* Sticky top header only on this page */
    .navbar-custom {
        position: sticky;
        top: 0;
        z-index: 1020;
        border-bottom: 1px solid #eef1f6;
    }
    .spot-header-bottom {
        padding: 0 2rem 1rem 0;
    }
</style>
@endsection

@section('header_bottom')
    <div class="spot-header-bottom">
        <form method="GET" action="{{ route('tourist_spots.index') }}" class="row g-2 align-items-end mx-0">
            <div class="col-12 col-lg-6">
                <div class="search-input-group">
                    <input
                        type="text"
                        class="form-control"
                        id="spot-search"
                        name="q"
                        value="{{ $searchTerm }}"
                        placeholder="Search"
                        autocomplete="off"
                        aria-label="Search"
                    >
                    <button type="button" class="search-clear-btn" id="spot-search-clear" aria-label="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 col-lg-2 ms-lg-auto text-lg-end">
                <a href="{{ route('tourist_spots.index') }}" class="btn btn-outline-secondary btn-sm">
                    Show All
                </a>
            </div>
        </form>
    </div>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Tourist Spots</h3>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('tourist_spots.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New Spot
        </a>
    @endif
</div>

<style>
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
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        background: #ffffff;
        overflow: hidden;
    }
    .spot-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.06);
    }
    .spot-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .spot-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
        background: #f2f4f8;
    }
    .spot-image-placeholder {
        height: 160px;
        background: linear-gradient(135deg, #eef1f6 0%, #f9fafc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8a94a6;
        font-size: 0.9rem;
        letter-spacing: 0.2px;
    }
    .spot-category-badge {
        background: #1e88e5;
        color: #ffffff;
        border: 1px solid #1e88e5;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .municipality-badge {
        background: #eef4ff;
        color: #3b5bdb;
        border: 1px solid #dbe4ff;
        font-weight: 600;
    }
    .search-input-group {
        position: relative;
    }
    .search-clear-btn {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #6c757d;
        font-size: 1rem;
        padding: 0;
        cursor: pointer;
        display: none;
    }
    .search-clear-btn.show {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>

<script>
    (function () {
        const input = document.getElementById('spot-search');
        const clearBtn = document.getElementById('spot-search-clear');

        if (!input || !clearBtn) {
            return;
        }

        const toggleClear = () => {
            clearBtn.classList.toggle('show', input.value.trim().length > 0);
        };

        clearBtn.addEventListener('click', () => {
            input.value = '';
            toggleClear();
            input.focus();
        });

        input.addEventListener('input', toggleClear);
        toggleClear();
    })();
</script>

@php
    $totalVisibleSpots = collect($groupedSpots)->sum(fn ($items) => $items->count());
@endphp

@if($totalVisibleSpots > 0)
    @foreach($spotCategories as $categoryKey => $categoryLabel)
        @php
            $categorySpots = $groupedSpots[$categoryKey] ?? collect();
        @endphp

        @if($categorySpots->isNotEmpty())
            <div class="card spot-category-section mb-4">
                <div class="card-header spot-category-header d-flex justify-content-between align-items-center py-3 px-4">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge spot-category-badge">{{ $categoryLabel }}</span>
                            <small class="text-muted">{{ $categorySpots->count() }} spot(s)</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($categorySpots as $spot)
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="spot-card h-100 d-flex flex-column">
                                    @if($spot->image_url)
                                        <img src="{{ preg_match('#^https?://#i', $spot->image_url) ? $spot->image_url : url($spot->image_url) }}" alt="{{ $spot->name }} image" class="spot-image">
                                    @else
                                        <div class="spot-image-placeholder">No image available</div>
                                    @endif

                                    <div class="p-3 d-flex flex-column h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div class="me-2">
                                                <h6 class="mb-1">{{ $spot->name }}</h6>
                                                <div class="spot-meta">{{ Str::limit($spot->address, 60) }}</div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-{{ $spot->verification_status === 'approved' ? 'success' : ($spot->verification_status === 'pending' ? 'warning text-dark' : 'danger') }}">
                                                    {{ ucfirst($spot->verification_status) }}
                                                </span>
                                                @php
                                                    $isOpen = in_array($spot->status, ['open', 'active']);
                                                @endphp
                                                <div class="mt-2">
                                                    <span class="badge bg-{{ $isOpen ? 'success' : 'secondary' }}">
                                                        {{ $isOpen ? 'Open' : 'Closed' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            <span class="badge municipality-badge">{{ $spot->municipality->name ?? 'Unknown Municipality' }}</span>
                                            <span class="badge bg-light text-dark border">{{ $categoryLabel }}</span>
                                        </div>

                                        @if($spot->getAverageRating() > 0)
                                            <div class="text-warning mt-2">
                                                <i class="fas fa-star"></i> {{ number_format($spot->getAverageRating(), 1) }}/5
                                            </div>
                                        @endif

                                        <div class="mt-auto pt-3 d-flex gap-2">
                                            <a href="{{ route('tourist_spots.show', $spot->id) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            @if(auth()->user()->isAdmin())
                                                <form action="{{ route('tourist_spots.destroy', $spot->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@else
    <div class="card">
        <div class="card-body text-center py-5">No tourist spots found</div>
    </div>
@endif

@endsection
