<?php

namespace App\Http\Controllers;

use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MunicipalityController extends Controller
{
    public function index()
    {
        $municipalitiesQuery = Municipality::query();
        if (Schema::hasColumn('municipalities', 'is_active')) {
            $municipalitiesQuery->where('is_active', true);
        }
        $municipalities = $municipalitiesQuery->paginate(15);
        return view('municipalities.index', ['municipalities' => $municipalities]);
    }

    public function show(Municipality $municipality)
    {
        abort_unless(!Schema::hasColumn('municipalities', 'is_active') || $municipality->is_active || Auth::user()?->isSuperAdmin(), 404);
        $user = Auth::user();
        $spotsQuery = $municipality->touristSpots();

        if ($user && $user->isMunicipalityAdmin()) {
            abort_unless((int) $user->municipality_id === (int) $municipality->id, 403);
        } elseif (!$user || !$user->isSuperAdmin()) {
            $spotsQuery->where('verification_status', 'approved')
                ->whereIn('status', ['open', 'active']);
        }

        $spots = $spotsQuery->get();
        $spotCategories = $this->spotCategories();
        $groupedSpots = collect($spotCategories)->mapWithKeys(function ($label, $key) use ($spots) {
            return [
                $key => $spots->filter(function ($spot) use ($key) {
                    return ($spot->category ?? 'nature') === $key;
                })->values(),
            ];
        });

        return view('municipalities.show', compact('municipality', 'groupedSpots', 'spotCategories'));
    }

    public function create()
    {
        return redirect()->route('municipalities.index')
            ->with('info', 'Municipalities are fixed for the 2nd district and cannot be added.');
    }

    public function store(Request $request)
    {
        return redirect()->route('municipalities.index')
            ->with('info', 'Municipalities are fixed for the 2nd district and cannot be added.');
    }

    public function edit(Municipality $municipality)
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $municipality->load([
            'admins',
            'touristSpots' => fn ($query) => $query->select(['id', 'municipality_id', 'verification_status']),
        ]);

        return view('municipalities.edit', ['municipality' => $municipality]);
    }

    public function update(Request $request, Municipality $municipality)
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:municipalities,name,' . $municipality->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'is_active' => 'required|boolean',
        ]);

        $imageUrl = $this->storeImageAndGetUrl($request, 'image', 'municipality-images', $municipality->image_url);
        if ($imageUrl !== null) {
            $validated['image_url'] = $imageUrl;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteStoredImage($municipality->image_url);
            $validated['image_url'] = null;
        }
        unset($validated['image']);
        unset($validated['remove_image']);

        if (Schema::hasColumn('municipalities', 'is_active')) {
            $municipality->update($validated);
        } else {
            unset($validated['is_active']);
            $municipality->update($validated);
        }

        return redirect()
            ->route('municipalities.edit', $municipality)
            ->with('success', 'Municipality updated successfully!');
    }

    public function destroy(Municipality $municipality)
    {
        $municipality->delete();
        return redirect()->route('municipalities.index')->with('success', 'Municipality deleted successfully!');
    }

    private function storeImageAndGetUrl(Request $request, string $inputName, string $directory, ?string $existingUrl = null): ?string
    {
        if (!$request->hasFile($inputName)) {
            return null;
        }

        $this->deleteStoredImage($existingUrl);
        $path = $request->file($inputName)->store($directory, 'public');
        return Storage::url($path);
    }

    private function deleteStoredImage(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
        $prefix = '/storage/';
        if (!str_starts_with($path, $prefix)) {
            return;
        }

        $relative = ltrim(substr($path, strlen($prefix)), '/');
        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }

    private function spotCategories(): array
    {
        return [
            'beach' => 'Beach',
            'parks' => 'Parks',
            'falls' => 'Falls',
            'nature' => 'Nature',
            'resort' => 'Resort',
        ];
    }
}
