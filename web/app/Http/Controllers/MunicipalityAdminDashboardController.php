<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\TouristSpot;
use App\Models\Municipality;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Notifications\DatabaseNotification;

class MunicipalityAdminDashboardController extends Controller
{
    /**
     * Show the municipality admin dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Check if municipality is properly assigned
        if (!$user->municipality_id || !$user->municipality) {
            return redirect()->route('login.form')->with('error', 'Your municipality has not been assigned. Please contact the super admin.');
        }

        $municipality = $user->municipality;
        $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');

        $dashboardData = [
            'municipality' => $municipality,
            'totalSpots' => TouristSpot::where('municipality_id', $municipality->id)->count(),
            'verifiedSpots' => $hasVerificationStatus
                ? TouristSpot::where('municipality_id', $municipality->id)
                    ->where('verification_status', 'approved')
                    ->count()
                : TouristSpot::where('municipality_id', $municipality->id)
                    ->whereIn('status', ['open', 'active'])
                    ->count(),
            'pendingVerificationSpots' => $hasVerificationStatus
                ? TouristSpot::where('municipality_id', $municipality->id)
                    ->where('verification_status', 'pending')
                    ->count()
                : TouristSpot::where('municipality_id', $municipality->id)
                    ->where('status', 'inactive')
                    ->count(),
            'rejectedSpots' => $hasVerificationStatus
                ? TouristSpot::where('municipality_id', $municipality->id)
                    ->where('verification_status', 'rejected')
                    ->count()
                : 0,
            'closedSpots' => TouristSpot::where('municipality_id', $municipality->id)
                ->where('status', 'closed')
                ->count(),
            'totalReviews' => Review::whereHas('touristSpot', function ($query) use ($municipality) {
                $query->where('municipality_id', $municipality->id);
            })->count(),
            'pendingReviews' => Review::where('status', 'pending')
                ->whereHas('touristSpot', function ($query) use ($municipality) {
                    $query->where('municipality_id', $municipality->id);
                })
                ->count(),
            'recentSpots' => TouristSpot::where('municipality_id', $municipality->id)
                ->latest('created_at')
                ->take(8)
                ->get(),
            'pendingSpots' => $hasVerificationStatus
                ? TouristSpot::where('municipality_id', $municipality->id)
                    ->where('verification_status', 'pending')
                    ->latest('created_at')
                    ->take(5)
                    ->get()
                : TouristSpot::where('municipality_id', $municipality->id)
                    ->where('status', 'inactive')
                    ->latest('created_at')
                    ->take(5)
                    ->get(),
            'recentReviews' => Review::where('status', 'pending')
                ->whereHas('touristSpot', function ($query) use ($municipality) {
                    $query->where('municipality_id', $municipality->id);
                })
                ->latest('created_at')
                ->take(5)
                ->with('touristSpot')
                ->get(),
            'revisionNotifications' => $user->unreadNotifications()
                ->where('type', 'App\\Notifications\\SpotRevisionRequested')
                ->latest()
                ->take(10)
                ->get(),
        ];

        return view('dashboard.municipality-admin', $dashboardData);
    }

    public function openNotification(DatabaseNotification $notification)
    {
        abort_unless(auth()->user()->hasPermission('manage_spots'), 403, 'You do not have permission to manage tourist spots.');
        abort_unless($notification->notifiable_id === auth()->id(), 403);
        $notification->markAsRead();
        $spotId = data_get($notification->data, 'tourist_spot_id');

        return $spotId
            ? redirect()->route('tourist_spots.edit', $spotId)
            : redirect()->route('municipality-admin.dashboard');
    }

    public function markNotificationsRead()
    {
        abort_unless(auth()->user()->hasPermission('manage_spots'), 403, 'You do not have permission to manage tourist spots.');
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Revision notifications marked as read.');
    }

    /**
     * View all tourist spots for this municipality
     */
    public function touristSpots(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('manage_spots'), 403, 'You do not have permission to manage tourist spots.');
        
        if (!$user->municipality_id || !$user->municipality) {
            return redirect()->route('login.form')->with('error', 'Your municipality has not been assigned.');
        }

        $municipality = $user->municipality;
        $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');
        $searchTerm = trim((string) $request->query('q', ''));
        $selectedCategory = trim((string) $request->query('category', 'all'));
        $spotCategories = [
            'all' => 'All Categories',
            'beach' => 'Beach',
            'parks' => 'Parks',
            'falls' => 'Falls',
            'nature' => 'Nature',
            'resort' => 'Resort',
        ];

        $spotsQuery = TouristSpot::where('municipality_id', $municipality->id)
            ->with('reviews')
            ->withCount(['reviews'])
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($searchQuery) use ($searchTerm) {
                    $searchQuery->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('address', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            })
            ->when($selectedCategory !== 'all' && array_key_exists($selectedCategory, $spotCategories), function ($query) use ($selectedCategory) {
                $query->where('category', $selectedCategory);
            });

        if ($hasVerificationStatus) {
            $spotsQuery->where('verification_status', 'approved');
        } else {
            $spotsQuery->whereIn('status', ['open', 'active']);
        }

        $pendingSpotsQuery = TouristSpot::where('municipality_id', $municipality->id)
            ->with('reviews')
            ->withCount(['reviews'])
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($searchQuery) use ($searchTerm) {
                    $searchQuery->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('address', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            })
            ->when($selectedCategory !== 'all' && array_key_exists($selectedCategory, $spotCategories), function ($query) use ($selectedCategory) {
                $query->where('category', $selectedCategory);
            });

        if ($hasVerificationStatus) {
            $pendingSpotsQuery->where('verification_status', 'pending');
        } else {
            $pendingSpotsQuery->where('status', 'inactive');
        }

        $pendingSpots = $pendingSpotsQuery->latest('created_at')->get();
        $spots = $spotsQuery->paginate(20)->withQueryString();

        return view('dashboard.municipality-admin-spots', [
            'municipality' => $municipality,
            'spots' => $spots,
            'pendingSpots' => $pendingSpots,
            'searchTerm' => $searchTerm,
            'selectedCategory' => $selectedCategory,
            'spotCategories' => $spotCategories,
        ]);
    }

    /**
     * Show reports for tourist spots in this municipality.
     */
    public function reports(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('view_reports'), 403, 'You do not have permission to view reports.');

        if (!$user->municipality_id || !$user->municipality) {
            return redirect()->route('login.form')->with('error', 'Your municipality has not been assigned.');
        }

        $municipality = $user->municipality;
        $searchTerm = trim((string) $request->query('q', ''));
        $reportDate = trim((string) $request->query('date', ''));
        $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');

        $spotsQuery = TouristSpot::where('municipality_id', $municipality->id)
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($searchQuery) use ($searchTerm) {
                    $searchQuery->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('address', 'like', '%' . $searchTerm . '%')
                        ->orWhere('category', 'like', '%' . $searchTerm . '%');
                });
            })
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate), function ($query) use ($reportDate) {
                $query->whereDate('created_at', $reportDate);
            })
            ->latest('created_at');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
            $reportDate = '';
        }

        $reportSpots = $spotsQuery->get();

        return view('dashboard.municipality-admin-reports', [
            'municipality' => $municipality,
            'spots' => $reportSpots,
            'searchTerm' => $searchTerm,
            'reportDate' => $reportDate,
            'hasVerificationStatus' => $hasVerificationStatus,
            'totalSpots' => $reportSpots->count(),
            'verifiedSpots' => $hasVerificationStatus
                ? $reportSpots->where('verification_status', 'approved')->count()
                : $reportSpots->whereIn('status', ['open', 'active'])->count(),
            'pendingSpots' => $hasVerificationStatus
                ? $reportSpots->where('verification_status', 'pending')->count()
                : $reportSpots->where('status', 'inactive')->count(),
            'activeSpots' => $reportSpots->whereIn('status', ['open', 'active'])->count(),
            'reportPeriod' => $reportDate !== ''
                ? Carbon::createFromFormat('!Y-m-d', $reportDate)->format('F j, Y')
                : 'All dates',
        ]);
    }

    /**
     * View reviews for this municipality
     */
    public function reviews(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('manage_reviews'), 403, 'You do not have permission to manage reviews.');
        
        if (!$user->municipality_id || !$user->municipality) {
            return redirect()->route('login.form')->with('error', 'Your municipality has not been assigned.');
        }

        $municipality = $user->municipality;
        $reviewDate = trim((string) $request->query('date', ''));
        $reviewsQuery = Review::whereHas('touristSpot', function ($query) use ($municipality) {
            $query->where('municipality_id', $municipality->id);
        })
            ->with('touristSpot')
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $reviewDate), function ($query) use ($reviewDate) {
                $query->whereDate('created_at', $reviewDate);
            })
            ->orderBy('status');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reviewDate)) {
            $reviewDate = '';
        }

        $reportReviews = (clone $reviewsQuery)->latest('created_at')->get();
        $reviewSummaries = $reportReviews->groupBy('tourist_spot_id')->map(function ($spotReviews) {
            return [
                'name' => $spotReviews->first()->touristSpot?->name ?? 'Unknown Tourist Spot',
                'reviewCount' => $spotReviews->count(),
                'averageRating' => round((float) $spotReviews->avg('rating'), 1),
            ];
        })->sortByDesc('reviewCount')->values();

        $reviews = $reviewsQuery
            ->paginate(20);

        return view('dashboard.municipality-admin-reviews', [
            'municipality' => $municipality,
            'reviews' => $reviews,
            'reportReviews' => $reportReviews,
            'reviewSummaries' => $reviewSummaries,
            'reviewDate' => $reviewDate,
            'totalReviews' => $reportReviews->count(),
            'averageRating' => $reportReviews->isNotEmpty() ? round((float) $reportReviews->avg('rating'), 1) : 0,
            'reportPeriod' => $reviewDate !== ''
                ? Carbon::createFromFormat('!Y-m-d', $reviewDate)->format('F j, Y')
                : 'All dates',
        ]);
    }

    /**
     * View municipality information
     */
    public function municipality()
    {
        $user = auth()->user();
        
        if (!$user->municipality_id || !$user->municipality) {
            return redirect()->route('login.form')->with('error', 'Your municipality has not been assigned.');
        }

        $municipality = $user->municipality;
        $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');
        $stats = [
            'totalSpots' => TouristSpot::where('municipality_id', $municipality->id)->count(),
            'verifiedSpots' => $hasVerificationStatus
                ? TouristSpot::where('municipality_id', $municipality->id)->where('verification_status', 'approved')->count()
                : TouristSpot::where('municipality_id', $municipality->id)->whereIn('status', ['open', 'active'])->count(),
            'pendingVerificationSpots' => $hasVerificationStatus
                ? TouristSpot::where('municipality_id', $municipality->id)->where('verification_status', 'pending')->count()
                : TouristSpot::where('municipality_id', $municipality->id)->where('status', 'inactive')->count(),
            'totalReviews' => Review::whereHas('touristSpot', function ($query) use ($municipality) {
                $query->where('municipality_id', $municipality->id);
            })->count(),
        ];

        return view('dashboard.municipality-admin-info', [
            'municipality' => $municipality,
            'stats' => $stats,
        ]);
    }
}
