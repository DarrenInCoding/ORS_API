<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\SocialAccount;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new customer account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => 'customer',
            'status' => 'active',
        ]);

        $deviceName = $request->header('User-Agent', 'unknown-device');
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->created([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registration successful');
    }

    /**
     * Login with email and password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        if (!$user->isActive()) {
            return $this->error('Your account has been suspended', 403);
        }

        $deviceName = $request->device_name ?? $request->header('User-Agent', 'unknown-device');
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'user' => new UserResource($user->load('socialAccounts')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Logout current device.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(message: 'Logged out successfully');
    }

    /**
     * Logout all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(message: 'Logged out from all devices');
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['socialAccounts', 'managedBranch', 'assignedBranches']);

        return $this->success(new UserResource($user));
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 422);
        }

        $user->update(['password' => $request->password]);

        return $this->success(message: 'Password changed successfully');
    }

    // ── Social Login ───────────────────────────────────────

    /**
     * Redirect to social provider (for web-based flow).
     */
    public function redirectToProvider(string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return $this->success(['redirect_url' => $url]);
    }

    /**
     * Handle callback from social provider (web-based flow).
     */
    public function handleProviderCallback(string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return $this->error('Social authentication failed: ' . $e->getMessage(), 401);
        }

        return $this->handleSocialUser($provider, $socialUser);
    }

    /**
     * Handle social login with access token (for mobile apps).
     * Mobile apps get the token from their native SDK and send it here.
     */
    public function socialLoginWithToken(string $provider, SocialLoginRequest $request): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->access_token);
        } catch (\Exception $e) {
            return $this->error('Invalid social token: ' . $e->getMessage(), 401);
        }

        return $this->handleSocialUser($provider, $socialUser, $request->device_name);
    }

    /**
     * Process social user - find or create.
     */
    protected function handleSocialUser(string $provider, $socialUser, ?string $deviceName = null): JsonResponse
    {
        // Check if social account already linked
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user;

            // Update token
            $socialAccount->update([
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
            ]);
        } else {
            // Check if user with same email exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'role' => 'customer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
            }

            // Link social account
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
                'provider_data' => [
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                ],
            ]);
        }

        if (!$user->isActive()) {
            return $this->error('Your account has been suspended', 403);
        }

        $deviceName = $deviceName ?? 'social-login-' . $provider;
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'user' => new UserResource($user->load('socialAccounts')),
            'token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => $user->wasRecentlyCreated,
        ], 'Social login successful');
    }

    /**
     * Validate the social provider.
     */
    protected function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'microsoft', 'apple'])) {
            abort(422, 'Invalid social provider. Supported: google, microsoft, apple');
        }
    }
}
