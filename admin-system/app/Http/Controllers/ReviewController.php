<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\TouristSpot;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with('touristSpot')->paginate(15);
        return view('reviews.index', ['reviews' => $reviews]);
    }

    public function updateStatus(Review $review, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $review->update($validated);

        return redirect()->back()->with('success', 'Review status updated successfully!');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return redirect()->back()->with('success', 'Review deleted successfully!');
    }
}
