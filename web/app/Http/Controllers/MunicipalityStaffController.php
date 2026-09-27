<?php

namespace App\Http\Controllers;

use App\Models\AdminTempCredential;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $temporaryCredentialIds = AdminTempCredential::whereIn('user_id', $staff->modelKeys())
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);

        return view('dashboard.municipality-admin-staff', [
            'staff' => $staff,
            'temporaryCredentialIds' => $temporaryCredentialIds,
            'permissions' => $this->availablePermissions($admin),
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
            'permissions' => ['nullable', 'array'],
        ];
        $validated = $request->validate($rules);
        $temporaryPassword = Str::random(32) . 'A!';
        $login = $this->normalizeLogin($validated['login']);
        $email = $this->resolveEmail($login);
        $this->ensureLoginIsAvailable($login, $email, null, $hasUsernameColumn);
        $staffData = [
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
            'role' => 'municipality-staff',
            'municipality_id' => $admin->municipality_id,
            'is_active' => true,
            'permissions' => $this->normalizePermissions($admin, $request->input('permissions', [])),
        ];
        if ($hasUsernameColumn) {
            $staffData['username'] = $login;
        }
        $staff = DB::transaction(function () use ($staffData, $temporaryPassword) {
            $staff = User::create($staffData);

            AdminTempCredential::updateOrCreate(
                ['user_id' => $staff->id],
                ['password' => encrypt($temporaryPassword)]
            );

            return $staff;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Staff account created successfully.',
                'name' => $staff->name,
                'login' => $login,
                'password' => $temporaryPassword,
            ], 201);
        }

        return redirect()->route('municipality-admin.staff')
            ->with('success', 'Staff account created. Share the temporary login details securely; staff must change the password after signing in.')
            ->with('temporary_staff_name', $staff->name)
            ->with('temporary_staff_login', $login)
            ->with('temporary_staff_password', $temporaryPassword);
    }

    public function destroy(User $staff)
    {
        $admin = $this->currentAdmin();
        $this->ensureStaffBelongsToAdmin($staff, $admin);

        DB::transaction(function () use ($staff) {
            AdminTempCredential::where('user_id', $staff->id)->delete();
            $staff->delete();
        });

        return redirect()->route('municipality-admin.staff')->with('success', 'Staff account deleted successfully.');
    }

    public function credentials(User $staff)
    {
        $admin = $this->currentAdmin();
        $this->ensureStaffBelongsToAdmin($staff, $admin);

        $credential = AdminTempCredential::where('user_id', $staff->id)->first();
        abort_unless($credential, 404, 'Temporary credentials are no longer available.');

        return response()->json([
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => decrypt($credential->password),
        ]);
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
