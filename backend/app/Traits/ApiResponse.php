<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Send a standardized success JSON response.
     */
    public function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Send a standardized error JSON response.
     */
    public function errorResponse(
        string $message = 'An error occurred',
        int $statusCode = 400,
        mixed $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null || $errorCode !== null) {
            $response['error'] = [
                'code' => $errorCode ?? 'ERROR',
                'details' => $errors,
            ];
        }

        return response()->json($response, $statusCode);
    }
}
