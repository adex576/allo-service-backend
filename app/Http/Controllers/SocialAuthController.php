<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $role   = $request->query('role')   === 'prestataire' ? 'prestataire' : 'client';
        $action = $request->query('action') === 'register'    ? 'register'    : 'login';

        // Encode both intent and role into the OAuth state so they survive
        // the round-trip to Google without a session (stateless flow).
        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => "{$action}:{$role}"])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect("{$frontendUrl}/login?error=google_failed");
        }

        // Decode state: "{action}:{role}"
        $parts  = explode(':', (string) $request->query('state', 'login:client'), 2);
        $action = ($parts[0] ?? 'login') === 'register' ? 'register' : 'login';
        $role   = ($parts[1] ?? 'client') === 'prestataire' ? 'prestataire' : 'client';

        $existing = User::where('email', $googleUser->getEmail())->first();

        // Register flow: refuse to silently log in an existing account
        if ($action === 'register' && $existing) {
            return redirect("{$frontendUrl}/login?error=google_already_registered");
        }

        $user = $existing ?? User::create([
            'name'     => $googleUser->getName(),
            'email'    => $googleUser->getEmail(),
            'password' => Hash::make(Str::random(24)),
            'role'     => $role,
            'avatar'   => $googleUser->getAvatar(),
        ]);

        // Refresh avatar for returning users who never set one
        if (!$user->avatar && $googleUser->getAvatar()) {
            $user->update(['avatar' => $googleUser->getAvatar()]);
        }

        $token = $user->createToken('google_token')->plainTextToken;

        return redirect("{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'avatar' => $user->avatar,
            'phone'  => $user->phone,
        ])));
    }
}
