<?php

namespace App\Traits;

trait ResponseTrait
{
    public function sendResponse($data, $message = '', $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function sendError($error, $code = 400, $data = null)
    {
        $response = [
            'success' => false,
            'message' => '',
            'error' => $error,
        ];
        if ($data) {
            $response['data'] = $data;
        }
        return response()->json($response, $code);
    }
}
