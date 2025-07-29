<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Log the login attempt for debugging
        Log::info('API Login attempt', [
            'email' => $request->input('email'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            Log::warning('Login failed - User not found', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.'],
            ]);
        }
        
        // Check if user is active
        if ($user->status !== 'active') {
            Log::warning('Login failed - User not active', ['email' => $request->email, 'status' => $user->status]);
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact administrator.'],
            ]);
        }
        
        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            Log::warning('Login failed - Invalid password', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;
        
        Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email]);
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        // Use Laravel's password reset functionality
        // [Password reset implementation]
        
        return response()->json(['message' => 'Password reset link sent to your email']);
    }
}