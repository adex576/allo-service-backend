<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // send a reset link (logged via the mail driver in dev)
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        // generic response so we don't leak which emails are registered
        return response()->json([
            'message' => 'Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.',
            'status'  => $status,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $user->tokens()->delete(); // revoke existing API tokens
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé. Vous pouvez vous connecter.']);
        }

        return response()->json([
            'message' => $status === Password::INVALID_TOKEN
                ? 'Lien invalide ou expiré.'
                : 'Impossible de réinitialiser le mot de passe.',
        ], 422);
    }
}
