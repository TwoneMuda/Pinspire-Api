<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Settings\UpdateSettingsRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SettingsController extends Controller
{
    /**
     * Display the authenticated user's account settings.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success('Settings retrieved successfully.', $this->settingsPayload($request));
    }

    /**
     * Update the authenticated user's account settings.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $attributes = $request->safe()->only(['email']);

        if ($request->has('preferences')) {
            $attributes['preferences'] = array_merge(
                $user->preferencesWithDefaults(),
                $request->validated('preferences', []),
            );
        }

        if ($attributes !== []) {
            $user->update($attributes);
        }

        return $this->success('Settings updated successfully.', $this->settingsPayload($request));
    }

    /**
     * Change the authenticated user's password and revoke other sessions.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $user->update(['password' => $request->string('password')->toString()]);

        if ($currentToken instanceof PersonalAccessToken) {
            $user->tokens()->whereKeyNot($currentToken->getKey())->delete();
        } else {
            $user->tokens()->delete();
        }

        return $this->success('Password updated successfully. Other sessions have been signed out.');
    }

    /**
     * Build the settings payload for the authenticated user.
     *
     * @return array<string, mixed>
     */
    private function settingsPayload(Request $request): array
    {
        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($request->user()->id);

        return [
            'user' => (new UserResource($user))->resolve(),
            'email' => $user->email,
            'preferences' => $user->preferencesWithDefaults(),
        ];
    }
}
