<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Login or register via Google Sign-In ID token.
     */
    public function googleLogin(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            // Verify token with Google
            $response = Http::timeout(10)->get(
                'https://oauth2.googleapis.com/tokeninfo',
                ['id_token' => $validated['id_token']]
            );

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google ID token',
                ], 401);
            }

            $payload = $response->json();

            $expectedClientIds = collect(explode(',', (string) env('GOOGLE_CLIENT_IDS', env('GOOGLE_CLIENT_ID', ''))))
                ->map(fn (string $clientId) => trim($clientId))
                ->filter()
                ->values();

            if ($expectedClientIds->isNotEmpty() && !$expectedClientIds->contains($payload['aud'] ?? null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token audience mismatch',
                ], 401);
            }

            $providerId = $payload['sub'] ?? null;
            $name = $payload['name'] ?? 'Google User';
            $email = $payload['email'] ?? null;
            $picture = $payload['picture'] ?? null;

            if (empty($providerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract user from Google token',
                ], 400);
            }

            return $this->findOrCreateUserAndRespond([
                'provider' => 'google',
                'provider_id' => $providerId,
                'name' => $name,
                'email' => $email,
                'profile_image_url' => $picture,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google auth error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login or register via Facebook access token.
     */
    public function facebookLogin(Request $request)
    {
        $validated = $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Verify token with Facebook Graph API
            $response = Http::timeout(10)->get(
                'https://graph.facebook.com/me',
                [
                    'access_token' => $validated['access_token'],
                    'fields' => 'id,name,email,picture.type(large)',
                ]
            );

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Facebook access token',
                ], 401);
            }

            $payload = $response->json();

            $providerId = $payload['id'] ?? null;
            $name = $payload['name'] ?? 'Facebook User';
            $email = $payload['email'] ?? null;
            $picture = $payload['picture']['data']['url'] ?? null;

            if (empty($providerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract user from Facebook token',
                ], 400);
            }

            return $this->findOrCreateUserAndRespond([
                'provider' => 'facebook',
                'provider_id' => $providerId,
                'name' => $name,
                'email' => $email,
                'profile_image_url' => $picture,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Facebook auth error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find existing user by provider+provider_id or email, or create new user.
     * Generate API token and return user + token.
     */
    private function findOrCreateUserAndRespond(array $data)
    {
        $user = User::where('auth_provider', $data['provider'])
            ->where('provider_id', $data['provider_id'])
            ->first();

        // Fallback: match by email if no provider match found
        if (!$user && !empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        }

        if (!$user) {
            // Create new user
            $username = $this->generateUniqueUsername($data['name']);
            $email = $data['email'] ?? $data['provider_id'] . '@' . $data['provider'] . '.local';

            $userData = [
                'name' => $data['name'],
                'email' => $email,
                'password' => bcrypt(Str::random(32)), // random password, never used for social auth
                'role' => 'user',
                'is_active' => true,
                'auth_provider' => $data['provider'],
                'provider_id' => $data['provider_id'],
                'profile_image_path' => $data['profile_image_url'] ?? null,
            ];

            if (Schema::hasColumn('users', 'username')) {
                $userData['username'] = $username;
            }

            $user = User::create($userData);
        } else {
            // Update profile info on re-login
            $user->update([
                'name' => $data['name'],
                'profile_image_path' => $data['profile_image_url'] ?? $user->profile_image_path,
                'auth_provider' => $data['provider'],
                'provider_id' => $data['provider_id'],
            ]);
        }

        // Generate a fresh API token
        $token = Str::random(60);
        $user->update(['api_token' => $token]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image_url' => $user->profile_image_url,
                    'provider' => $user->auth_provider,
                ],
                'token' => $token,
            ],
        ]);
    }

    private function generateUniqueUsername(string $name): string
    {
        if (!Schema::hasColumn('users', 'username')) {
            return Str::slug($name, '_') ?: 'user';
        }

        $base = Str::slug($name, '_');
        $base = $base !== '' ? $base : 'user';
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }
}

