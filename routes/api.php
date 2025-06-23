<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\V1\GiftBoxController;
use App\Http\Controllers\API\V1\GiftingController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\ServiceController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\V1\CataloguesController;
use App\Http\Controllers\API\V1\CollectionController;
use App\Http\Controllers\API\V1\CreateOrderApiController;
use App\Http\Controllers\API\V1\ServiceDetailsController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\V1\Frontend\GiftBoxApiController;
use App\Http\Controllers\API\V1\Frontend\GiftingApiController;
use App\Http\Controllers\API\V1\Frontend\ProductApiController;
use App\Http\Controllers\API\V1\Frontend\ServiceApiController;
use App\Http\Controllers\API\V1\Frontend\CollectionApiController;

//register
Route::post('register', [RegisterController::class, 'register']);
Route::post('/verify-email', [RegisterController::class, 'verifyEmail']);
Route::post('/resend-otp', [RegisterController::class, 'resendOtp']);
//login
Route::post('login', [LoginController::class, 'login']);
//forgot password
Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/verify-otp', [ResetPasswordController::class, 'verifyOTP']);
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);

Route::group(['middleware' => 'auth:sanctum'], static function () {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);


    //Service group controller
    Route::controller(ServiceController::class)->prefix('v1/service')->group(function () {
        Route::get('/list', 'serviceList');
        Route::post('/create', 'serviceCreate');
        Route::post('/update/{id}', 'serviceUpdate');
        Route::delete('/delete/{id}', 'serviceDelete');
    });

    //service details group controller
    Route::controller(ServiceDetailsController::class)->prefix('v1/service-details')->group(function () {
        Route::get('/list', 'serviceDetailsList');
        Route::post('/create', 'serviceDetailsCreate');
        Route::post('/update/{id}', 'serviceDetailsUpdate');
        Route::delete('/delete/{id}', 'serviceDetailsDelete');

        //delete images
        Route::delete('/delete-image/{id}', 'serviceDetailsDeleteImage');
    });

    //Gifting group controller
    Route::controller(GiftingController::class)->prefix('v1/gifting')->group(function () {
        Route::get('/list', 'giftingList');
        Route::post('/create', 'giftingCreate');
        Route::post('/update/{id}', 'giftingUpdate');
        Route::delete('/delete/{id}', 'giftingDelete');
    });
    //GiftBox group controller
    Route::controller(GiftBoxController::class)->prefix('v1/gift-box')->group(function () {
        Route::get('/list', 'giftBoxList');
        Route::post('/create', 'giftBoxCreate');
        Route::post('/update/{id}', 'giftBoxUpdate');
        Route::delete('/delete/{id}', 'giftBoxDelete');
    });
    //Collection group controller
    Route::controller(CollectionController::class)->prefix('v1/collection')->group(function () {
        Route::get('/list', 'collectionList');
        Route::get('/view/{id}', 'collectionShow');
        Route::post('/create', 'collectionCreate');
        Route::post('/update/{id}', 'collectionUpdate');
        Route::delete('/delete/{id}', 'collectionDelete');
    });

    //product group controller
    Route::controller(ProductController::class)->prefix('v1/product')->group(function () {
        Route::get('/list', 'productList');
        Route::post('/create', 'productCreate');
        Route::post('/update/{id}', 'productUpdate');
        Route::delete('/delete/{id}', 'productDelete');
    });
});

//frontend all api routes
Route::prefix('v1/')->group(function () {
    Route::get('servces', [ServiceApiController::class, 'index']);
    Route::get('servces/{id}', [ServiceApiController::class, 'serviceShow']);

    //gifting route
    Route::get('gifting', [GiftingApiController::class, 'index']);
    Route::get('gifting/{id}', [GiftingApiController::class, 'serviceShow']);
    //gifting
    Route::get('gift-box', [GiftBoxApiController::class, 'index']);
    //Collection route
    Route::get('collections', [CollectionApiController::class, 'getCollectionsDropdown']);
    Route::get('collections/{id}', [CollectionApiController::class, 'collectionShow']);
    //product route
    Route::get('products', [ProductApiController::class, 'index']);
    //create Order
    Route::post('create-order', [CreateOrderApiController::class, 'createOrder']);

});
