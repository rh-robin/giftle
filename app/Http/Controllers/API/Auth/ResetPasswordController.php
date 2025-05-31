<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    use ResponseTrait;

    public function forgotPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            $email = $request->input('email');
            $otp = random_int(100000, 999999);
            $user = User::where('email', $email)->first();

            if (!$user) {
                return $this->sendError('User not found', 404);
            }

            Mail::to($email)->send(new OtpMail($otp, $user, 'Reset Your Password'));

            $user->update([
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(60),
            ]);

            return $this->sendResponse('OTP sent successfully.', 200);


        } catch (Exception $e) {
            return $this->sendError('Error sending OTP', $e->getMessage(), 500);

        }
    }

    public function VerifyOTP(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->sendError('User not found.', 404);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return $this->sendError('The provided OTP has expired. Please request a new one.', 400);

            }

            if ($user->otp !== $request->otp) {
               return $this->sendError('The provided OTP is incorrect. Please try again.', 400);
            }

            $token = Str::random(60);
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
                'reset_password_token' => $token,
                'reset_password_token_expire_at' => Carbon::now()->addHour(),
            ]);
            return $this->sendResponse('OTP verified successfully.', ['token' => $token], 200);
        } catch (Exception $e) {
            return $this->sendError('Failed to verify OTP. Please try again.', 500);
        }
    }

    public function ResetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->sendError('User account not found.', 404);
            }

            $tokenValid = $user->reset_password_token === $request->token &&
                $user->reset_password_token_expire_at >= Carbon::now();

            if (!$tokenValid) {
                return $this->sendError('The password reset link has expired or is invalid. Please request a new one.', 400 );
            }

            $user->update([
                'password' => Hash::make($request->password),
                'reset_password_token' => null,
                'reset_password_token_expire_at' => null,
            ]);

            return $this->sendResponse('Password reset successfully.', 200);

        } catch (Exception $e) {
            return $this->sendError('Failed to reset password. Please try again.', 500);
        }
    }
}
