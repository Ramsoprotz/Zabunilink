<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\SystemMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected SystemMessageService $messageService,
    ) {}
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'business_name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'business_name' => $validated['business_name'],
            'password' => $validated['password'],
        ]);

        // Create default notification preferences
        NotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'category_ids' => [],
            'location_ids' => [],
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Send welcome message (email + SMS)
        $this->messageService->sendWelcome($user);

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user->load('activeSubscription.plan'),
            'token' => $token,
        ], 201);
    }

    /**
     * Login a user.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load('activeSubscription.plan'),
            'token' => $token,
        ]);
    }

    /**
     * Logout the current user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('activeSubscription.plan');

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'business_name' => 'sometimes|string|max:255',
            'fcm_token' => 'sometimes|nullable|string|max:500',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'data' => $request->user()->fresh(),
        ]);
    }

    /**
     * Request a password reset OTP.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            // Don't reveal whether email exists
            return response()->json([
                'message' => 'If an account with that email exists, a reset code has been sent.',
            ]);
        }

        // Rate-limit: max 3 OTPs per identifier in 15 minutes
        $recentCount = DB::table('password_reset_otps')
            ->where('identifier', $user->email)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentCount >= 3) {
            return response()->json([
                'message' => 'Too many reset requests. Please wait 15 minutes before trying again.',
            ], 429);
        }

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_otps')->insert([
            'identifier' => $user->email,
            'otp'        => Hash::make($otp),
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send OTP via email + SMS
        $this->messageService->sendPasswordResetOtp($user, $otp);

        return response()->json([
            'message' => 'If an account with that email exists, a reset code has been sent.',
        ]);
    }

    /**
     * Reset password using OTP.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|string|email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid reset code.'], 422);
        }

        // Find valid OTP records for this identifier
        $otpRecords = DB::table('password_reset_otps')
            ->where('identifier', $user->email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        $validOtp = null;
        foreach ($otpRecords as $record) {
            if (Hash::check($request->otp, $record->otp)) {
                $validOtp = $record;
                break;
            }
        }

        if (! $validOtp) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 422);
        }

        // Mark OTP as used
        DB::table('password_reset_otps')
            ->where('id', $validOtp->id)
            ->update(['used' => true, 'updated_at' => now()]);

        // Update password
        $user->update([
            'password' => $request->password,
        ]);

        // Revoke all existing tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. Please log in with your new password.',
        ]);
    }
}
