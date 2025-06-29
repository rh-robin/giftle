<?php

namespace App\Http\Controllers\API\V1;

use App\Models\CollectionImage;
use Exception;
use App\Helpers\Helper;
use App\Models\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;

class CollectionController extends Controller
{
    use ResponseTrait;

    // List all collections with their images
    public function collectionList()
    {
        try {
            $collections = Collection::latest()
                ->get();


            return $this->sendResponse($collections, 'Collection list retrieved successfully');
        } catch (Exception $e) {
            Log::error('Failed to retrieve collection list: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    // Create a new collection with multiple images
    public function collectionCreate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'sub_title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'nullable|in:active,inactive',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
        ]);

        DB::beginTransaction();
        try {
            $slug = $request->slug ?? Str::slug($request->title);
            $slug = $this->makeUniqueSlug($slug);

            $collection = Collection::create([
                'title' => $request->title,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'slug' => $slug,
                'status' => $request->status ?? 'active',
            ]);

            // Handle multiple image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filePath = Helper::fileUpload($file, 'collections', $file->getClientOriginalName());
                    if ($filePath) {
                        CollectionImage::create([
                            'collection_id' => $collection->id,
                            'image' => $filePath,
                        ]);
                    } else {
                        throw new Exception('Image upload failed');
                    }
                }
            }

            // Reload collection with images
            $collection->load('images:image,collection_id');

            // Prepare response data with image_url
            $responseData = $collection->toArray();
            $responseData['images'] = $collection->images->map(function ($image) {
                return [
                    'image' => $image->image,
                    'image_url' => $image->image ? asset($image->image) : null,
                    'collection_id' => $image->collection_id,
                ];
            })->toArray();

            DB::commit();
            return $this->sendResponse($responseData, 'Collection created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            Log::error('Duplicate entry during collection creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for collection', 409);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Collection creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    // Update an existing collection
    public function collectionUpdate(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'sub_title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'nullable|in:active,inactive',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
        ]);

        DB::beginTransaction();
        try {
            $collection = Collection::find($id);
            if (!$collection) {
                DB::rollBack();
                return $this->sendError('Collection not found', 404);
            }

            $slug = $request->slug ?? Str::slug($request->title);
            $slug = $this->makeUniqueSlug($slug, $collection->id);

            // Update collection
            $collection->update([
                'title' => $request->title,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'slug' => $slug,
                'status' => $request->status ?? $collection->status,
            ]);

            // Handle new image uploads
            if ($request->hasFile('images')) {
                // Delete existing images
                foreach ($collection->images as $image) {
                    Helper::fileDelete($image->image);
                    $image->delete();
                }

                // Upload new images
                foreach ($request->file('images') as $file) {
                    $filePath = Helper::fileUpload($file, 'collections', $file->getClientOriginalName());
                    if ($filePath) {
                        CollectionImage::create([
                            'collection_id' => $collection->id,
                            'image' => $image->image,
                        ]);
                    } else {
                        throw new Exception('Image upload failed');
                    }
                }
            }

            // Reload collection with images
            $collection->load('images:image,collection_id');

            // Prepare response data with image_url
            $responseData = $collection->toArray();
            $responseData['images'] = $collection->images->map(function ($image) {
                return [
                    'image' => $image->image,
                    'image_url' => $image->image ? asset($image->image) : null,
                    'collection_id' => $image->collection_id,
                ];
            })->toArray();

            DB::commit();
            return $this->sendResponse($responseData, 'Collection updated successfully');
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            Log::error('Duplicate entry during collection update: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for collection', 409);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Collection update failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }



    // show details of a collection
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


    // Delete a collection
    public function collectionDelete($id)
    {
        try {
            $collection = Collection::find($id);
            if (!$collection) {
                return $this->sendError('Collection not found', 404);
            }

            // Delete associated images (files and records)
            foreach ($collection->images as $image) {
                Helper::fileDelete($image->image);
            }

            // Delete collection (cascades to collection_images via foreign key)
            $collection->delete();

            return $this->sendResponse(null, 'Collection deleted successfully');
        } catch (Exception $e) {
            Log::error('Collection deletion failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong', 500);
        }
    }

    // Helper method to generate unique slugs
    protected function makeUniqueSlug($slug, $excludeId = null)
    {
        $originalSlug = $slug;
        $count = 1;

        while (Collection::where('slug', $slug)
            ->where('id', '!=', $excludeId)
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }
}
