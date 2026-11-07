@extends('layouts.app')

@section('title', 'Municipality Admin')
@section('header', '')

@section('content')
<style>
    .reviews-page .table { --bs-table-striped-bg: #fbfcfc; }
    .reviews-page .table thead th { color: #52666a; font-size: .78rem; white-space: nowrap; }
    .reviews-page .review-comment { min-width: 220px; max-width: 560px; overflow-wrap: anywhere; white-space: normal; }
    .reviews-page .review-rating { white-space: nowrap; }
</style>

<div class="reviews-page card mb-4">
    <div class="card-header bg-light"><h5 class="mb-0">Tourist Reviews</h5></div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th scope="col">Tourist</th>
                    <th scope="col">Tourist Spot</th>
                    <th scope="col">Rating</th>
                    <th scope="col">Comment</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    @php $rating = max(0, min(5, (int) $review->rating)); @endphp
                    <tr>
                        <td>{{ $review->user_name ?: ($review->user?->name ?? 'Tourist') }}</td>
                        <td>{{ $review->touristSpot?->name ?? 'Unknown Tourist Spot' }}</td>
                        <td class="review-rating">
                            <span class="visually-hidden">{{ $rating }} out of 5 stars</span>
                            @for($star = 1; $star <= 5; $star++)
                                <i class="fas fa-star {{ $star <= $rating ? 'text-warning' : 'text-body-tertiary' }}" aria-hidden="true"></i>
                            @endfor
                        </td>
                        <td class="review-comment">{{ $review->comment ?: 'No comment provided' }}</td>
                        <td class="text-nowrap">{{ $review->created_at?->format('M d, Y') ?? 'Not available' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No reviews found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())
        <div class="card-footer">{{ $reviews->links() }}</div>
    @endif
</div>

@endsection
