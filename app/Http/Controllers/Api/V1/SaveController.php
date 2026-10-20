<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Save\StoreSaveRequest;
use App\Http\Resources\SaveResource;
use App\Models\Board;
use App\Models\Pin;
use App\Models\Save;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveController extends Controller
{
    /**
     * Display the pins saved by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $saves = Save::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'board.user',
                'pin' => fn (Relation $pin) => $pin->with('user')->withViewerData($request->user()),
            ])
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('Saved pins retrieved successfully.', $saves, SaveResource::class);
    }

    /**
     * Save a pin to one of the authenticated user's boards.
     */
    public function store(StoreSaveRequest $request, Pin $pin): JsonResponse
    {
        $board = Board::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($request->integer('board_id'));

        $save = Save::firstOrCreate(
            ['board_id' => $board->id, 'pin_id' => $pin->id],
            ['user_id' => $request->user()->id],
        );

        if ($save->wasRecentlyCreated && $board->cover_image_path === null) {
            $board->update(['cover_image_path' => $pin->image_path]);
        }

        if ($save->wasRecentlyCreated) {
            NotificationService::push(
                recipient: User::query()->findOrFail($pin->user_id),
                actor: $request->user(),
                type: 'save',
                title: "{$request->user()->name} menyimpan pinmu",
                body: $pin->title,
                targetType: 'pin',
                targetId: $pin->id,
                imagePath: $pin->image_path,
            );
        }

        $save->load(['board.user', 'board.pins:id', 'pin.user']);
        $save->board->loadCount('pins');
        $save->pin->setAttribute('is_saved_by_me', true);
        $save->pin->loadCount('saves');

        return $this->success(
            $save->wasRecentlyCreated ? 'Pin saved to board successfully.' : 'Pin is already saved to this board.',
            (new SaveResource($save))->resolve(),
            status: $save->wasRecentlyCreated ? 201 : 200,
        );
    }

    /**
     * Remove a pin from one of the authenticated user's boards.
     */
    public function destroy(Request $request, Pin $pin): JsonResponse
    {
        $validated = $request->validate([
            'board_id' => ['required', 'integer'],
        ]);

        $save = Save::query()
            ->where('user_id', $request->user()->id)
            ->where('pin_id', $pin->id)
            ->where('board_id', $validated['board_id'])
            ->first();

        if ($save === null) {
            return $this->error('This pin is not saved to the selected board.', 404);
        }

        $save->delete();

        return $this->success('Pin removed from board successfully.');
    }
}
