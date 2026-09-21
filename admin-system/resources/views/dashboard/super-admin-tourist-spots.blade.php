@extends('layouts.app')

@section('title', 'Tourist Spots - Super Admin')
@section('header', 'All Tourist Spots Verification')

@section('content')
@php
    $resolveImageUrl = function (?string $imagePath): ?string {
        if (!$imagePath) {
            return null;
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (str_starts_with($imagePath, '/storage/')) {
            return url($imagePath);
        }

        if (str_starts_with($imagePath, 'storage/')) {
            return url('/' . ltrim($imagePath, '/'));
        }

        return asset('storage/' . ltrim($imagePath, '/'));
    };
@endphp
<style>
    .spot-search-card {
        border-radius: 16px;
        overflow: hidden;
    }
    .spot-search-card .card-body {
        padding: 0.85rem 1rem;
    }
    .spot-search-card .form-label {
        margin-bottom: 0.25rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #4b5563;
    }
    .spot-search-card .form-control {
        min-height: 38px;
        border-radius: 10px;
    }
    .spot-search-card .btn {
        border-radius: 10px;
    }
    .spot-search-helper {
        font-size: 0.8rem;
        color: #6c757d;
    }
    .verification-table-card {
        border-radius: 16px;
        overflow: hidden;
    }
    .spot-category-section {
        border: 1px solid #e6e9ef;
        border-radius: 16px;
        background: #ffffff;
        overflow: hidden;
    }
    .spot-category-header {
        background: linear-gradient(135deg, #f7f9fc 0%, #eef4ff 100%);
        border-bottom: 1px solid #eef1f6;
    }
    .spot-card {
        border: 1px solid #eef1f6;
        border-radius: 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        background: #ffffff;
        overflow: hidden;
    }
    .spot-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.06);
    }
    .spot-image {
        width: 100%;
        height: 170px;
        object-fit: cover;
        display: block;
        background: #f2f4f8;
    }
    .spot-image-placeholder {
        height: 170px;
        background: linear-gradient(135deg, #eef1f6 0%, #f9fafc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8a94a6;
        font-size: 0.9rem;
        letter-spacing: 0.2px;
    }
    .spot-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .category-badge {
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
</style>

<div class="card mb-4 verification-table-card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pending Approval</h5>
        <span class="badge bg-warning text-dark">
            {{ $pendingSpots->count() }} pending
        </span>
    </div>

    <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Spot Name</th>
                        <th>Images</th>
                        <th>Municipality</th>
                        <th>Category</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingSpots as $spot)
                    <tr>
                        <td>
                            <strong>{{ $spot->name }}</strong>
                            <div class="text-muted small">{{ Str::limit($spot->address, 45) }}</div>
                        </td>
                        <td>
                            @php $spotImages = collect($spot->image_urls ?? []); @endphp
                            @if($spotImages->isNotEmpty())
                                @php $firstImage = $resolveImageUrl($spotImages->first()); @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <img
                                        src="{{ $firstImage }}"
                                        alt="{{ $spot->name }} image"
                                        style="width: 72px; height: 52px; object-fit: cover; border-radius: 8px; border: 1px solid #e6e9ef;"
                                    >
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-light text-dark border align-self-start">{{ $spotImages->count() }} image(s)</span>
                                        <small class="text-muted">Uploaded by admin</small>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">No images</span>
                            @endif
                        </td>
                        <td>{{ $spot->municipality->name ?? 'Unknown Municipality' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ ucfirst($spot->category ?? 'nature') }}
                            </span>
                        </td>
                        <td><small>{{ $spot->created_at->format('M d, Y') }}</small></td>
                        <td class="text-end">
                            <a href="{{ route('tourist_spots.show', $spot->id) }}" class="btn btn-info btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No tourist spots waiting for approval</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4 spot-search-card">
    <div class="card-body">
        <form method="GET" action="{{ route('super-admin.tourist-spots') }}" class="d-flex flex-column flex-md-row gap-2 align-items-md-end">
            <div class="flex-grow-1">
                <label for="spot-search" class="form-label">Search tourist spots</label>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    id="spot-search"
                    name="q"
                    value="{{ $searchTerm ?? '' }}"
                    placeholder="Type a spot name..."
                    autocomplete="off"
                >
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Search
                </button>
                @if(($searchTerm ?? '') !== '')
                    <a href="{{ route('super-admin.tourist-spots') }}" class="btn btn-outline-secondary btn-sm">
                        Clear
                    </a>
                @endif
            </div>
        </form>
        @if(($searchTerm ?? '') !== '')
            <div class="spot-search-helper mt-2">
                Showing results for <strong>{{ $searchTerm }}</strong>
            </div>
        @endif
    </div>
</div>

@php
    $approvedCount = collect($groupedApprovedSpots)->sum(fn ($items) => $items->count());
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Approved Tourist Spots</h5>
    <span class="text-muted small">{{ $approvedCount }} spot(s)</span>
</div>

@if($approvedCount > 0)
    @foreach($spotCategories as $categoryKey => $categoryLabel)
        @php
            $categorySpots = $groupedApprovedSpots[$categoryKey] ?? collect();
        @endphp

        @if($categorySpots->isNotEmpty())
            <div class="card spot-category-section mb-4">
                <div class="card-header spot-category-header d-flex justify-content-between align-items-center py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge category-badge">{{ $categoryLabel }}</span>
                        <small class="text-muted">{{ $categorySpots->count() }} approved spot(s)</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($categorySpots as $spot)
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="spot-card h-100 d-flex flex-column">
                                    @php $primaryImage = $resolveImageUrl($spot->primary_image_url ?? $spot->image_url); @endphp
                                    @if($primaryImage)
                                        <img src="{{ $primaryImage }}" alt="{{ $spot->name }} image" class="spot-image">
                                    @else
                                        <div class="spot-image-placeholder">No image available</div>
                                    @endif

                                    <div class="p-3 d-flex flex-column h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div class="me-2">
                                                <h6 class="mb-1">{{ $spot->name }}</h6>
                                                <div class="spot-meta">{{ Str::limit($spot->address, 60) }}</div>
                                            </div>
                                            <span class="badge bg-success">Approved</span>
                                        </div>

                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            <span class="badge municipality-badge">{{ $spot->municipality->name ?? 'Unknown Municipality' }}</span>
                                            <span class="badge bg-light text-dark border">{{ $categoryLabel }}</span>
                                        </div>

                                        <div class="mt-2">
                                            <span class="badge bg-{{ in_array($spot->status, ['open', 'active']) ? 'success' : 'secondary' }}">
                                                {{ in_array($spot->status, ['open', 'active']) ? 'Open' : 'Closed' }}
                                            </span>
                                        </div>

                                        @if($spot->getAverageRating() > 0)
                                            <div class="text-warning mt-2">
                                                <i class="fas fa-star"></i> {{ number_format($spot->getAverageRating(), 1) }}/5
                                            </div>
                                        @endif

                                        <div class="mt-auto pt-3 d-flex gap-2">
                                            <a href="{{ route('tourist_spots.show', $spot->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
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
        <div class="card-body text-center py-5">No approved tourist spots found{{ ($searchTerm ?? '') !== '' ? ' for this search.' : '' }}</div>
    </div>
@endif

@endsection
