<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\TouristSpot;
use App\Models\Municipality;
use App\Models\Review;
use App\Models\User;
use App\Models\AdminTempCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuperAdminDashboardController extends Controller
{
    /**
     * Show the super admin dashboard with overview of all municipalities
     */
    public function index()
    {
        try {
            $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');

            $dashboardData = [
                'totalSpots' => TouristSpot::count(),
                'totalMunicipalities' => Municipality::count(),
                'totalReviews' => Review::count(),
                'totalAdmins' => User::where('role', 'municipality-admin')->count(),
                'pendingReviews' => Review::where('status', 'pending')->count(),
                'pendingVerificationSpots' => $hasVerificationStatus
                    ? TouristSpot::where('verification_status', 'pending')->count()
                    : 0,
                'municipalities' => Municipality::with('admins', 'touristSpots')
                    ->withCount(['touristSpots', 'admins'])
                    ->orderBy('name')
                    ->get(),
            ];
        } catch (\Exception $e) {
            Log::error('Super admin dashboard error: ' . $e->getMessage());

            $dashboardData = [
                'totalSpots' => 0,
                'totalMunicipalities' => 0,
                'totalReviews' => 0,
                'totalAdmins' => 0,
                'pendingReviews' => 0,
                'pendingVerificationSpots' => 0,
                'municipalities' => collect(),
            ];
        }

        return view('dashboard.super-admin', $dashboardData);
    }

    /**
     * Approve a tourist spot
     */
    public function approveSpot(TouristSpot $touristSpot)
    {
        $touristSpot->update([
            'verification_status' => 'approved',
            'status' => 'open',
        ]);

        return redirect()
            ->route('super-admin.tourist-spots')
            ->with('success', "Tourist spot '{$touristSpot->name}' has been approved!");
    }

    /**
     * Reject a tourist spot
     */
    public function rejectSpot(TouristSpot $touristSpot)
    {
        $touristSpot->update([
            'verification_status' => 'rejected',
            'status' => 'closed',
        ]);

        return redirect()
            ->route('super-admin.tourist-spots')
            ->with('success', "Tourist spot '{$touristSpot->name}' has been rejected!");
    }

    /**
     * View all tourist spots for verification
     */
    public function touristSpots()
    {
        try {
            $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');
            $searchTerm = trim((string) request()->query('q'));
            $spotCategories = [
                'beach' => 'Beach',
                'parks' => 'Parks',
                'falls' => 'Falls',
                'nature' => 'Nature',
                'resort' => 'Resort',
            ];

            $approvedQuery = TouristSpot::with('municipality', 'reviews')
                ->withCount('reviews')
                ->where(function ($query) use ($hasVerificationStatus) {
                    if ($hasVerificationStatus) {
                        $query->where('verification_status', 'approved');
                    } else {
                        $query->whereIn('status', ['open', 'active']);
                    }
                });

            $pendingQuery = $hasVerificationStatus
                ? TouristSpot::with('municipality', 'reviews')
                    ->withCount('reviews')
                    ->where('verification_status', 'pending')
                : TouristSpot::with('municipality', 'reviews')
                    ->withCount('reviews')
                    ->where('status', 'inactive');

            if ($searchTerm !== '') {
                $approvedQuery->where('name', 'like', '%' . $searchTerm . '%');
                $pendingQuery->where('name', 'like', '%' . $searchTerm . '%');
            }

            $approvedSpots = $approvedQuery
                ->orderBy('name')
                ->get();

            $pendingSpots = $pendingQuery
                ->orderByDesc('created_at')
                ->get();

            $groupedApprovedSpots = collect($spotCategories)->mapWithKeys(function ($label, $key) use ($approvedSpots) {
                return [
                    $key => $approvedSpots->filter(function ($spot) use ($key) {
                        return ($spot->category ?? 'nature') === $key;
                    })->values(),
                ];
            });

            return view('dashboard.super-admin-tourist-spots', [
                'pendingSpots' => $pendingSpots,
                'approvedSpots' => $approvedSpots,
                'groupedApprovedSpots' => $groupedApprovedSpots,
                'spotCategories' => $spotCategories,
                'hasVerificationStatus' => $hasVerificationStatus,
                'searchTerm' => $searchTerm,
            ]);
        } catch (\Exception $e) {
            Log::error('Super admin tourist spots view error: ' . $e->getMessage());
            return redirect()->route('super-admin.dashboard')->with('error', 'Error loading tourist spots. Please try again.');
        }
    }

    /**
     * View all municipality admins
     */
    public function admins(Request $request)
    {
        try {
            $searchTerm = trim((string) $request->query('q', ''));
            $municipalityId = (int) $request->query('municipality_id', 0);
            $status = (string) $request->query('status', 'all');
            if (!in_array($status, ['all', 'active', 'inactive'], true)) {
                $status = 'all';
            }

            $admins = User::where('role', 'municipality-admin')
                ->with('municipality')
                ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                    $query->where(function ($searchQuery) use ($searchTerm) {
                        $searchQuery->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('email', 'like', '%' . $searchTerm . '%')
                            ->orWhere('username', 'like', '%' . $searchTerm . '%');
                    });
                })
                ->when($municipalityId > 0, fn ($query) => $query->where('municipality_id', $municipalityId))
                ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();

            return view('dashboard.super-admin-admins', [
                'admins' => $admins,
                'municipalities' => Municipality::orderBy('name')->get(),
                'searchTerm' => $searchTerm,
                'municipalityId' => $municipalityId,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Super admin admins view error: ' . $e->getMessage());
            return redirect()->route('super-admin.dashboard')->with('error', 'Error loading admins. Please try again.');
        }
    }

    /**
     * Show the Add Municipality Admin form.
     */
    public function createAdmin()
    {
        return view('dashboard.super-admin-admin-create', [
            'municipalities' => Municipality::orderBy('name')->get(),
            'selectedMunicipalityId' => (int) request()->query('municipality_id', 0),
        ]);
    }

    /**
     * Create a municipality admin and assign the selected municipality.
     */
    public function storeAdmin(Request $request)
    {
        $hasUsernameColumn = Schema::hasColumn('users', 'username');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'municipality_id' => ['required', 'exists:municipalities,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ];

        if ($hasUsernameColumn) {
            $rules['username'] = ['required', 'string', 'max:255', 'unique:users,username'];
        }

        $validated = $request->validate($rules);
        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $hasUsernameColumn ? $validated['username'] : null,
            'password' => Hash::make($validated['password']),
            'role' => 'municipality-admin',
            'municipality_id' => $validated['municipality_id'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        AdminTempCredential::updateOrCreate(
            ['user_id' => $admin->id],
            ['password' => encrypt($validated['password'])]
        );

        return redirect()->route('super-admin.admins')->with('success', 'Municipality admin created successfully.');
    }

    /**
     * Show summary reports for all municipalities.
     */
    public function reports()
    {
        try {
            $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');
            $reportType = (string) request()->query('report_type', 'all');
            if (!in_array($reportType, ['all', 'overall', 'verification'], true)) {
                $reportType = 'all';
            }
            $municipalityId = (int) request()->query('municipality_id', 0);
            $status = (string) request()->query('status', 'all');
            if (!in_array($status, ['all', 'pending', 'approved'], true)) {
                $status = 'all';
            }
            $dateFrom = trim((string) request()->query('date_from', ''));
            $dateTo = trim((string) request()->query('date_to', ''));
            $periodStart = null;
            $periodEnd = null;

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                $periodStart = Carbon::createFromFormat('!Y-m-d', $dateFrom)->startOfDay();
            } else {
                $dateFrom = '';
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                $periodEnd = Carbon::createFromFormat('!Y-m-d', $dateTo)->endOfDay();
            } else {
                $dateTo = '';
            }
            if ($periodStart && $periodEnd && $periodStart->gt($periodEnd)) {
                [$periodStart, $periodEnd] = [$periodEnd->copy()->startOfDay(), $periodStart->copy()->endOfDay()];
                [$dateFrom, $dateTo] = [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')];
            }

            $reportPeriod = 'All dates';
            if ($periodStart && $periodEnd) {
                $reportPeriod = $periodStart->format('F j, Y') . '–' . $periodEnd->format('F j, Y');
            } elseif ($periodStart) {
                $reportPeriod = $periodStart->format('F j, Y');
            } elseif ($periodEnd) {
                $reportPeriod = 'Through ' . $periodEnd->format('F j, Y');
            }

            $municipalities = Municipality::with('touristSpots')
                ->withCount(['touristSpots', 'admins'])
                ->orderBy('name')
                ->get();
            $touristSpotsQuery = TouristSpot::with(['municipality', 'creator'])
                ->latest('created_at');

            $touristSpotsQuery
                ->when($municipalityId > 0, fn ($query) => $query->where('municipality_id', $municipalityId))
                ->when($status !== 'all' && $hasVerificationStatus, fn ($query) => $query->where('verification_status', $status))
                ->when($status !== 'all' && !$hasVerificationStatus, function ($query) use ($status) {
                    $query->whereIn('status', $status === 'approved' ? ['open', 'active'] : ['inactive']);
                })
                ->when($periodStart, fn ($query) => $query->where('created_at', '>=', $periodStart))
                ->when($periodEnd, fn ($query) => $query->where('created_at', '<=', $periodEnd));

            $touristSpots = $touristSpotsQuery->get()
                ->groupBy(function ($spot) {
                    return $spot->municipality?->name ?? 'Unknown Municipality';
                });

            $reportSpots = $touristSpots->flatten(1);

            return view('dashboard.super-admin-reports', [
                'totalSpots' => $reportSpots->count(),
                'totalMunicipalities' => $municipalities->count(),
                'totalAdmins' => User::where('role', 'municipality-admin')->count(),
                'totalReviews' => Review::count(),
                'municipalities' => $municipalities,
                'touristSpots' => $touristSpots,
                'verificationSpots' => $reportSpots,
                'hasVerificationStatus' => $hasVerificationStatus,
                'reportType' => $reportType,
                'municipalityId' => $municipalityId,
                'status' => $status,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'reportPeriod' => $reportPeriod,
                'verifiedSpots' => $hasVerificationStatus
                    ? $reportSpots->where('verification_status', 'approved')->count()
                    : $reportSpots->whereIn('status', ['open', 'active'])->count(),
                'pendingSpots' => $hasVerificationStatus
                    ? $reportSpots->where('verification_status', 'pending')->count()
                    : $reportSpots->where('status', 'inactive')->count(),
                'activeSpots' => $reportSpots->whereIn('status', ['open', 'active'])->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Super admin reports view error: ' . $e->getMessage());
            return redirect()->route('super-admin.dashboard')->with('error', 'Error loading reports. Please try again.');
        }
    }

    /**
     * Show the edit form for a municipality admin
     */
    public function editAdmin(User $admin)
    {
        if ($admin->role !== 'municipality-admin') {
            abort(404);
        }

        return view('dashboard.super-admin-admin-edit', [
            'admin' => $admin->load('municipality'),
        ]);
    }

    /**
     * Update a municipality admin's details
     */
    public function updateAdmin(Request $request, User $admin)
    {
        if ($admin->role !== 'municipality-admin') {
            abort(404);
        }

        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255'],
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'required|boolean',
        ];

        if ($hasUsernameColumn) {
            $rules['username'][] = Rule::unique('users', 'username')->ignore($admin->id);
        }

        $validated = $request->validate($rules);

        $username = $this->normalizeAdminUsername($validated['username']);
        $email = $this->resolveAdminEmail($username);

        if (User::where('email', $email)->where('id', '!=', $admin->id)->exists()) {
            throw ValidationException::withMessages([
                'username' => 'The generated login email is already taken.',
            ]);
        }

        $admin->name = $validated['name'];
        if ($hasUsernameColumn) {
            $admin->username = $username;
        }
        $admin->email = $email;
        $admin->is_active = $request->boolean('is_active');

        if (!empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
            AdminTempCredential::updateOrCreate(
                ['user_id' => $admin->id],
                ['password' => encrypt($validated['password'])]
            );
        }

        $admin->save();

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $host = parse_url($returnTo, PHP_URL_HOST);
            $scheme = parse_url($returnTo, PHP_URL_SCHEME);
            $isRelative = !str_starts_with($returnTo, 'http://') && !str_starts_with($returnTo, 'https://');

            if ($isRelative || ($host === $request->getHost() && in_array($scheme, [null, '', $request->getScheme()], true))) {
                return redirect($returnTo)->with('success', 'Municipality admin updated successfully.');
            }
        }

        return redirect()->route('super-admin.admins')->with('success', 'Municipality admin updated successfully.');
    }

    /**
     * Normalize the username entered by super admin.
     * Email-style usernames are preserved; plain usernames are slugged.
     */
    private function normalizeAdminUsername(string $username): string
    {
        $username = Str::lower(trim($username));

        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return $username;
        }

        $slug = Str::slug($username, '_');

        return $slug !== '' ? $slug : $username;
    }

    /**
     * Convert the stored username into the actual login email.
     */
    private function resolveAdminEmail(string $username): string
    {
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return $username;
        }

        return $username . '@tourist-spots.com';
    }

    /**
     * Toggle a municipality admin's active status
     */
    public function toggleAdminStatus(User $admin)
    {
        if ($admin->role !== 'municipality-admin') {
            abort(404);
        }

        $admin->update([
            'is_active' => ! $admin->is_active,
        ]);

        $message = $admin->is_active
            ? "Municipality admin '{$admin->name}' has been activated."
            : "Municipality admin '{$admin->name}' has been deactivated.";

        return back()->with('success', $message);
    }

    /**
     * Get admin password for viewing credentials
     */
    public function getAdminPassword(User $admin)
    {
        if ($admin->role !== 'municipality-admin') {
            abort(404);
        }

        $credential = AdminTempCredential::where('user_id', $admin->id)->first();

        if ($credential) {
            $password = decrypt($credential->password);
        } else {
            $password = 'Password not available';
        }

        return response()->json([
            'password' => $password,
        ]);
    }
}
