<?php

namespace App\Http\Controllers;

use App\Models\Municipality;
use App\Models\Review;
use App\Models\TouristSpot;
use Throwable;

class LandingPageController extends Controller
{
    public function index()
    {
        try {
            $municipalities = Municipality::query()
                ->where(function ($query) {
                    $query->whereNull('is_active')->orWhere('is_active', true);
                })
                ->withCount(['touristSpots' => function ($query) {
                    $query->where('verification_status', 'approved');
                }])
                ->orderBy('name')
                ->get();

            $totalSpots = TouristSpot::where('verification_status', 'approved')->count();
            $totalReviews = Review::count();
        } catch (Throwable $exception) {
            $municipalities = collect([
                'Aguilar', 'Basista', 'Binmaley', 'Bugallon',
                'Lingayen', 'Malasiqui', 'Mapandan', 'San Carlos City',
            ])->map(function ($name) {
                return new Municipality(['name' => $name]);
            });
            $totalSpots = 10;
            $totalReviews = 0;
        }

        return view('landing', [
            'municipalities' => $municipalities,
            'featuredMunicipalities' => $municipalities->take(4),
            'totalSpots' => $totalSpots,
            'totalMunicipalities' => $municipalities->count(),
            'verifiedSpots' => $totalSpots,
            'totalReviews' => $totalReviews,
        ]);
    }
}