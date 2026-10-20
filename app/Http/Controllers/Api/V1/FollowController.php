<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Follow;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    /**
     * Display the users followed by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $users = $request->user()
            ->following()
            ->withViewerData($request->user())
            ->paginate($this->perPage($request));

        return $this->paginated('Following retrieved successfully.', $users, UserResource::class);
    }

    /**
     * Follow a user.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->error('You cannot follow yourself.', 422);
        }

        $follow = Follow::firstOrCreate([
            'follower_id' => $request->user()->id,
            'following_id' => $user->id,
        ]);

        if ($follow->wasRecentlyCreated) {
            NotificationService::push(
                recipient: $user,
                actor: $request->user(),
                type: 'follow',
                title: "{$request->user()->name} mulai mengikuti kamu",
                body: $request->user()->bio,
                targetType: 'user',
                targetId: $request->user()->id,
                imagePath: $request->user()->avatar,
            );
        }

        return $this->success('User followed successfully.', $this->followPayload($user, true));
    }

    /**
     * Unfollow a user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        Follow::query()
            ->where('follower_id', $request->user()->id)
            ->where('following_id', $user->id)
            ->delete();

        return $this->success('User unfollowed successfully.', $this->followPayload($user, false));
    }

    /**
     * Build the follow counters returned to the client.
     *
     * @return array<string, mixed>
     */
    private function followPayload(User $user, bool $following): array
    {
        $user->loadCount(['pins', 'boards', 'followers', 'following']);
        $user->setAttribute('is_following', $following);

        return (new UserResource($user))->resolve();
    }
}
