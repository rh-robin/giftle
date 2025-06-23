<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    use ResponseTrait;
    public function index()
    {
        try {
            $categories = Category::latest()
                ->select(['id', 'name', 'description', 'image', 'slug', 'status', 'created_at', 'updated_at'])
                ->get();

            if ($categories->isEmpty()) {
                return $this->sendError('No categories found', 404);
            }

            // Prepare response data with image_url
            $responseData = $categories->map(function ($category) {
                $data = $category->toArray();
                $data['image'] = $category->getRawOriginal('image');
                $data['image_url'] = $data['image'] ? asset($data['image']) : null;
                return $data;
            })->toArray();

            return $this->sendResponse($responseData, 'Category list retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve category list: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }
}
