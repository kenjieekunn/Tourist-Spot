<?php

namespace App\Http\Controllers;

use App\Models\TouristSpot;
use App\Models\Municipality;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Route users to their respective dashboards based on role
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        } else if ($user->belongsToMunicipalityTeam()) {
            return redirect()->route('municipality-admin.dashboard');
        }
        
        // Generic dashboard for other users (fallback)
        $totalSpots = TouristSpot::count();
        $totalMunicipalities = Municipality::count();
        $totalReviews = Review::count();
        $recentSpots = TouristSpot::latest('created_at')->take(5)->get();
        $municipalities = Municipality::with('touristSpots')
            ->orderBy('name')
            ->take(8)
            ->get();
        $pendingReviews = Review::where('status', 'pending')->count();

        return view('dashboard.index', [
            'totalSpots' => $totalSpots,
            'totalMunicipalities' => $totalMunicipalities,
            'totalReviews' => $totalReviews,
            'recentSpots' => $recentSpots,
            'municipalities' => $municipalities,
            'pendingReviews' => $pendingReviews,
        ]);
    }
}
