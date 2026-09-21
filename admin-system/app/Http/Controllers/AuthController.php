<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|min:6',
        ]);

        // Debug: Log the attempt
        \Log::info('Login attempt', [
            'login' => $validated['login'],
            'timestamp' => now()
        ]);

        $loginValue = Str::lower(trim((string) $validated['login']));
        $userQuery = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($loginValue) {
                if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
                    $query->where('email', $loginValue);
                    return;
                }

                $query->where('email', $loginValue . '@tourist-spots.com');

                if (Schema::hasColumn('users', 'username')) {
                    $query->orWhere('username', $loginValue);
                }
            });

        $user = $userQuery->first();

        if ($user && Hash::check($validated['password'], $user->password)) {
            Auth::login($user);
            \Log::info('Login successful', [
                'login' => $loginValue,
                'user_id' => Auth::id(),
                'role' => Auth::user()->role,
                'timestamp' => now()
            ]);
            $request->session()->regenerate();
            
            // Redirect based on user role
            if (Auth::user()->isSuperAdmin()) {
                return redirect()->route('super-admin.dashboard');
            } else if (Auth::user()->isMunicipalityAdmin()) {
                return redirect()->route('municipality-admin.dashboard');
            }
            
            return redirect()->route('dashboard');
        }

        \Log::warning('Login failed', [
            'login' => $loginValue,
            'timestamp' => now()
        ]);

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function storeRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $this->generateUniqueUsername($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_active' => true,
        ]);

        return redirect()->route('login.form')->with('success', 'Registration successful! Please log in.');
    }

    private function generateUniqueUsername(string $email): string
    {
        $baseUsername = Str::slug(explode('@', $email)[0], '_');
        $baseUsername = $baseUsername !== '' ? $baseUsername : 'user';

        $username = $baseUsername;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }
}
