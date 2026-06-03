<?php
namespace App\Http\Controllers;

use App\Models\PrestataireProfile;
use Illuminate\Http\Request;

class PrestataireController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius'    => 'nullable|numeric|min:1|max:500',
        ]);

        $hasLocation = $request->filled('latitude') && $request->filled('longitude');
        $lat    = (float) $request->latitude;
        $lng    = (float) $request->longitude;
        $radius = (float) ($request->radius ?? 20);

        $query = PrestataireProfile::with(['category'])
            ->join('users', 'users.id', '=', 'prestataire_profiles.user_id');

        if ($hasLocation) {
            $query->selectRaw("prestataire_profiles.*, users.name, users.avatar, users.latitude, users.longitude,
                ( 6371 * acos(
                    cos(radians(?)) * cos(radians(users.latitude))
                    * cos(radians(users.longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(users.latitude))
                )) AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('users.latitude')
                ->whereNotNull('users.longitude')
                ->havingRaw('distance_km <= ?', [$radius])
                ->orderBy('distance_km');
        } else {
            $query->select('prestataire_profiles.*', 'users.name', 'users.avatar', 'users.latitude', 'users.longitude');
        }

        if ($request->category_id) {
            $query->where('prestataire_profiles.category_id', $request->category_id);
        }

        if ($request->has('availability')) {
            $query->where('prestataire_profiles.availability', $request->availability);
        }

        return response()->json($query->paginate(15));
    }

    public function updateProfile(Request $request)
    {
        if (!$this->currentUser()->isPrestataire()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'bio'          => 'nullable|string|max:1000',
            'availability' => 'boolean',
        ]);

        $profile = PrestataireProfile::updateOrCreate(
            ['user_id' => $this->currentUser()->id],
            [
                'category_id'  => $request->category_id,
                'bio'          => $request->bio,
                'availability' => $request->availability ?? true,
            ]
        );

        return response()->json($profile);
    }

    public function show(int $id)
    {
        $profile = PrestataireProfile::with(['user', 'category'])
            ->where('user_id', $id)
            ->firstOrFail();

        return response()->json($profile);
    }
}
