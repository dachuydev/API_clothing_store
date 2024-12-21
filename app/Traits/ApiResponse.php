<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($message, $data = [])
    {
        return response()->json([
            'status' => 'success',
            'ok' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function errorResponse($message, $code = 400, $error = null)
    {
        return response()->json([
            'status' => 'error',
            'ok' => false,
            'message' => $message,
            'error' => $error
        ], $code);
    }
} 