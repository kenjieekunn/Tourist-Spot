<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\TouristSpot;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $this->authorizeReviewAccess();
        $reviews = Review::with('touristSpot')->paginate(15);
        return view('reviews.index', ['reviews' => $reviews]);
    }

    public function updateStatus(Review $review, Request $request)
    {
        $this->authorizeReviewAccess($review);
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $review->update($validated);

        return redirect()->back()->with('success', 'Review status updated successfully!');
    }

    public function destroy(Review $review)
    {
        $this->authorizeReviewAccess($review);
        $review->delete();
        return redirect()->back()->with('success', 'Review deleted successfully!');
    }

    private function authorizeReviewAccess(?Review $review = null): void
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin() && $user->hasPermission('manage_reviews'), 403, 'You do not have permission to manage reviews.');

        if ($review && $user->belongsToMunicipalityTeam()) {
            abort_unless($review->touristSpot && (int) $review->touristSpot->municipality_id === (int) $user->municipality_id, 403);
        }
    }
}
