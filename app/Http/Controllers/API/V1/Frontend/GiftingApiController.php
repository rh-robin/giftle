<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Gifting;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

class GiftingApiController extends Controller
{
    use ResponseTrait;
    public function index()
    {
        $giftings = Gifting::latest()->cursor();
        return $this->sendResponse($giftings, 'Giftings fetched successfully');
    }
    //servivice details fetch
    public function serviceShow($id)
    {
        $giftings = Gifting::with(['products.images', 'products.priceRange', 'products.catalouge'])
            ->where('id', $id)
            ->firstOrFail();

        return $this->sendResponse($giftings, 'Gifting Details fetched successfully');
    }

    public function giftingForDropdown()
    {
        try {
            $giftings = Gifting::where('status', 'active')
                ->select(['id', 'name', 'slug'])
                ->latest()
                ->get();



            return $this->sendResponse($giftings->toArray(), 'Gifting dropdown list retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve gifting dropdown list: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 'Something went wrong', 500);
        }
    }
}
