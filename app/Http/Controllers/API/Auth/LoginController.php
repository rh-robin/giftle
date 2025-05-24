<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use ResponseTrait;

    /**
     * Handles user login using their email and password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Check if the user exists
            $user = User::withTrashed()->where('email', $request->email)->first();

            if (empty($user)) {
                return $this->sendError(
                    error: 'User not found. Please check your email or register for an account.',
                    code: 404
                );
            }

            // Check the password
            if (!Hash::check($request->password, $user->password)) {
                return $this->sendError(
                    error: 'Incorrect password. Please try again or reset your password if you\'ve forgotten it.',
                    code: 401
                );
            }

            // Generate token if email is verified
            $token = $user->createToken('Login Token')->plainTextToken;

            return $this->sendResponse(
                data: [
                    'token_type' => 'bearer',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                    ]
                ],
                message: 'Login successful. Welcome back!',
                code: 200
            );
        } catch (Exception $e) {
            return $this->sendError(
                error: 'An error occurred during login. Please try again later.',
                code: 500,
                data: ['system_error' => $e->getMessage()]
            );
        }
    }

    public function refreshToken(): \Illuminate\Http\JsonResponse
    {
        try {
            $refreshToken = auth('api')->refresh();

            return $this->sendResponse(
                data: [
                    'token_type' => 'bearer',
                    'token' => $refreshToken,
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                    'user' => auth('api')->user()
                ],
                message: 'Access token refreshed successfully.',
                code: 200
            );
        } catch (Exception $e) {
            return $this->sendError(
                error: 'Failed to refresh token. Please login again.',
                code: 401,
                data: ['system_error' => $e->getMessage()]
            );
        }
    }
}
