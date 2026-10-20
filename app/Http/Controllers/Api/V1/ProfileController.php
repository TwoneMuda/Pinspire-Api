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
     * Search public profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        $users = User::query()
            ->withViewerData($request->user())
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhere('bio', 'like', "%{$term}%");
            }))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated('Users retrieved successfully.', $users, UserResource::class);
    }

    /**
     * Display the authenticated user's own profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($request->user()->id);

        return $this->success('Profile retrieved successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Display a public profile by username.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($user->id);

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
            ->with('pins:id')
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

        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($user->id);

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

        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($user->id);

        return $this->success('Avatar updated successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar !== null) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        $user = User::query()
            ->withViewerData($request->user())
            ->findOrFail($user->id);

        return $this->success('Avatar removed successfully.', (new UserResource($user))->resolve());
    }

    /**
     * Delete the authenticated user's account and all of their content.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();
        $user->delete();

        return $this->success('Account deleted successfully.');
    }
}
