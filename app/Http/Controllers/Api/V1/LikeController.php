<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinResource;
use App\Models\Pin;
use App\Models\PinLike;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Display the pins liked by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $pins = Pin::query()
            ->whereHas('likes', fn ($likes) => $likes->where('user_id', $request->user()->id))
            ->with('user')
            ->withViewerData($request->user())
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('Liked pins retrieved successfully.', $pins, PinResource::class);
    }

    /**
     * Like a pin.
     */
    public function store(Request $request, Pin $pin): JsonResponse
    {
        $like = PinLike::firstOrCreate([
            'user_id' => $request->user()->id,
            'pin_id' => $pin->id,
        ]);

        if ($like->wasRecentlyCreated) {
            $owner = User::query()->findOrFail($pin->user_id);

            NotificationService::push(
                recipient: $owner,
                actor: $request->user(),
                type: 'like',
                title: "{$request->user()->name} menyukai pinmu",
                body: $pin->title,
                targetType: 'pin',
                targetId: $pin->id,
                imagePath: $pin->image_path,
            );
        }

        return $this->success('Pin liked successfully.', $this->likePayload($pin, true));
    }

    /**
     * Remove a like from a pin.
     */
    public function destroy(Request $request, Pin $pin): JsonResponse
    {
        PinLike::query()
            ->where('user_id', $request->user()->id)
            ->where('pin_id', $pin->id)
            ->delete();

        return $this->success('Pin unliked successfully.', $this->likePayload($pin, false));
    }

    /**
     * Build the like counters returned to the client.
     *
     * @return array{likes_count: int, is_liked: bool}
     */
    private function likePayload(Pin $pin, bool $liked): array
    {
        return [
            'likes_count' => $pin->likes()->count(),
            'is_liked' => $liked,
        ];
    }
}
