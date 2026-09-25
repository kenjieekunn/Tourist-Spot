@extends('layouts.app')

@section('title', 'Edit Municipality: ' . $municipality->name)
@section('header', 'Edit Municipality: ' . $municipality->name)

@section('content')
@php
    $totalSpots = $municipality->touristSpots->count();
    $approvedSpots = $municipality->touristSpots->where('verification_status', 'approved')->count();
    $assignedAdmin = $municipality->admins->first();
    $imageSource = $municipality->image_url && preg_match('#^https?://#i', $municipality->image_url)
        ? $municipality->image_url
        : ($municipality->image_url ? url($municipality->image_url) : null);
@endphp

<style>
    .municipality-edit-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .municipality-edit-page .breadcrumb { --bs-breadcrumb-divider: '>'; font-size: .88rem; }
    .municipality-edit-page .breadcrumb a { color: var(--tourism-teal); text-decoration: none; }
    .municipality-edit-page .page-heading { border: 1px solid #c8e5da; border-radius: 10px; padding: 1.1rem 1.25rem; background: linear-gradient(135deg, #effaf7, #f7fbf3); color: var(--tourism-ink); }
    .municipality-edit-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .municipality-edit-page .card-header { color: var(--tourism-ink); border-bottom-color: #dce9e5; }
    .municipality-edit-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .municipality-edit-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .municipality-edit-page .info-icon { color: var(--tourism-teal); }
    .municipality-edit-page .image-frame { aspect-ratio: 3 / 2; overflow: hidden; border-radius: 8px; background: #eef5f2; border: 1px solid #dce9e5; }
    .municipality-edit-page .image-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .municipality-edit-page .empty-image { height: 100%; display: flex; align-items: center; justify-content: center; color: #6b8580; }
    .municipality-edit-page .attention { color: #946c10; background: #fff9e8; border: 1px solid #e9c76a; }
    .municipality-edit-page .overview-link { color: var(--tourism-teal); font-weight: 600; text-decoration: none; }
</style>

<div class="municipality-edit-page">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Provincial Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('municipalities.index') }}">2nd District Municipalities</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit: {{ $municipality->name }}</li>
        </ol>
    </nav>

    <div class="page-heading mb-4">
        <div class="small text-uppercase fw-bold" style="color: var(--tourism-teal); letter-spacing: .06em;">Pangasinan 2nd District</div>
        <h2 class="h4 mb-0">Edit Municipality: {{ $municipality->name }}</h2>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light"><h5 class="mb-0">Municipality Details</h5></div>
                <div class="card-body p-4">
                    <form action="{{ route('municipalities.update', $municipality->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_active" value="0">

                        <div class="mb-3">
                            <label for="name" class="form-label">Municipality Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $municipality->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description', $municipality->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="image" class="form-label">Municipality Image</label>
                            <div class="image-frame mb-3">
                                @if($imageSource)<img src="{{ $imageSource }}" alt="{{ $municipality->name }} image">@else<div class="empty-image"><i class="fas fa-image me-2"></i>No image uploaded</div>@endif
                            </div>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Recommended: 1200x800px, landscape orientation. JPG, PNG, or WebP, maximum 2MB.</div>
                            @if($municipality->image_url)
                                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image"><label class="form-check-label" for="remove_image">Remove current image</label></div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="is_active" class="form-label d-block">Municipality Status</label>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $municipality->is_active ?? true))><label class="form-check-label" for="is_active">Active and visible in the system</label></div>
                            <div class="form-text">Turn this off to hide the municipality from public municipality listings.</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="btn btn-tourism"><i class="fas fa-save me-1"></i> Save Changes</button>
                            <a href="{{ route('municipalities.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-circle-info info-icon me-2"></i>Municipality Overview</h5></div>
                <div class="card-body">
                    <div class="mb-3"><small class="text-muted d-block">Total Spots</small><strong class="{{ $totalSpots === 0 ? 'text-warning' : '' }}">{{ $totalSpots }}{{ $totalSpots === 0 ? ' - No spots yet' : '' }}</strong><div class="small text-muted">{{ $approvedSpots }} Approved</div></div>
                    <div class="mb-3"><small class="text-muted d-block">Assigned Admin</small>@if($assignedAdmin)<a class="overview-link" href="{{ route('super-admin.admins.edit', $assignedAdmin) }}"><i class="fas fa-user-shield me-1"></i>{{ $assignedAdmin->name }}</a>@else<span class="text-muted">No admin assigned</span>@endif</div>
                    <div class="mb-3"><a class="overview-link" href="{{ route('municipalities.show', $municipality) }}"><i class="fas fa-location-dot me-1"></i> View Tourist Spots</a></div>
                    <hr>
                    <div class="mb-3"><small class="text-muted d-block">Created</small><strong>{{ optional($municipality->created_at)->format('M d, Y H:i') }}</strong></div>
                    <div class="mb-0"><small class="text-muted d-block">Updated</small><strong>{{ optional($municipality->updated_at)->format('M d, Y H:i') }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
