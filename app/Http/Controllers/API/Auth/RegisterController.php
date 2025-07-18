<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    use ResponseTrait;
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:50',
            'company_address' => 'required|string',
            'phone' => 'required|string|max:20|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'role' => 'required|in:receptionist,user',
        ]);
        try {
            // Create user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'company_name' => $request->input('company_name'),
                'company_address' => $request->input('company_address'),
                'phone' => $request->input('phone'),
                'email_verified_at' => Carbon::now(),
                'role' => $request->input('role'),
            ]);
            //token releted to user
            $token = $user->createToken('YourAppName')->plainTextToken;
            return $this->sendResponse(['user' => $user, 'token' => $token], 'Registration successful ', 200);
        } catch (Exception $e) {
            Log::error('Register Error', (array)$e->getMessage());
            return $this->sendError($e->getMessage());
        }
    }
}
