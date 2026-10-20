<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Display the authenticated user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $messages = Message::query()
            ->where(fn (Builder $query) => $query
                ->where('sender_id', $viewer->id)
                ->orWhere('recipient_id', $viewer->id))
            ->with(['sender', 'recipient'])
            ->latest()
            ->get();

        $conversations = [];

        foreach ($messages as $message) {
            $other = $message->sender_id === $viewer->id
                ? $message->recipient
                : $message->sender;

            if (isset($conversations[$other->id])) {
                continue;
            }

            $other->loadCount(['pins', 'boards', 'followers', 'following']);
            $other->setAttribute('is_following', \App\Models\Follow::query()
                ->where('follower_id', $viewer->id)
                ->where('following_id', $other->id)
                ->exists());

            $conversations[$other->id] = [
                'user' => (new UserResource($other))->resolve($request),
                'last_message' => (new MessageResource($message))->resolve($request),
                'unread_count' => Message::query()
                    ->where('sender_id', $other->id)
                    ->where('recipient_id', $viewer->id)
                    ->whereNull('read_at')
                    ->count(),
            ];
        }

        return $this->success('Conversations retrieved successfully.', array_values($conversations));
    }

    /**
     * Display the messages exchanged with a user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->error('You cannot open a conversation with yourself.', 422);
        }

        $messages = Message::query()
            ->where(fn (Builder $query) => $query
                ->where('sender_id', $request->user()->id)
                ->where('recipient_id', $user->id))
            ->orWhere(fn (Builder $query) => $query
                ->where('sender_id', $user->id)
                ->where('recipient_id', $request->user()->id))
            ->with(['sender', 'recipient'])
            ->oldest()
            ->limit(200)
            ->get();

        return $this->success(
            'Messages retrieved successfully.',
            MessageResource::collection($messages)->resolve($request),
        );
    }

    /**
     * Send a message to a user.
     */
    public function store(StoreMessageRequest $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->error('You cannot message yourself.', 422);
        }

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $user->id,
            'body' => $request->string('body')->toString(),
        ]);

        NotificationService::push(
            recipient: $user,
            actor: $request->user(),
            type: 'message',
            title: "Pesan baru dari {$request->user()->name}",
            body: Str::limit($message->body, 120),
            targetType: 'user',
            targetId: $request->user()->id,
            imagePath: $request->user()->avatar,
        );

        return $this->success(
            'Message sent successfully.',
            (new MessageResource($message->load('sender')))->resolve($request),
            status: 201,
        );
    }

    /**
     * Mark every message from a user as read.
     */
    public function read(Request $request, User $user): JsonResponse
    {
        Message::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success('Conversation marked as read.');
    }
}
