<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and return a token
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'id' => 'sometimes|integer|unique:users',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|string|email|max:50|unique:users',
            'password' => 'required|string|min:8',          
            'gender' => 'nullable|in:Male,Female',
            'phone' => 'nullable|string|max:11',
            'birthDate' => 'nullable|date',
            'diabetes_type' => 'nullable|in:Type1,Type2,LADA,MODY,Gestational,diabetes,other',
            'insulin_therapy' => 'nullable|in:Pen / Syringes,pump,No insulin',
            'diagnose_date' => 'nullable|date_format:Y-m-d H:i:s',
            'glucose' => 'nullable|in:mg/dl,mmol/L',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'max_glucose' => 'nullable|numeric|min:0',
            'target_glucose_range' => 'nullable|numeric|min:0',
            'min_glucose' => 'nullable|numeric|min:0',
            'emergency_contact' => 'nullable|string|max:11',
        ]);


        $user = User::create([
            'id' => $validated['id'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'gender' => $validated['gender'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'birthDate' => $validated['birthDate'] ?? null,
            'diabetes_type' => $validated['diabetes_type'] ?? null,
            'insulin_therapy' => $validated['insulin_therapy'] ?? null,
            'diagnose_date' => $validated['diagnose_date'] ?? null,
            'glucose' => $validated['glucose'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'height' => $validated['height'] ?? null,
            'max_glucose' => $validated['max_glucose'] ?? null,
            'target_glucose_range' => $validated['target_glucose_range'] ?? null,
            'min_glucose' => $validated['min_glucose'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login an existing user and return a token
     */
    public function login(Request $request)
    {
        $validated = $request->validate([

            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Logout the authenticated user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }

    /**
     * Update authenticated user's profile with medical details
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'id' => 'sometimes|integer|unique:users,id,' . $user->id,
            'first_name' => 'sometimes|required|string|max:50',
            'last_name' => 'sometimes|required|string|max:50',
            'email' => 'sometimes|required|string|email|max:50|unique:users,email,' . $user->id,
            'gender' => 'nullable|in:Male,Female',
            'phone' => 'nullable|string|max:11',
            'birthDate' => 'nullable|date',
            'diabetes_type' => 'nullable|in:Type1,Type2,LADA,MODY,Gestational,diabetes,other',
            'insulin_therapy' => 'nullable|in:Pen / Syringes,pump,No insulin',
            'diagnose_date' => 'nullable|date_format:Y-m-d H:i:s',
            'glucose' => 'nullable|in:mg/dl,mmol/L',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'max_glucose' => 'nullable|numeric|min:0',
            'target_glucose_range' => 'nullable|numeric|min:0',
            'min_glucose' => 'nullable|numeric|min:0',
            'emergency_contact' => 'nullable|string|max:11',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }


    /**
 * Check if the email is already registered.
 */
public function checkEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email|max:50',
    ]);

    $exists = User::where('email', $request->email)->exists();

    if ($exists) {
        return response()->json([
            'exists' => true,
            'message' => 'هذا البريد الإلكتروني مسجل بالفعل.'
        ], 200); 
    }

    return response()->json([
        'exists' => false,
        'message' => 'البريد الإلكتروني غير مسجل .'
    ], 200);
}
}