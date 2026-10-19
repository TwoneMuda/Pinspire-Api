<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Return a successful JSON response.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function success(string $message, mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return an error JSON response.
     */
    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a paginated collection of resources inside the standard envelope.
     *
     * @param  class-string<JsonResource>  $resource
     * @param  array<string, mixed>  $meta
     */
    protected function paginated(
        string $message,
        LengthAwarePaginator $paginator,
        string $resource,
        array $meta = [],
    ): JsonResponse {
        $meta = array_merge([
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
        ], $meta);

        return $this->success(
            $message,
            $resource::collection($paginator)->resolve(),
            $meta,
        );
    }

    /**
     * Resolve the requested page size, clamped to a safe range.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 50): int
    {
        return max(1, min($request->integer('per_page', $default), $max));
    }
}
