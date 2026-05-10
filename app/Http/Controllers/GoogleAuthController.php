<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $nameParts = explode(' ', $googleUser->getName(), 2);
            $firstName = $nameParts[0] ?? 'Unknown';
            $lastName = $nameParts[1] ?? 'Unknown';

            // Define all required fields with placeholder defaults to bypass SQL strict mode constraints.
            // You can easily add more fields here if the database throws "Field doesn't have a default value" errors.
            $defaultAttributes = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'google_id'  => $googleUser->getId(),
                'phone'      => '0000000000',
                'birthDate'  => '2000-01-01',
                'password'   => bcrypt(Str::random(16)),
                'weight'     => 70.0,
                'height'     => 170.0,
            ];

            // TODO: Redirect the user to a 'Complete Profile' page after their first Google login to fill in their real phone number.
            // Note: Since we use updateOrCreate directly on the Model, FormRequest validation is fully bypassed.
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                $defaultAttributes
            );

            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
