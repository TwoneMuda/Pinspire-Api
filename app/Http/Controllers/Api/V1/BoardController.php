<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Board\StoreBoardRequest;
use App\Http\Requests\Api\V1\Board\UpdateBoardRequest;
use App\Http\Resources\BoardResource;
use App\Http\Resources\PinResource;
use App\Models\Board;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoardController extends Controller
{
    /**
     * Display the authenticated user's boards.
     */
    public function index(Request $request): JsonResponse
    {
        $boards = Board::query()
            ->where('user_id', $request->user()->id)
            ->with('user')
            ->withCount('pins')
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('Boards retrieved successfully.', $boards, BoardResource::class);
    }

    /**
     * Create a new board for the authenticated user.
     */
    public function store(StoreBoardRequest $request): JsonResponse
    {
        $name = $request->string('name')->toString();

        $board = $request->user()->boards()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($request->user()->id, $name),
            'description' => $request->input('description'),
            'is_private' => $request->boolean('is_private'),
        ]);

        $board->load('user')->loadCount('pins');

        return $this->success('Board created successfully.', (new BoardResource($board))->resolve(), status: 201);
    }

    /**
     * Display a single board.
     */
    public function show(Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $board->load('user')->loadCount('pins');

        return $this->success('Board retrieved successfully.', (new BoardResource($board))->resolve());
    }

    /**
     * Update a board owned by the authenticated user.
     */
    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $attributes = $request->safe()->only(['name', 'description', 'is_private']);

        if (array_key_exists('name', $attributes)) {
            $attributes['slug'] = $this->uniqueSlug($board->user_id, $attributes['name'], $board->id);
        }

        $board->update($attributes);
        $board->load('user')->loadCount('pins');

        return $this->success('Board updated successfully.', (new BoardResource($board))->resolve());
    }

    /**
     * Delete a board owned by the authenticated user.
     */
    public function destroy(Board $board): JsonResponse
    {
        $this->authorize('delete', $board);

        $board->delete();

        return $this->success('Board deleted successfully.');
    }

    /**
     * Display the pins saved to a board.
     */
    public function pins(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $pins = $board->pins()
            ->with('user')
            ->withViewerData($request->user())
            ->latest('saves.created_at')
            ->paginate($this->perPage($request));

        return $this->paginated('Board pins retrieved successfully.', $pins, PinResource::class);
    }

    /**
     * Build a slug that is unique per board owner.
     */
    private function uniqueSlug(int $userId, string $name, ?int $ignoreBoardId = null): string
    {
        $base = Str::slug($name) ?: 'board';
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($userId, $slug, $ignoreBoardId)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    /**
     * Determine whether the slug is already used by another board of the user.
     */
    private function slugExists(int $userId, string $slug, ?int $ignoreBoardId): bool
    {
        return Board::query()
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->when($ignoreBoardId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreBoardId))
            ->exists();
    }
}
