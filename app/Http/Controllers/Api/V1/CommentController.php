<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Pin;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    /**
     * Display the comments written on a pin.
     */
    public function index(Request $request, Pin $pin): JsonResponse
    {
        $comments = $pin->comments()
            ->with('user')
            ->oldest()
            ->get();

        return $this->success(
            'Comments retrieved successfully.',
            CommentResource::collection($comments)->resolve($request),
        );
    }

    /**
     * Store a new comment on a pin.
     */
    public function store(StoreCommentRequest $request, Pin $pin): JsonResponse
    {
        $comment = $pin->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->string('text')->toString(),
        ]);

        $owner = User::query()->findOrFail($pin->user_id);

        NotificationService::push(
            recipient: $owner,
            actor: $request->user(),
            type: 'comment',
            title: "{$request->user()->name} mengomentari pinmu",
            body: Str::limit($comment->body, 120),
            targetType: 'pin',
            targetId: $pin->id,
            imagePath: $pin->image_path,
        );

        return $this->success(
            'Comment created successfully.',
            (new CommentResource($comment->load('user')))->resolve($request),
            status: 201,
        );
    }

    /**
     * Delete a comment owned by the authenticated user.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        abort_unless($comment->user_id === request()->user()?->id, 403);

        $comment->delete();

        return $this->success('Comment deleted successfully.');
    }
}
