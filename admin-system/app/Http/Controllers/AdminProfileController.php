<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $hasProfileImageColumn = Schema::hasColumn('users', 'profile_image_path');
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        $settingsData = [
            'user' => $user->loadMissing('municipality'),
            'hasProfileImageColumn' => $hasProfileImageColumn,
            'hasUsernameColumn' => $hasUsernameColumn,
        ];

        if ($user->isSuperAdmin() && request()->query('section') === 'admins') {
            $settingsData['managedAdmins'] = User::where('role', 'municipality-admin')
                ->with('municipality')
                ->orderBy('name')
                ->get();
            $settingsData['municipalities'] = Municipality::orderBy('name')->get();
        }

        return view('profile.edit', $settingsData);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $section = $request->input('section', 'profile');
        $hasProfileImageColumn = Schema::hasColumn('users', 'profile_image_path');
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($section === 'account') {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['current_password'] = ['required', 'current_password'];
        }

        if ($hasUsernameColumn) {
            $rules['username'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];

        if ($section === 'account' && $request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('profile_image')) {
            if (!$hasProfileImageColumn) {
                return redirect()->route('profile.edit')->withErrors([
                    'profile_image' => 'The database is missing the users.profile_image_path column. Run the profile image migration first.',
                ]);
            }

            $this->replaceProfileImage($request, $user);
        }

        $user->save();

        return redirect()->route('profile.edit', ['section' => $section])->with('success', $section === 'account'
            ? 'Password changed successfully.'
            : 'Profile updated successfully.');
    }

    private function replaceProfileImage(Request $request, User $user): void
    {
        $newPath = $request->file('profile_image')->store('admin-profile-images', 'public');

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $user->profile_image_path = $newPath;
    }
}
