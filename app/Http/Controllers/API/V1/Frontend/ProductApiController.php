<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Product;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

class ProductApiController extends Controller
{
    use ResponseTrait;
    public function index()
    {
        $products = Product::with(['collections', 'gifting', 'images', 'thumbnailImage', 'priceRanges'])->paginate(10);
        return $this->sendResponse($products, 'Products List');
    }
}
