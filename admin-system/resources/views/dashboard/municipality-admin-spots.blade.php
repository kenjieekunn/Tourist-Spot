@extends('layouts.app')

@section('title', 'Municipality Admin')
@section('header', '')

@section('content')
<style>
    .filters-card {
        border: 1px solid #e6e9ef;
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    }
    .search-input-group {
        position: relative;
    }
    .search-control-row {
        display: flex;
        gap: 0.5rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .search-control-row .search-input-group {
        flex: 1 1 320px;
    }
    .filters-row {
        row-gap: 0.75rem;
    }
    .filter-label {
        margin-bottom: 0.35rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #4b5563;
    }
    .search-clear-btn {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #6c757d;
        padding: 0;
        display: none;
    }
    .search-clear-btn.show {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .spot-card {
        border: 1px solid #eef1f6;
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .spot-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.06);
    }
    .spot-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
        background: #f2f4f8;
    }
    .spot-image-placeholder {
        height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #eef1f6 0%, #f9fafc 100%);
        color: #8a94a6;
        font-size: 0.9rem;
    }
    .spot-meta {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .spot-badge {
        font-size: 0.8rem;
        font-weight: 600;
    }
    .spot-municipality {
        background: #eef4ff;
        color: #3b5bdb;
        border: 1px solid #dbe4ff;
        font-weight: 600;
    }
    .filter-badge {
        font-size: 0.8rem;
        font-weight: 600;
    }
</style>

<div class="card filters-card mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('municipality-admin.tourist-spots') }}">
            <div class="row g-2 align-items-end filters-row">
                <div class="col-12 col-lg-8">
                    <label for="spot-search" class="filter-label">Search Spots</label>
                    <div class="search-control-row">
                        <div class="search-input-group">
                            <input
                                type="text"
                                class="form-control form-control-sm pe-5"
                                id="spot-search"
                                name="q"
                                value="{{ $searchTerm }}"
                                placeholder="Search by name, address, or description"
                                autocomplete="off"
                            >
                            <button type="button" class="search-clear-btn" id="spot-search-clear" aria-label="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('municipality-admin.tourist-spots') }}" class="btn btn-outline-secondary btn-sm">
                            Reset
                        </a>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <label for="category-filter" class="filter-label">Category Filter</label>
                    <select class="form-select form-select-sm" id="category-filter" name="category">
                        @foreach($spotCategories as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if($searchTerm !== '' || $selectedCategory !== 'all')
            <div class="mt-3 d-flex flex-wrap gap-2">
                @if($searchTerm !== '')
                    <span class="badge bg-light text-dark border filter-badge">
                        Search: {{ $searchTerm }}
                    </button>
                @endif
                @if($selectedCategory !== 'all')
                    <span class="badge bg-light text-dark border filter-badge">
                        Category: {{ $spotCategories[$selectedCategory] ?? ucfirst($selectedCategory) }}
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end mb-4">
    <a href="{{ route('tourist_spots.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Tourist Spot
    </a>
</div>

@if($spots->count() > 0)
    <div class="row g-3">
        @foreach($spots as $spot)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="spot-card h-100 d-flex flex-column">
                    @if($spot->image_url)
                        <img
                            src="{{ preg_match('#^https?://#i', $spot->image_url) ? $spot->image_url : url($spot->image_url) }}"
                            alt="{{ $spot->name }}"
                            class="spot-image"
                        >
                    @else
                        <div class="spot-image-placeholder">No image available</div>
                    @endif

                    <div class="p-3 d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="me-2">
                                <h6 class="mb-1">{{ $spot->name }}</h6>
                                <div class="spot-meta">{{ Str::limit($spot->address, 70) }}</div>
                            </div>
                            <span class="badge spot-badge bg-light text-dark border">
                                {{ ucfirst($spot->category ?? 'nature') }}
                            </span>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <span class="badge spot-badge spot-municipality">
                                {{ $spot->municipality->name ?? 'Unknown Municipality' }}
                            </span>
                            <span class="badge spot-badge {{ $spot->verification_status === 'approved' ? 'bg-success' : ($spot->verification_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ ucfirst($spot->verification_status) }}
                            </span>
                            <span class="badge spot-badge {{ in_array($spot->status, ['open', 'active']) ? 'bg-success' : 'bg-secondary' }}">
                                {{ in_array($spot->status, ['open', 'active']) ? 'Open' : 'Closed' }}
                            </span>
                        </div>

                        <div class="mt-2 text-muted small">
                            Reviews: {{ $spot->reviews_count }} | Created {{ $spot->created_at->format('M d, Y') }}
                        </div>

                        <div class="mt-auto pt-3 d-flex gap-2">
                            <a href="{{ route('tourist_spots.show', $spot->id) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('tourist_spots.edit', $spot->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('tourist_spots.destroy', $spot->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="{{ route('municipality-admin.tourist-spots') }}">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-5 text-muted">No tourist spots yet</div>
    </div>
@endif

@if($spots->hasPages())
    <nav class="mt-4">
        {{ $spots->links() }}
    </nav>
@endif

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

@endsection
