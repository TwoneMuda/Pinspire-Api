<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Pin\StorePinRequest;
use App\Http\Requests\Api\V1\Pin\UpdatePinRequest;
use App\Http\Resources\PinResource;
use App\Models\Pin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PinController extends Controller
{
    /**
     * Display the paginated pin feed.
     */
    public function index(Request $request): JsonResponse
    {
        $pins = Pin::query()
            ->with('user')
            ->withViewerData($request->user())
            ->search($request->string('search')->toString())
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated('Pins retrieved successfully.', $pins, PinResource::class);
    }

    /**
     * Store a newly uploaded pin.
     */
    public function store(StorePinRequest $request): JsonResponse
    {
        $file = $request->file('image');
        [$width, $height] = $this->imageDimensions($file->getRealPath());

        $pin = $request->user()->pins()->create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'source_url' => $request->input('source_url'),
            'image_path' => $file->store('pins', 'public'),
            'image_width' => $width,
            'image_height' => $height,
        ]);

        return $this->success('Pin uploaded successfully.', $this->resolvePin($pin->load('user'), $request), status: 201);
    }

    /**
     * Display a single pin.
     */
    public function show(Request $request, Pin $pin): JsonResponse
    {
        return $this->success('Pin retrieved successfully.', $this->resolvePin($pin->load('user'), $request));
    }

    /**
     * Update a pin owned by the authenticated user.
     */
    public function update(UpdatePinRequest $request, Pin $pin): JsonResponse
    {
        $attributes = $request->safe()->only(['title', 'description', 'source_url']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            [$width, $height] = $this->imageDimensions($file->getRealPath());

            $attributes['image_path'] = $file->store('pins', 'public');
            $attributes['image_width'] = $width;
            $attributes['image_height'] = $height;

            Storage::disk('public')->delete($pin->image_path);
        }

        $pin->update($attributes);

        return $this->success('Pin updated successfully.', $this->resolvePin($pin->load('user'), $request));
    }

    /**
     * Delete a pin owned by the authenticated user.
     */
    public function destroy(Request $request, Pin $pin): JsonResponse
    {
        $this->authorize('delete', $pin);

        Storage::disk('public')->delete($pin->image_path);

        $pin->delete();

        return $this->success('Pin deleted successfully.');
    }

    /**
     * Resolve a pin together with the viewer specific state.
     *
     * @return array<string, mixed>
     */
    private function resolvePin(Pin $pin, Request $request): array
    {
        $pin->loadCount('saves');
        $pin->setAttribute('is_saved_by_me', $this->isSavedByViewer($pin, $request));

        return (new PinResource($pin))->resolve();
    }

    /**
     * Determine whether the current viewer saved the given pin.
     */
    private function isSavedByViewer(Pin $pin, Request $request): bool
    {
        $viewer = $request->user();

        return $viewer !== null && $pin->saves()->where('user_id', $viewer->id)->exists();
    }

    /**
     * Read the width and height of an uploaded image.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(string $path): array
    {
        $size = @getimagesize($path);

        if ($size === false) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded file is not a readable image.',
            ]);
        }

        return [$size[0], $size[1]];
    }
}
