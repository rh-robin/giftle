<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Gifting;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

class GiftingApiController extends Controller
{
     use ResponseTrait;
    public function index(){
        $giftings = Gifting::latest()->cursor();
        return $this->sendResponse($giftings, 'Giftings fetched successfully');
    }
    //servivice details fetch
   public function serviceShow($slug)
{
    $giftings = Gifting::with(['products.images', 'products.priceRange', 'products.catalouge'])
        ->where('slug', $slug)
        ->firstOrFail();

    return $this->sendResponse($giftings, 'Gifting Details fetched successfully');
}
}
