@extends('layouts.app')

@section('title', 'Municipality Admin')
@section('header', '')

@section('content')
@php
    $municipalityLogoName = strtolower(preg_replace('/[^a-z0-9]/i', '', $municipality->name)) . 'logo.png';
@endphp
<style>
    .reviews-report { color: #1f2937; background: #fff; }
    .reviews-report-header { border-bottom: 3px solid #164e63; padding: 1.5rem; text-align: center; }
    .reviews-report-branding { align-items: center; display: grid; grid-template-columns: 1fr minmax(0, 3fr) 1fr; gap: 1rem; }
    .reviews-report-logo { height: 78px; object-fit: contain; width: 78px; }
    .reviews-report-logo:first-child { justify-self: start; }
    .reviews-report-logo:last-child { justify-self: end; }
    .reviews-report-copy { min-width: 0; }
    .reviews-report-header h1, .reviews-report-header h2, .reviews-report-header p { margin: 0; }
    .reviews-report-header h1 { color: #123b4a; font-size: 1.35rem; text-transform: uppercase; }
    .reviews-report-header h2 { font-size: 1.1rem; margin-top: 0.45rem; text-transform: uppercase; }
    .reviews-report-mark { color: #164e63; font-size: 2rem; }
    .reviews-report-period { font-weight: 600; margin-top: 0.8rem !important; }
    .reviews-report-title { border-bottom: 2px solid #164e63; color: #164e63; font-size: 0.95rem; font-weight: 800; letter-spacing: 0.08em; margin: 1.25rem 1.5rem 0.75rem; padding-bottom: 0.35rem; text-transform: uppercase; }
    .reviews-report-summary { margin: 0 1.5rem; }
    .reviews-report-summary td { border: 0; padding: 0.28rem 0; }
    .reviews-report-summary td:last-child { font-weight: 700; text-align: right; }
    .reviews-report-table { margin: 0 1.5rem; width: calc(100% - 3rem); }
    .reviews-report-table th, .reviews-report-table td { border: 1px solid #9ca3af; padding: 0.5rem 0.6rem; vertical-align: top; }
    .reviews-report-table th { background: #e6f0f2; color: #123b4a; font-size: 0.78rem; text-transform: uppercase; }
    .reviews-report-signoff { margin: 2rem 1.5rem 1.5rem; }
    .reviews-report-signoff p { margin: 0.65rem 0; }
    .reviews-report-footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: 0.75rem; margin: 1.5rem; padding-top: 0.5rem; text-align: center; }
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        .sidebar, .review-controls, .main-col > header, .main-col > .alert { display: none !important; }
        .main-col, .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .reviews-report { box-shadow: none !important; margin: 0 !important; }
        .reviews-report-footer { bottom: 0; left: 0; margin: 0; position: fixed; right: 0; }
        .reviews-report-page-number::after { content: 'Page ' counter(page) ' of ' counter(pages); }
    }
</style>

<div class="review-controls card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('municipality-admin.reviews') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="review-date" class="form-label">Report date</label>
                <input type="date" name="date" id="review-date" value="{{ $reviewDate }}" class="form-control">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter Reviews</button>
                <a href="{{ route('municipality-admin.reviews') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
            <div class="col-12 col-md-auto ms-md-auto">
                <button type="button" class="btn btn-dark" onclick="window.print()"><i class="fas fa-print"></i> Print Feedback Report</button>
            </div>
        </form>
    </div>
</div>

<div class="card reviews-report mb-4">
    <header class="reviews-report-header">
        <div class="reviews-report-branding">
            <img class="reviews-report-logo" src="{{ asset('assets/report-logos/' . $municipalityLogoName) }}" alt="{{ $municipality->name }} logo">
            <div class="reviews-report-copy">
                <div class="reviews-report-mark"><i class="fas fa-comments"></i></div>
                <p>REPUBLIC OF THE PHILIPPINES</p><p>PROVINCE OF PANGASINAN</p><p>TOURISM OFFICE / TOURISM SYSTEM</p>
                <h1>Tourist Feedback and Reviews Report</h1>
                <h2>{{ $municipality->name }} | 2nd District of Pangasinan</h2>
                <p class="reviews-report-period">Report Period: {{ $reportPeriod }}</p>
            </div>
            <img class="reviews-report-logo" src="{{ asset('assets/report-logos/pangasinanlogo.png') }}" alt="Pangasinan logo">
        </div>
    </header>
    <div class="reviews-report-title">Summary</div>
    <table class="reviews-report-summary"><tbody>
        <tr><td>Total Reviews</td><td>{{ $totalReviews }}</td></tr>
        <tr><td>Average Rating</td><td>{{ number_format($averageRating, 1) }} / 5</td></tr>
    </tbody></table>
    <div class="reviews-report-title">Ratings by Tourist Spot</div>
    <table class="reviews-report-table"><thead><tr><th>Tourist Spot</th><th>Reviews</th><th>Rating</th></tr></thead><tbody>
        @forelse($reviewSummaries as $summary)
            <tr><td>{{ $summary['name'] }}</td><td>{{ $summary['reviewCount'] }}</td><td>{{ number_format($summary['averageRating'], 1) }} / 5</td></tr>
        @empty
            <tr><td colspan="3" class="text-center">No reviews found</td></tr>
        @endforelse
    </tbody></table>
    <div class="reviews-report-title">Tourist Comments</div>
    <table class="reviews-report-table"><thead><tr><th>Tourist Spot</th><th>Comment</th><th>Status</th><th>Date</th><th>Media</th></tr></thead><tbody>
        @forelse($reportReviews as $review)
            @php $reviewMedia = $review->media ?? collect($review->images ?? [])->map(fn ($path) => ['path' => $path, 'type' => 'image'])->all(); @endphp
            <tr><td>{{ $review->touristSpot?->name ?? 'Unknown Tourist Spot' }}</td><td>{{ $review->comment ?: 'No comment provided' }}</td><td>{{ ucfirst($review->status ?? 'pending') }}</td><td>{{ $review->created_at?->format('M d, Y') ?? 'Not available' }}</td><td>@forelse($reviewMedia as $media)<a href="{{ asset('storage/' . $media['path']) }}" target="_blank" rel="noopener">{{ ($media['type'] ?? 'image') === 'video' ? 'View video' : 'View image' }}</a>@if(!$loop->last), @endif @empty None @endforelse</td></tr>
        @empty
            <tr><td colspan="5" class="text-center">No tourist comments found</td></tr>
        @endforelse
    </tbody></table>
    <div class="reviews-report-signoff"><p>Prepared by: ____________________________________</p><p>Position: Municipal Tourism Officer</p><p>Date Printed: ___________________________________</p></div>
    <footer class="reviews-report-footer"><span class="reviews-report-page-number"></span></footer>
</div>

@endsection
