<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

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
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => 'required|string|email|max:50|unique:users',
            'password' => 'required|string|min:8',          
            'gender' => 'nullable|in:Male,Female',
            'phone' => 'nullable|string|max:11',
            'birthDate' => 'nullable|date',
            'diabetes_type' => ['nullable', Rule::in(User::DIABETES_TYPES)],
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


        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profiles', 'public');
        }

        $user = User::create([
            'id' => $validated['id'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'profile_picture' => $profilePicturePath,
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
            'diabetes_type' => ['nullable', Rule::in(User::DIABETES_TYPES)],
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
            'exists' => true
        ], 200); 
    }

    return response()->json([
        'exists' => false
    ], 200);
}
    /**
     * Return the currently authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'message' => 'User retrieved successfully',
            'user' => $request->user(),
        ], 200);
    }

    /**
     * Delete the authenticated user's account and all related data
     */
    public function deleteUser(Request $request)
    {
        $user = $request->user();

        // Delete profile picture if it exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Delete all user's posts and associated images
        foreach ($user->posts as $post) {
            foreach ($post->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }
            $post->delete();
        }

        // Delete all user's comments
        $user->comments()->delete();

        // Delete all user's likes
        $user->likes()->delete();

        // Delete all conversations where user is involved and their messages
        Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->each(function ($conversation) {
                $conversation->messages()->delete();
                $conversation->delete();
            });

        // Delete all messages sent by this user
        ChatMessage::where('sender_id', $user->id)->delete();

        // Delete all friendships (both sent and received requests)
        $user->sentFriendRequests()->delete();
        $user->receivedFriendRequests()->delete();

        // Delete all blocks (both blocked by user and blocking user)
        $user->blockedUsers()->delete();
        $user->blockers()->delete();

        // Delete all health tracking logs (Glucose, Meals, Medications cascade through Log)
        $user->logs()->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete the user
        $user->delete();

        return response()->json([
            'message' => 'User account deleted successfully',
        ], 200);
    }
}  