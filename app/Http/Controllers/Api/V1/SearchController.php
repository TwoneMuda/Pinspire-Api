<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PinResource;
use App\Models\Pin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search pins by title or description.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $pins = Pin::query()
            ->with('user')
            ->withViewerData($request->user())
            ->search($request->string('q')->toString())
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('Search results retrieved successfully.', $pins, PinResource::class, [
            'query' => $request->string('q')->toString(),
        ]);
    }
}
