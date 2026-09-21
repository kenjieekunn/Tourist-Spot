@extends('layouts.app')

@section('title', 'Reviews Management')
@section('header', 'Manage Reviews')

@section('styles')
<style>
    .review-image-thumbnail {
        max-width: 60px;
        max-height: 60px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .review-image-thumbnail:hover {
        transform: scale(1.1);
    }
    .modal-body img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
    }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover m-0">
            <thead class="table-light">
                <tr>
                    <th>Tourist Spot</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td>
                            <strong>{{ $review->touristSpot->name }}</strong>
                        </td>
                        <td>
                            {{ $review->user_name }}
                        </td>
                        <td>
                            <small class="text-warning">
                                @for($i = 0; $i < $review->rating; $i++)
                                    <i class="fas fa-star"></i>
                                @endfor
                                ({{ $review->rating }}/5)
                            </small>
                        </td>
                        <td>
                            <small>{{ Str::limit($review->comment, 40) }}</small>
                        </td>
                        <td>
                            @if($review->images && count($review->images) > 0)
                                <div class="d-flex gap-1">
                                    @foreach($review->images as $index => $imagePath)
                                        <img 
                                            src="{{ asset('storage/' . $imagePath) }}" 
                                            alt="Review image {{ $index + 1 }}" 
                                            class="review-image-thumbnail"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#imageModal{{ $review->id }}"
                                            onclick="setModalImage('{{ asset('storage/' . $imagePath) }}')"
                                        >
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $review->status === 'approved' ? 'success' : ($review->status === 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($review->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <form action="{{ route('reviews.destroy', $review->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @if($review->images && count($review->images) > 0)
                    <!-- Images Modal -->
                    <div class="modal fade" id="imageModal{{ $review->id }}" tabindex="-1" aria-labelledby="imageModalLabel{{ $review->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="imageModalLabel{{ $review->id }}">
                                        Review Images - {{ $review->user_name }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="reviewImagesCarousel{{ $review->id }}" class="carousel slide mb-3" data-bs-ride="carousel">
                                        <div class="carousel-inner">
                                            @foreach($review->images as $index => $imagePath)
                                                <div class="carousel-item @if($index === 0) active @endif">
                                                    <img src="{{ asset('storage/' . $imagePath) }}" alt="Review image {{ $index + 1 }}" style="width: 100%; max-height: 400px; object-fit: contain;">
                                                </div>
                                            @endforeach
                                        </div>
                                        @if(count($review->images) > 1)
                                            <button class="carousel-control-prev" type="button" data-bs-target="#reviewImagesCarousel{{ $review->id }}" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#reviewImagesCarousel{{ $review->id }}" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <p><strong>Tourist Spot:</strong> {{ $review->touristSpot->name }}</p>
                                        <p><strong>User:</strong> {{ $review->user_name }}</p>
                                        <p><strong>Rating:</strong> 
                                            @for($i = 0; $i < $review->rating; $i++)
                                                <i class="fas fa-star text-warning"></i>
                                            @endfor
                                            ({{ $review->rating }}/5)
                                        </p>
                                        <p><strong>Comment:</strong> {{ $review->comment }}</p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-{{ $review->status === 'approved' ? 'success' : ($review->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($review->status) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No reviews found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">
    {{ $reviews->links() }}
</div>
@endsection

@section('scripts')
<script>
function setModalImage(imageSrc) {
    // This function is called when clicking on thumbnail images
    // The carousel will handle displaying the correct image
}
</script>
@endsection
