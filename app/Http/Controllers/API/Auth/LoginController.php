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
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
            'role' => 'required|in:admin,receptionist,user',
        ]);

        try {
            // Check if the user exists
            $user = User::withTrashed()->where('email', $request->email)->first();

            if (empty($user)) {
                return $this->sendError('User not found. Please check your email or register for an account.',);
            }

            // Check the password
            if (!Hash::check($request->password, $user->password)) {
                return $this->sendError('Incorrect password. Please try again or reset your password if you\'ve forgotten it.',);
            }

            // Generate token if email is verified
            if (!$user->hasVerifiedEmail()) {
                return $this->sendError('Email not verified. Please verify your email before logging in.');
            }
            // Generate token if role is matched
            if ($user->role !== $request->role) {
                return $this->sendError('Role not matched. Please check your role.');
            }

            $token = $user->createToken('Login Token')->plainTextToken;

             return $this->sendResponse(
                data: [
                    'token_type' => 'bearer',
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_verified' => $user->is_verified,
                        'role' => $user->role
                    ]
                ],
                message: 'Login successful. Welcome back!',
                code: 200
            );
        } catch (Exception $e) {
            return $this->sendError('Something went wrong. Please try again later.', 500);
        }
    }

    public function refreshToken(): \Illuminate\Http\JsonResponse
    {
        try {
            $refreshToken = auth('api')->refresh();

            return $this->sendResponse('Token refreshed successfully', ['token' => $refreshToken]);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong');
        }
    }
}
