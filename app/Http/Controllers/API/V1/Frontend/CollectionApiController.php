<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Collection;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;

class CollectionApiController extends Controller
{
    use ResponseTrait;
    public function index()
    {
        $Collections = Collection::latest()->cursor();
        return $this->sendResponse($Collections, 'Collections fetched successfully');
    }
}
