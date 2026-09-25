<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\TouristSpot;
use App\Models\Municipality;
use App\Models\Review;
use App\Models\User;
use App\Models\AdminTempCredential;
use App\Notifications\SpotRevisionRequested;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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
            $municipalityQuery = Municipality::with(['admins', 'touristSpots'])
                ->withCount(['touristSpots', 'admins', 'staffAccounts']);

            if ($hasVerificationStatus) {
                $municipalityQuery->withCount([
                    'touristSpots as approved_spots_count' => fn ($query) => $query->where('verification_status', 'approved'),
                    'touristSpots as pending_spots_count' => fn ($query) => $query->where('verification_status', 'pending'),
                ]);
            }

            $recentSpots = TouristSpot::with(['municipality', 'creator'])
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(fn ($spot) => [
                    'type' => 'spot',
                    'icon' => 'fa-location-dot',
                    'title' => $spot->name,
                    'description' => ($spot->creator?->name ?? 'An admin') . ' added a tourist spot in ' . ($spot->municipality?->name ?? 'a municipality'),
                    'url' => route('tourist_spots.show', $spot),
                    'created_at' => $spot->created_at,
                ]);
            $recentAdmins = User::whereIn('role', ['municipality-admin', 'municipality-staff'])
                ->with('municipality')
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(fn ($user) => [
                    'type' => 'admin',
                    'icon' => 'fa-user-plus',
                    'title' => $user->name,
                    'description' => 'New ' . str_replace('-', ' ', $user->role) . ' account for ' . ($user->municipality?->name ?? 'the system'),
                    'url' => route('super-admin.admins'),
                    'created_at' => $user->created_at,
                ]);
            $recentReviews = Review::with('touristSpot')
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(fn ($review) => [
                    'type' => 'review',
                    'icon' => 'fa-star',
                    'title' => 'Review for ' . ($review->touristSpot?->name ?? 'tourist spot'),
                    'description' => ($review->user_name ?: 'A visitor') . ' submitted a visitor review',
                    'url' => route('reviews.index'),
                    'created_at' => $review->created_at,
                ]);

            $dashboardData = [
                'totalSpots' => TouristSpot::count(),
                'totalMunicipalities' => Municipality::count(),
                'totalReviews' => Review::count(),
                'totalAdmins' => User::where('role', 'municipality-admin')->count(),
                'pendingReviews' => Review::where('status', 'pending')->count(),
                'pendingVerificationSpots' => $hasVerificationStatus
                    ? TouristSpot::where('verification_status', 'pending')->count()
                    : 0,
                'municipalities' => $municipalityQuery->orderBy('name')->get(),
                'recentActivity' => $recentSpots->merge($recentAdmins)->merge($recentReviews)
                    ->sortByDesc('created_at')
                    ->take(8)
                    ->values(),
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
                'recentActivity' => collect(),
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
        if (Schema::hasColumn('tourist_spots', 'rejection_reason')) {
            $touristSpot->update(['rejection_reason' => null]);
        }
        $this->recordVerificationEvent($touristSpot, 'approved');

        return redirect()
            ->route('super-admin.tourist-spots')
            ->with('success', "Tourist spot '{$touristSpot->name}' has been approved!");
    }

    /**
     * Reject a tourist spot
     */
    public function rejectSpot(Request $request, TouristSpot $touristSpot)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $touristSpot->update([
            'verification_status' => 'rejected',
            'status' => 'closed',
        ]);
        if (Schema::hasColumn('tourist_spots', 'rejection_reason')) {
            $touristSpot->update(['rejection_reason' => $validated['reason']]);
        }
        $this->recordVerificationEvent($touristSpot, 'rejected', $validated['reason']);

        return redirect()
            ->route('super-admin.tourist-spots')
            ->with('success', "Tourist spot '{$touristSpot->name}' has been rejected!");
    }

    public function requestSpotRevision(Request $request, TouristSpot $touristSpot)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $touristSpot->update([
            'verification_status' => 'pending',
            'status' => 'closed',
        ]);
        if (Schema::hasColumn('tourist_spots', 'rejection_reason')) {
            $touristSpot->update(['rejection_reason' => 'Revision requested: ' . $validated['reason']]);
        }
        $this->recordVerificationEvent($touristSpot, 'revision_requested', $validated['reason']);
        $touristSpot->loadMissing('municipality.admins');
        if (Schema::hasTable('notifications')) {
            foreach ($touristSpot->municipality?->admins ?? collect() as $municipalityAdmin) {
                if ($municipalityAdmin->is_active) {
                    $municipalityAdmin->notify(new SpotRevisionRequested($touristSpot, $validated['reason']));
                }
            }
        }

        return redirect()->route('tourist_spots.show', $touristSpot)
            ->with('success', "Revision requested for '{$touristSpot->name}'.");
    }

    private function recordVerificationEvent(TouristSpot $touristSpot, string $action, ?string $note = null): void
    {
        if (!Schema::hasTable('tourist_spot_verification_events')) {
            return;
        }

        DB::table('tourist_spot_verification_events')->insert([
            'tourist_spot_id' => $touristSpot->id,
            'actor_id' => auth()->id(),
            'action' => $action,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * View all tourist spots for verification
     */
    public function touristSpots()
    {
        try {
            $hasVerificationStatus = Schema::hasColumn('tourist_spots', 'verification_status');

            $pendingQuery = $hasVerificationStatus
                ? TouristSpot::with('municipality', 'creator')
                    ->withCount('reviews')
                    ->where('verification_status', 'pending')
                : TouristSpot::with('municipality', 'creator')
                    ->withCount('reviews')
                    ->where('status', 'inactive');

            $pendingSpots = $pendingQuery
                ->orderByDesc('created_at')
                ->get();

            return view('dashboard.super-admin-tourist-spots', [
                'pendingSpots' => $pendingSpots,
                'hasVerificationStatus' => $hasVerificationStatus,
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
            $search = trim((string) $request->query('search', ''));
            $status = $request->query('status', 'all');
            if (!in_array($status, ['all', 'active', 'disabled'], true)) {
                $status = 'all';
            }

            $hasUsernameColumn = Schema::hasColumn('users', 'username');

            $admins = User::where('role', 'municipality-admin')
                ->with(['municipality' => fn ($query) => $query->withCount(['touristSpots', 'staffAccounts'])])
                ->when($search !== '', function ($query) use ($search, $hasUsernameColumn) {
                    $query->where(function ($adminQuery) use ($search, $hasUsernameColumn) {
                        $adminQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');

                        if ($hasUsernameColumn) {
                            $adminQuery->orWhere('username', 'like', '%' . $search . '%');
                        }

                        $adminQuery->orWhereHas('municipality', function ($municipalityQuery) use ($search) {
                            $municipalityQuery->where('name', 'like', '%' . $search . '%');
                        });
                    });
                })
                ->when($status === 'active', fn ($query) => $query->where('is_active', true))
                ->when($status === 'disabled', fn ($query) => $query->where('is_active', false))
                ->orderBy('name')
                ->paginate(15);

            $municipalityCount = Municipality::count();
            $municipalitiesWithAdmins = User::where('role', 'municipality-admin')
                ->whereNotNull('municipality_id')
                ->distinct('municipality_id')
                ->count('municipality_id');

            return view('dashboard.super-admin-admins', [
                'admins' => $admins,
                'search' => $search,
                'status' => $status,
                'municipalityCount' => $municipalityCount,
                'municipalitiesWithAdmins' => $municipalitiesWithAdmins,
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
            'municipalities' => Municipality::whereDoesntHave('admins')->orderBy('name')->get(),
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
            'login' => ['required', 'string', 'max:255'],
            'municipality_id' => [
                'required',
                'exists:municipalities,id',
                Rule::unique('users', 'municipality_id')->where(fn ($query) => $query->where('role', 'municipality-admin')),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[^A-Za-z0-9]/'],
            'is_active' => ['required', 'boolean'],
            'max_staff_accounts' => ['required', 'integer', 'min:0', 'max:10000'],
        ];

        $validated = $request->validate($rules);
        $login = $this->normalizeAdminUsername($validated['login']);
        $email = $this->resolveAdminEmail($login);
        $this->ensureLoginIsAvailable($login, $email);
        $admin = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'username' => $hasUsernameColumn ? $login : null,
            'password' => Hash::make($validated['password']),
            'role' => 'municipality-admin',
            'municipality_id' => $validated['municipality_id'],
            'is_active' => (bool) $validated['is_active'],
            'permissions' => $this->normalizePermissions(array_keys($this->adminPermissions())),
            'max_staff_accounts' => $validated['max_staff_accounts'],
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
            $reportType = (string) request()->query('report_type', 'management');
            if (!in_array($reportType, ['management', 'verification', 'reviews'], true)) {
                $reportType = 'management';
            }
            $hasGeneratedReport = request()->boolean('generated');
            $municipalityId = (int) request()->query('municipality_id', 0);
            $spotName = trim((string) request()->query('spot_name', ''));
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

            $municipalities = Municipality::with(['touristSpots', 'admins'])
                ->withCount(['touristSpots', 'admins'])
                ->orderBy('name')
                ->get();
            $touristSpotsQuery = TouristSpot::with(['municipality.admins', 'creator'])
                ->latest('created_at');

            $touristSpotsQuery
                ->when($municipalityId > 0, fn ($query) => $query->where('municipality_id', $municipalityId))
                ->when($spotName !== '', fn ($query) => $query->where('name', 'like', '%' . $spotName . '%'))
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
            $reportReviews = Review::with(['touristSpot.municipality'])
                ->latest('created_at')
                ->when($municipalityId > 0, fn ($query) => $query->whereHas('touristSpot', fn ($spotQuery) => $spotQuery->where('municipality_id', $municipalityId)))
                ->when($spotName !== '', fn ($query) => $query->whereHas('touristSpot', fn ($spotQuery) => $spotQuery->where('name', 'like', '%' . $spotName . '%')))
                ->when($status !== 'all', fn ($query) => $query->where('status', $status === 'approved' ? 'approved' : 'pending'))
                ->when($periodStart, fn ($query) => $query->where('created_at', '>=', $periodStart))
                ->when($periodEnd, fn ($query) => $query->where('created_at', '<=', $periodEnd))
                ->get();
            $emptyMunicipalities = $municipalities
                ->filter(fn ($municipality) => $municipality->tourist_spots_count === 0)
                ->count();

            return view('dashboard.super-admin-reports', [
                'totalSpots' => $reportSpots->count(),
                'totalMunicipalities' => $municipalities->count(),
                'totalAdmins' => User::where('role', 'municipality-admin')->count(),
                'totalReviews' => $reportReviews->count(),
                'municipalities' => $municipalities,
                'touristSpots' => $touristSpots,
                'verificationSpots' => $reportSpots,
                'reportReviews' => $reportReviews,
                'emptyMunicipalities' => $emptyMunicipalities,
                'hasGeneratedReport' => $hasGeneratedReport,
                'hasVerificationStatus' => $hasVerificationStatus,
                'reportType' => $reportType,
                'municipalityId' => $municipalityId,
                'spotName' => $spotName,
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

        $admin->load('municipality');
        $municipality = $admin->municipality;

        return view('dashboard.super-admin-admin-edit', [
            'admin' => $admin,
            'permissions' => $this->adminPermissions(),
            'touristSpotsCount' => $municipality ? TouristSpot::where('municipality_id', $municipality->id)->count() : 0,
            'staffAccountsUsed' => $municipality
                ? User::where('role', 'municipality-staff')->where('municipality_id', $municipality->id)->count()
                : 0,
        ]);
    }

    /**
     * Show read-only details for a municipality admin.
     */
    public function showAdmin(User $admin)
    {
        if ($admin->role !== 'municipality-admin') {
            abort(404);
        }

        $admin->load('municipality');
        $municipality = $admin->municipality;
        $permissions = $this->adminPermissions();
        $enabledPermissions = $admin->permissions === null
            ? array_keys($permissions)
            : collect($admin->permissions)->filter(fn ($enabled) => $enabled === true)->keys()->all();

        return view('dashboard.super-admin-admin-show', [
            'admin' => $admin,
            'permissions' => $permissions,
            'enabledPermissions' => $enabledPermissions,
            'touristSpotsCount' => $municipality ? TouristSpot::where('municipality_id', $municipality->id)->count() : 0,
            'staffAccountsUsed' => $municipality
                ? User::where('role', 'municipality-staff')->where('municipality_id', $municipality->id)->count()
                : 0,
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
            'login' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[^A-Za-z0-9]/'],
            'is_active' => 'required|boolean',
            'permissions' => 'nullable|array',
            'max_staff_accounts' => ['required', 'integer', 'min:0', 'max:10000'],
        ];

        $validated = $request->validate($rules);

        $username = $this->normalizeAdminUsername($validated['login']);
        $email = $this->resolveAdminEmail($username);
        $this->ensureLoginIsAvailable($username, $email, $admin->id);

        $admin->name = $validated['name'];
        if ($hasUsernameColumn) {
            $admin->username = $username;
        }
        $admin->email = $email;
        $admin->is_active = $request->boolean('is_active');
        if ($request->has('permissions')) {
            $admin->permissions = $this->normalizePermissions($request->input('permissions', []));
        }
        $admin->max_staff_accounts = $validated['max_staff_accounts'];

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

    private function ensureLoginIsAvailable(string $username, string $email, ?int $ignoreId = null): void
    {
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        $query = User::where(function ($builder) use ($username, $email, $hasUsernameColumn) {
            $builder->where('email', $email);

            if ($hasUsernameColumn) {
                $builder->orWhere('username', $username);
            }
        });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['login' => 'That email/username is already in use.']);
        }
    }

    private function adminPermissions(): array
    {
        return [
            'manage_spots' => 'Add and manage tourist spots',
            'manage_reviews' => 'Manage tourist spot reviews',
            'view_reports' => 'View municipality reports',
            'manage_staff' => 'Create and manage municipality staff accounts',
        ];
    }

    private function normalizePermissions(array $permissions): array
    {
        $allowed = array_keys($this->adminPermissions());

        return collect($allowed)->mapWithKeys(fn ($permission) => [
            $permission => in_array($permission, $permissions, true),
        ])->all();
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
