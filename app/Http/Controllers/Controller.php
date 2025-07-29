<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{

    protected function jsonResponse($data, $code = 200): JsonResponse
    {
        return response()->json($data, $code);
    }

    protected function successResponse($message = '', $data = [], $code = 200) : JsonResponse
    {
        return $this->jsonResponse(['message' => $message, 'data' => $data], $code);
    }

    protected function errorResponse($message, $code = 500) : JsonResponse
    {
        return $this->jsonResponse(['message' => $message], $code);
    }

    public static function invalidApiEndPoint(): JsonResponse
    {
        return response()->json('Invalid API endpoint', 404);
    }



}
