<?php

namespace App\Http\Controllers;

use App\Models\AdminTempCredential;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MunicipalityStaffController extends Controller
{
    private function currentAdmin(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->isMunicipalityAdmin() && $user->hasPermission('manage_staff'), 403, 'You do not have permission to manage staff accounts.');

        return $user;
    }

    public function index()
    {
        $admin = $this->currentAdmin();
        $staff = User::where('role', 'municipality-staff')
            ->where('municipality_id', $admin->municipality_id)
            ->with('municipality')
            ->orderBy('name')
            ->get();

        return view('dashboard.municipality-admin-staff', compact('staff'));
    }

    public function create()
    {
        $admin = $this->currentAdmin();
        $staffCount = $this->staffCount($admin);
        if ($this->hasReachedStaffLimit($admin, $staffCount)) {
            return redirect()->route('municipality-admin.staff')->with('error', 'The staff account limit has been reached. Ask the super admin to increase the limit.');
        }

        return view('dashboard.municipality-admin-staff-create', [
            'permissions' => $this->availablePermissions($admin),
            'staffCount' => $staffCount,
            'staffLimit' => $admin->max_staff_accounts,
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->currentAdmin();
        if ($this->hasReachedStaffLimit($admin)) {
            return back()->withInput()->with('error', 'The staff account limit has been reached. Ask the super admin to increase the limit.');
        }
        $hasUsernameColumn = Schema::hasColumn('users', 'username');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ];
        $validated = $request->validate($rules);
        $login = $this->normalizeLogin($validated['login']);
        $email = $this->resolveEmail($login);
        $this->ensureLoginIsAvailable($login, $email, null, $hasUsernameColumn);
        $staffData = [
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => 'municipality-staff',
            'municipality_id' => $admin->municipality_id,
            'is_active' => $request->boolean('is_active'),
            'permissions' => $this->normalizePermissions($admin, $request->input('permissions', [])),
        ];
        if ($hasUsernameColumn) {
            $staffData['username'] = $login;
        }
        $staff = DB::transaction(function () use ($staffData, $validated) {
            $staff = User::create($staffData);

            AdminTempCredential::updateOrCreate(
                ['user_id' => $staff->id],
                ['password' => encrypt($validated['password'])]
            );

            return $staff;
        });

        return redirect()->route('municipality-admin.staff')->with('success', "Staff account created successfully. Login: {$login}");
    }

    public function edit(User $staff)
    {
        $admin = $this->currentAdmin();
        $this->ensureStaffBelongsToAdmin($staff, $admin);

        return view('dashboard.municipality-admin-staff-edit', [
            'staff' => $staff,
            'permissions' => $this->availablePermissions($admin),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $admin = $this->currentAdmin();
        $this->ensureStaffBelongsToAdmin($staff, $admin);
        $hasUsernameColumn = Schema::hasColumn('users', 'username');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'login' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ];
        $validated = $request->validate($rules);
        $login = $this->normalizeLogin($validated['login']);
        $email = $this->resolveEmail($login);
        $this->ensureLoginIsAvailable($login, $email, $staff->id, $hasUsernameColumn);
        $staff->name = $validated['name'];
        if ($hasUsernameColumn) {
            $staff->username = $login;
        }
        $staff->email = $email;
        $staff->is_active = $request->boolean('is_active');
        $staff->permissions = $this->normalizePermissions($admin, $request->input('permissions', []));
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
            AdminTempCredential::updateOrCreate(
                ['user_id' => $staff->id],
                ['password' => encrypt($validated['password'])]
            );
        }
        $staff->save();

        return redirect()->route('municipality-admin.staff')->with('success', 'Staff account updated successfully.');
    }

    public function toggleStatus(User $staff)
    {
        $admin = $this->currentAdmin();
        $this->ensureStaffBelongsToAdmin($staff, $admin);
        $staff->update(['is_active' => !$staff->is_active]);

        return back()->with('success', 'Staff account status updated.');
    }

    private function ensureStaffBelongsToAdmin(User $staff, User $admin): void
    {
        abort_unless($staff->isMunicipalityStaff() && (int) $staff->municipality_id === (int) $admin->municipality_id, 404);
    }

    private function availablePermissions(User $admin): array
    {
        $all = [
            'manage_spots' => 'Add and manage tourist spots',
            'manage_reviews' => 'Manage tourist spot reviews',
            'view_reports' => 'View municipality reports',
        ];

        return collect($all)->filter(fn ($label, $permission) => $admin->hasPermission($permission))->all();
    }

    private function normalizePermissions(User $admin, array $permissions): array
    {
        return collect(array_keys($this->availablePermissions($admin)))->mapWithKeys(fn ($permission) => [
            $permission => in_array($permission, $permissions, true),
        ])->all();
    }

    private function normalizeLogin(string $login): string
    {
        $login = strtolower(trim($login));
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? $login : str_replace('-', '_', preg_replace('/[^a-z0-9_]+/i', '_', $login));
    }

    private function resolveEmail(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? $login : $login . '@tourist-spots.com';
    }

    private function ensureLoginIsAvailable(string $username, string $email, ?int $ignoreId = null, bool $hasUsernameColumn = false): void
    {
        $query = User::where(function ($builder) use ($username, $email, $hasUsernameColumn) {
            $builder->where('email', $email);
            if ($hasUsernameColumn) {
                $builder->orWhere('username', $username);
            }
        });
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        abort_unless(!$query->exists(), 422, 'That email/username is already in use.');
    }

    private function staffCount(User $admin): int
    {
        return User::where('role', 'municipality-staff')
            ->where('municipality_id', $admin->municipality_id)
            ->count();
    }

    private function hasReachedStaffLimit(User $admin, ?int $staffCount = null): bool
    {
        if ($admin->max_staff_accounts === null) {
            return false;
        }

        return ($staffCount ?? $this->staffCount($admin)) >= $admin->max_staff_accounts;
    }
}
