@extends('layouts.app')

@section('title', 'Municipality Info - Municipality Admin')
@section('header', $municipality->name . ' - Information')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">{{ $municipality->name }}</h5>
            </div>
            <div class="card-body">
                @if($municipality->image_url)
                    <div class="mb-3">
                        <img src="{{ preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url) }}" 
                             alt="{{ $municipality->name }}" class="img-fluid rounded" style="max-height: 400px;">
                    </div>
                @endif
                
                <h6 class="mb-3">Description</h6>
                <p class="text-muted">{{ $municipality->description ?? 'No description available' }}</p>
                
                <h6 class="mb-3">Location Coordinates</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <strong>Latitude:</strong> {{ number_format($municipality->latitude, 8) }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Longitude:</strong> {{ number_format($municipality->longitude, 8) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span>Total Tourist Spots</span>
                    <span class="badge bg-primary" style="font-size: 1rem;">{{ $stats['totalSpots'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span>Verified Spots</span>
                    <span class="badge bg-success" style="font-size: 1rem;">{{ $stats['verifiedSpots'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span>Pending Verification</span>
                    <span class="badge bg-warning text-dark" style="font-size: 1rem;">{{ $stats['pendingVerificationSpots'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Total Reviews</span>
                    <span class="badge bg-info" style="font-size: 1rem;">{{ $stats['totalReviews'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
