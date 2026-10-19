<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UploadAvatarRequest;
use App\Http\Resources\BoardResource;
use App\Http\Resources\PinResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's own profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount(['pins', 'boards']);

        return $this->success('Profile retrieved successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Display a public profile by username.
     */
    public function show(User $user): JsonResponse
    {
        $user->loadCount(['pins', 'boards']);

        return $this->success('Profile retrieved successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Display the pins uploaded by a user.
     */
    public function pins(Request $request, User $user): JsonResponse
    {
        $pins = $user->pins()
            ->with('user')
            ->withViewerData($request->user())
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('User pins retrieved successfully.', $pins, PinResource::class);
    }

    /**
     * Display the boards owned by a user.
     */
    public function boards(Request $request, User $user): JsonResponse
    {
        $isOwner = $request->user()?->id === $user->id;

        $boards = $user->boards()
            ->when(! $isOwner, fn (Builder $query) => $query->where('is_private', false))
            ->with('user')
            ->withCount('pins')
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('User boards retrieved successfully.', $boards, BoardResource::class);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->safe()->only(['name', 'username', 'bio', 'website']));

        $user->loadCount(['pins', 'boards']);

        return $this->success('Profile updated successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Replace the authenticated user's avatar.
     */
    public function updateAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar !== null) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $path]);
        $user->loadCount(['pins', 'boards']);

        return $this->success('Avatar updated successfully.', (new UserResource($user))->resolve());
    }
}
