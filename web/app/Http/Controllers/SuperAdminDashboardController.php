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

            $pendingSpotsQuery = TouristSpot::with(['municipality', 'creator']);
            if (Schema::hasTable('tourist_spot_verification_events')) {
                $pendingSpotsQuery->with('approvalEvent');
            }
            if ($hasVerificationStatus) {
                $pendingSpotsQuery->where(function ($query) {
                    $query->where('verification_status', 'pending')
                        ->orWhere(function ($legacyQuery) {
                            $legacyQuery->whereNull('verification_status')
                                ->whereIn('status', ['pending', 'inactive']);
                        });
                });
            } else {
                $pendingSpotsQuery->whereIn('status', ['pending', 'inactive']);
            }
            $pendingTouristSpots = $pendingSpotsQuery->latest('created_at')->get();
            $categoryTotals = collect();
            if (Schema::hasColumn('tourist_spots', 'category')) {
                $categoryTotals = TouristSpot::query()
                    ->selectRaw('category, COUNT(*) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category');
            }

            $dashboardData = [
                'totalSpots' => TouristSpot::count(),
                'totalMunicipalities' => Municipality::count(),
                'totalReviews' => Review::count(),
                'totalAdmins' => User::where('role', 'municipality-admin')->count(),
                'totalStaff' => User::where('role', 'municipality-staff')->count(),
                'pendingReviews' => Review::where('status', 'pending')->count(),
                'pendingVerificationSpots' => $pendingTouristSpots->count(),
                'pendingTouristSpots' => $pendingTouristSpots,
                'spotCategoryCounts' => collect([
                    ['label' => 'Beach', 'count' => (int) $categoryTotals->get('beach', 0)],
                    ['label' => 'Parks', 'count' => (int) $categoryTotals->get('parks', 0)],
                    ['label' => 'Falls', 'count' => (int) $categoryTotals->get('falls', 0)],
                    ['label' => 'Nature', 'count' => (int) $categoryTotals->get('nature', 0)],
                ]),
                'municipalities' => $municipalityQuery->orderBy('name')->get(),
            ];
        } catch (\Exception $e) {
            Log::error('Super admin dashboard error: ' . $e->getMessage());

            $dashboardData = [
                'totalSpots' => 0,
                'totalMunicipalities' => 0,
                'totalReviews' => 0,
                'totalAdmins' => 0,
                'totalStaff' => 0,
                'pendingReviews' => 0,
                'pendingVerificationSpots' => 0,
                'pendingTouristSpots' => collect(),
                'spotCategoryCounts' => collect([
                    ['label' => 'Beach', 'count' => 0],
                    ['label' => 'Parks', 'count' => 0],
                    ['label' => 'Falls', 'count' => 0],
                    ['label' => 'Nature', 'count' => 0],
                ]),
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
            $touristSpotsQuery = TouristSpot::with(['municipality', 'creator'])
                ->withCount('reviews');
            if (Schema::hasTable('tourist_spot_verification_events')) {
                $touristSpotsQuery->with('approvalEvent');
            }
            $touristSpots = $touristSpotsQuery->orderByDesc('created_at')->get();
            $pendingTouristSpots = $touristSpots->filter(function ($spot) use ($hasVerificationStatus) {
                if ($hasVerificationStatus && $spot->verification_status !== null) {
                    return $spot->verification_status === 'pending';
                }

                return in_array($spot->status, ['pending', 'inactive'], true);
            })->values();

            return view('dashboard.super-admin-tourist-spots', [
                'touristSpots' => $touristSpots,
                'pendingTouristSpots' => $pendingTouristSpots,
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
            $admins = User::where('role', 'municipality-admin')
                ->with(['municipality' => fn ($query) => $query->withCount(['touristSpots', 'staffAccounts'])])
                ->orderBy('name')
                ->get();

            $municipalityCount = Municipality::count();
            $municipalitiesWithAdmins = User::where('role', 'municipality-admin')
                ->whereNotNull('municipality_id')
                ->distinct('municipality_id')
                ->count('municipality_id');

            return view('dashboard.super-admin-admins', [
                'admins' => $admins,
                'municipalityCount' => $municipalityCount,
                'municipalitiesWithAdmins' => $municipalitiesWithAdmins,
                'availableMunicipalities' => Municipality::whereDoesntHave('admins')->orderBy('name')->get(),
                'selectedMunicipalityId' => (int) $request->query('municipality_id', 0),
            ]);
        } catch (\Exception $e) {
            Log::error('Super admin admins view error: ' . $e->getMessage());
            return redirect()->route('super-admin.dashboard')->with('error', 'Error loading admins. Please try again.');
        }
    }

    public function staffAccounts()
    {
        $staff = User::where('role', 'municipality-staff')
            ->with('municipality')
            ->orderBy('municipality_id')
            ->orderBy('name')
            ->get();

        return view('dashboard.super-admin-staff', compact('staff'));
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
            'max_staff_accounts' => ['required', 'integer', 'min:0', 'max:10000'],
        ];

        $validated = $request->validate($rules);
        $temporaryPassword = Str::random(32) . 'A!';
        $login = $this->normalizeAdminUsername($validated['login']);
        $email = $this->resolveAdminEmail($login);
        $this->ensureLoginIsAvailable($login, $email);
        $admin = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'username' => $hasUsernameColumn ? $login : null,
            'password' => Hash::make($temporaryPassword),
            'role' => 'municipality-admin',
            'municipality_id' => $validated['municipality_id'],
            'is_active' => true,
            'permissions' => $this->normalizePermissions(array_keys($this->adminPermissions())),
            'max_staff_accounts' => $validated['max_staff_accounts'],
        ]);

        AdminTempCredential::updateOrCreate(
            ['user_id' => $admin->id],
            ['password' => encrypt($temporaryPassword)]
        );

        return redirect()->route('super-admin.admins')
            ->with('success', 'Municipality admin created successfully. Share the temporary password securely.')
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_admin_name', $admin->name)
            ->with('temporary_admin_email', $admin->email);
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
