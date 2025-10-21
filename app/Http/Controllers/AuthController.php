<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'], // Simplified password validation for development
                'birth_date' => ['nullable', 'date', 'before:today'],
                'education_level' => ['nullable', 'in:sd,smp,sma,kuliah'],
                'institution' => ['nullable', 'string', 'max:255'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'birth_date' => $validated['birth_date'] ?? null,
            'education_level' => $validated['education_level'] ?? null,
            'institution' => $validated['institution'] ?? null,
        ]);

        try {
            $token = $user->createToken('api-token')->plainTextToken;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create authentication token.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'education_level' => $user->education_level,
                'education_display' => $user->education_display,
                'institution' => $user->institution,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if (!Auth::attempt($validated)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found after authentication.',
            ], 500);
        }
        
        try {
            $token = $user->createToken('api-token')->plainTextToken;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create authentication token.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'education_level' => $user->education_level,
                'education_display' => $user->education_display,
                'institution' => $user->institution,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'age' => $user->age,
                'education_level' => $user->education_level,
                'education_display' => $user->education_display,
                'institution' => $user->institution,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'birth_date' => ['nullable', 'date', 'before:today'],
                'education_level' => ['nullable', 'in:sd,smp,sma,kuliah'],
                'institution' => ['nullable', 'string', 'max:255'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
            ], 401);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'age' => $user->age,
                'education_level' => $user->education_level,
                'education_display' => $user->education_display,
                'institution' => $user->institution,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
