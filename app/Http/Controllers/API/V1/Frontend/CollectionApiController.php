<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Collection;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class CollectionApiController extends Controller
{
    use ResponseTrait;
    public function getCollectionsDropdown()
    {
        try {
            $collections = Collection::latest()
                ->select(['id', 'title'])
                ->get();

            if ($collections->isEmpty()) {
                return $this->sendError('No collections found', 404);
            }

            return $this->sendResponse($collections, 'Collection list retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve collection list: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    public function collectionShow($id)
    {
        try {
            $collection = Collection::with('images:image,collection_id')
                ->select(['id', 'title', 'sub_title', 'content', 'slug', 'status', 'created_at', 'updated_at'])
                ->find($id);

            if (!$collection) {
                return $this->sendError('Collection not found', 404);
            }

            // Prepare response data with image_url
            $responseData = $collection->toArray();
            $responseData['images'] = $collection->images->map(function ($image) {
                return [
                    'image' => $image->image,
                    'image_url' => $image->image ? asset($image->image) : null,
                    'collection_id' => $image->collection_id,
                ];
            })->toArray();

            return $this->sendResponse($responseData, 'Collection retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve collection: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }
}
