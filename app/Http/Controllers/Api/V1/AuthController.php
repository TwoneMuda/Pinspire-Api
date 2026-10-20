<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Register a new user and issue an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only(['name', 'username', 'email', 'password']));

        return $this->success(
            'Registration successful.',
            $this->tokenPayload($user, $request->input('device_name')),
            status: 201,
        );
    }

    /**
     * Authenticate an existing user and issue an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return $this->error('The provided credentials are incorrect.', 401);
        }

        return $this->success(
            'Login successful.',
            $this->tokenPayload($user, $request->input('device_name')),
        );
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success('Logged out successfully.');
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($request->user()->id);

        return $this->success('Authenticated user retrieved successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Build the authentication payload returned after register or login.
     *
     * @return array<string, mixed>
     */
    private function tokenPayload(User $user, ?string $deviceName): array
    {
        $user->loadCount(['pins', 'boards', 'followers', 'following']);

        return [
            'user' => (new UserResource($user, includeEmail: true))->resolve(),
            'access_token' => $user->createToken($deviceName ?: 'api')->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
