@extends('layouts.app')

@section('title', 'Spots Verification - Super Admin')
@section('header', '')

@section('content')
@php
    $resolveImageUrl = function (?string $imagePath): ?string {
        if (!$imagePath) return null;
        if (preg_match('#^https?://#i', $imagePath)) return $imagePath;
        return str_starts_with($imagePath, '/storage/') || str_starts_with($imagePath, 'storage/')
            ? url('/' . ltrim($imagePath, '/'))
            : asset('storage/' . ltrim($imagePath, '/'));
    };
@endphp
<style>
    .verification-summary { border: 1px solid #e6e9ef; border-radius: 16px; background: linear-gradient(135deg, #fffaf5, #fff); }
    .verification-card { display: flex; color: inherit; text-decoration: none; border: 1px solid #e6e9ef; border-radius: 16px; overflow: hidden; background: #fff; transition: transform .15s ease, box-shadow .15s ease; }
    .verification-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(15, 23, 42, .08); }
    .verification-card:focus-visible { outline: 3px solid rgba(255, 107, 53, .45); outline-offset: 3px; }
    .verification-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; background: #eef1f6; }
    .verification-gallery img { width: 100%; height: 150px; object-fit: cover; display: block; }
    .verification-gallery.single-image { display: block; }
    .verification-gallery.single-image img { height: 230px; }
    .verification-no-image { min-height: 230px; display: flex; align-items: center; justify-content: center; color: #8a94a6; background: #f2f4f8; }
    .verification-meta { color: #6b7280; font-size: .9rem; }
</style>

<div class="verification-summary p-4 mb-4 d-flex justify-content-between align-items-center gap-3">
    <div>
        <h5 class="mb-1">Pending Tourist Spot Submissions</h5>
        <p class="text-muted mb-0">Review submissions added by municipality admins before approval.</p>
    </div>
    <span class="badge bg-warning text-dark fs-6">{{ $pendingSpots->count() }} pending</span>
</div>

@if($pendingSpots->isNotEmpty())
    <div class="row g-4">
        @foreach($pendingSpots as $spot)
            @php $spotImages = collect($spot->image_urls ?? [])->map(fn ($image) => $resolveImageUrl($image))->filter()->values(); @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ route('tourist_spots.show', $spot->id) }}" class="verification-card h-100 flex-column">
                    @if($spotImages->isNotEmpty())
                        <div class="verification-gallery {{ $spotImages->count() === 1 ? 'single-image' : '' }}">
                            @foreach($spotImages as $image)
                                <img src="{{ $image }}" alt="{{ $spot->name }} image {{ $loop->iteration }}">
                            @endforeach
                        </div>
                    @else
                        <div class="verification-no-image"><i class="fas fa-image me-2"></i> No images uploaded</div>
                    @endif
                    <div class="p-3 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="mb-0">{{ $spot->name }}</h5>
                            <span class="badge bg-warning text-dark">Pending</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark border">{{ ucfirst($spot->category ?? 'nature') }}</span>
                            <span class="badge bg-light text-dark border">{{ $spot->municipality->name ?? 'Unknown Municipality' }}</span>
                        </div>
                        <div class="verification-meta mb-1"><i class="fas fa-calendar me-2"></i>Created {{ optional($spot->created_at)->format('M d, Y h:i A') }}</div>
                        @if($spot->creator)
                            <div class="verification-meta"><i class="fas fa-user me-2"></i>Added by {{ $spot->creator->name }}</div>
                        @endif
                        @if($spot->address)
                            <div class="verification-meta mt-2"><i class="fas fa-location-dot me-2"></i>{{ Str::limit($spot->address, 90) }}</div>
                        @endif
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="card"><div class="card-body text-center py-5 text-muted"><i class="fas fa-circle-check fa-2x mb-3 text-success"></i><div>No pending tourist spot submissions.</div></div></div>
@endif
@endsection
