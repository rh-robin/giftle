<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\V1\GiftingController;
use App\Http\Controllers\API\V1\ServiceController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\V1\ServiceDetailsController;
use App\Http\Controllers\API\Auth\ResetPasswordController;

//register
Route::post('register', [RegisterController::class, 'register']);
Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']);
Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);
//login
Route::post('login', [LoginController::class, 'login']);
//forgot password
Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);

Route::group(['middleware' => 'auth:sanctum'], static function () {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);


    //Service group controller
    Route::controller(ServiceController::class)->prefix('v1/service')->group(function () {
        Route::get('/list', 'ServiceList');
        Route::post('/create', 'ServiceCreate');
        Route::post('/update/{id}', 'ServiceUpdate');
        Route::delete('/delete/{id}', 'ServiceDelete');
    });

    //service details group controller
    Route::controller(ServiceDetailsController::class)->prefix('v1/service-details')->group(function () {
        Route::get('/list', 'ServiceDetailsList');
        Route::post('/create', 'ServiceDetailsCreate');
        Route::post('/update/{id}', 'ServiceDetailsUpdate');
        Route::delete('/delete/{id}', 'ServiceDetailsDelete');
    });

       //Gifting group controller
    Route::controller(GiftingController::class)->prefix('v1/gifting')->group(function () {
        Route::get('/list', 'GiftingList');
        Route::post('/create', 'GiftingCreate');
        Route::post('/update/{id}', 'GiftingUpdate');
        Route::delete('/delete/{id}', 'GiftingDelete');
    });

});

