<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Consistent JSON envelope for API endpoints.
 */
class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], fn ($v) => ! is_null($v)), $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null,
        ], fn ($v) => ! is_null($v)), $status);
    }
}
