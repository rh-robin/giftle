<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Helpers\Helper;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LogoutController extends Controller
{
    use ResponseTrait;
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->sendError([],'User not authenticated.');
        }

        try {
            $user->currentAccessToken()?->delete(); // safe call with null check

            return $this->sendResponse(
                data: null,
                message: 'Logged out successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('Logout failed. Please try again.', ['error' => $e->getMessage()]);
        }
    }
}
