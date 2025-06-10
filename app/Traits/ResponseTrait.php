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



    public function sendError($error, $message = null, $statusCode = 400, $data = null)
    {
        $response = ['success' => false];

        // Determine status code and message based on error type
        switch (true) {
            case $error instanceof \Illuminate\Validation\ValidationException:
                $statusCode = 422;
                $response['message'] = $message ?? 'Validation failed';
                $response['errors'] = $error->errors();
                break;

            case $error instanceof \Illuminate\Database\Eloquent\ModelNotFoundException:
                $statusCode = 404;
                $response['message'] = $message ?? 'Resource not found';
                break;

            case $error instanceof \Illuminate\Database\QueryException:
                $statusCode = 500;
                $response['message'] = $message ?? 'Database error';
                if (config('app.debug')) {
                    $response['debug'] = $error->getMessage();
                }
                break;

            case $error instanceof \Illuminate\Auth\AuthenticationException:
                $statusCode = 401;
                $response['message'] = $message ?? 'Unauthenticated';
                break;

            case $error instanceof \Illuminate\Auth\Access\AuthorizationException:
                $statusCode = 403;
                $response['message'] = $message ?? 'Forbidden';
                break;

            case $error instanceof \Symfony\Component\HttpKernel\Exception\HttpException:
                $statusCode = $error->getStatusCode();
                $response['message'] = $message ?? $error->getMessage();
                break;

            case $error instanceof \Exception:
                $statusCode = 500;
                $response['message'] = $message ?? 'Internal server error';
                if (config('app.debug')) {
                    $response['debug'] = $error->getMessage();
                }
                break;

            case is_array($error):
                $response['errors'] = $error;
                $response['message'] = $message ?? 'Validation failed';
                break;

            default:
                $response['message'] = is_string($error) ? $error : ($message ?? 'An error occurred');
        }

        if ($data) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}
