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
        $attributes = $request->safe()->only([
            'title',
            'description',
            'category',
            'vibe',
            'tags',
            'palette',
            'source_url',
            'image_url',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            [$width, $height] = $this->imageDimensions($file->getRealPath());

            $attributes['image_path'] = $file->store('pins', 'public');
            $attributes['image_url'] = null;
            $attributes['image_width'] = $width;
            $attributes['image_height'] = $height;
        } else {
            $attributes['image_path'] = null;
            $attributes['image_width'] = null;
            $attributes['image_height'] = null;
        }

        $pin = $request->user()->pins()->create($attributes);

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
        $attributes = $request->safe()->only([
            'title',
            'description',
            'category',
            'vibe',
            'tags',
            'palette',
            'source_url',
            'image_url',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            [$width, $height] = $this->imageDimensions($file->getRealPath());

            $attributes['image_path'] = $file->store('pins', 'public');
            $attributes['image_url'] = null;
            $attributes['image_width'] = $width;
            $attributes['image_height'] = $height;

            if ($pin->image_path !== null) {
                Storage::disk('public')->delete($pin->image_path);
            }
        } elseif ($request->has('image_url') && $request->input('image_url') !== null) {
            $attributes['image_path'] = null;
            $attributes['image_width'] = null;
            $attributes['image_height'] = null;

            if ($pin->image_path !== null) {
                Storage::disk('public')->delete($pin->image_path);
            }
        }

        $pin->update($attributes);

        return $this->success('Pin updated successfully.', $this->resolvePin($pin->load('user'), $request));
    }

    /**
     * Delete a pin owned by the authenticated user.
     */
    public function destroy(Pin $pin): JsonResponse
    {
        $this->authorize('delete', $pin);

        if ($pin->image_path !== null) {
            Storage::disk('public')->delete($pin->image_path);
        }

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
        $viewer = $request->user();

        $pin->loadCount(['saves', 'likes', 'comments']);
        $pin->setAttribute('is_saved_by_me', $viewer !== null && $pin->saves()->where('user_id', $viewer->id)->exists());
        $pin->setAttribute('is_liked_by_me', $viewer !== null && $pin->likes()->where('user_id', $viewer->id)->exists());

        return (new PinResource($pin))->resolve();
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
