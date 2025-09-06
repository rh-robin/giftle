<?php

use App\Http\Controllers\API\V1\CampaignController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\ConversionRateController;
use App\Http\Controllers\API\V1\Frontend\CampaignApiController;
use App\Http\Controllers\API\V1\Frontend\CategoryApiController;
use App\Http\Controllers\API\V1\Frontend\CurrencyApiController;
use App\Http\Controllers\API\V1\Frontend\GiftRedemptionApiController;
use App\Http\Controllers\API\V1\Frontend\MicrositeApiController;
use App\Http\Controllers\API\V1\Frontend\OrderApiController;
use App\Http\Controllers\API\V1\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\V1\GiftBoxController;
use App\Http\Controllers\API\V1\GiftingController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\ServiceController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\V1\CollectionController;
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
});



Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
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

    //Category controller
    Route::controller(CategoryController::class)->prefix('v1/category')->group(function () {
        Route::get('/list', 'categoryList');
        Route::post('/create', 'categoryCreate');
        Route::post('/update/{id}', 'categoryUpdate');
        Route::delete('/delete/{id}', 'categoryDelete');
    });

    //product group controller
    Route::controller(ProductController::class)->prefix('v1/product')->group(function () {
        Route::get('/list', 'productList');
        Route::post('/create', 'productCreate');
        Route::get('/view/{id}', 'productView');
        Route::post('/update/{id}', 'productUpdate');
        Route::delete('/delete/{id}', 'productDelete');
    });


    //conversion rate controller
    Route::controller(ConversionRateController::class)->prefix('v1/conversion-rate')->group(function () {
        Route::post('/store', 'conversionRateStore');
    });

    Route::controller(OrderController::class)->prefix('v1/order')->group(function () {
        Route::get('/pending', 'pendingOrders'); // New pending orders route
        Route::get('/{id}', 'viewOrder'); // view order
    });


    // Campaign route
    Route::controller(CampaignController::class)->prefix('v1/campaign')->group(function () {
        Route::get('/pending', 'pendingCampaigns');
        Route::get('/{id}', 'viewCampaign');
    });
});





//frontend all api routes
Route::prefix('v1/')->group(function () {
    Route::get('servces', [ServiceApiController::class, 'index']);
    Route::get('servces/{id}', [ServiceApiController::class, 'serviceShow']);

    //gifting route
    Route::get('gifting', [GiftingApiController::class, 'index']);
    Route::get('gifting/{id}', [GiftingApiController::class, 'giftingShow']);
    Route::get('gifting/dropdown/list', [GiftingApiController::class, 'giftingForDropdown']);
    //gift box
    Route::get('gift-box', [GiftBoxApiController::class, 'index']);
    //Collection route
    Route::get('collections', [CollectionApiController::class, 'getCollectionsDropdown']);
    Route::get('collections/{id}', [CollectionApiController::class, 'collectionShow']);

    //Collection route
    Route::get('categories', [CategoryApiController::class, 'index']);
    Route::get('categories/dropdown/list', [CategoryApiController::class, 'categoryForDropdown']);

    //product route
    Route::get('products', [ProductApiController::class, 'index']);

    //microsite
    Route::get('campaign/microsite/{slug}/recipient-data', [MicrositeApiController::class, 'recipientPage']);
    Route::post('campaign/microsite/{slug}/recipient-data', [MicrositeApiController::class, 'storeRecipientData']);

    //gift-redemption
    Route::get('campaign/gift-redemption/{slug}/recipient-data', [GiftRedemptionApiController::class, 'recipientPage']);
    Route::post('campaign/gift-redemption/{slug}/recipient-data', [GiftRedemptionApiController::class, 'storeRecipientData']);

    // Currency route
    Route::get('get-currency', [CurrencyApiController::class, 'getCurrency']);

});


Route::get('/invoice/{slug}', [OrderApiController::class, 'downloadInvoice'])->name('invoice.download');
Route::prefix('v1/')->middleware('auth:sanctum')->group(function () {
    //orders
    Route::post('create-order', [OrderApiController::class, 'store']);
    Route::get('pending-order', [OrderApiController::class, 'pendingOrders']);
    Route::get('order/{id}', [OrderApiController::class, 'viewOrder']);

    //campaigns
    Route::get('pending-campaign', [CampaignApiController::class, 'pendingCampaigns']);
    Route::get('campaign/{id}', [CampaignApiController::class, 'viewCampaign']);
    Route::post('campaign/name', [CampaignApiController::class, 'updateCampaignName']);

    //microsite
    Route::post('campaign/microsite-setup/{id}', [MicrositeApiController::class, 'micrositeSetup']);
    Route::get('campaign/microsite/{orderId}/responses', [MicrositeApiController::class, 'viewRecipientResponses']);

    //gift-redemption
    Route::post('campaign/gift-redemption-setup', [GiftRedemptionApiController::class, 'setGiftRedeemQuantity']);
    Route::get('campaign/gift-redemption/{orderId}/responses', [GiftRedemptionApiController::class, 'viewRecipientResponses']);
});
