<?php

namespace App\Http\Controllers\API\V1;

use Exception;
use App\Helpers\Helper;
use App\Models\Collection;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;

class CollectionController extends Controller
{
     use ResponseTrait;
    // collection list
    public function collectionList()
    {
        try {
            $collectionList = Collection::latest()->select(['id', 'name', 'description', 'image', 'slug', 'status', 'created_at', 'updated_at'])->cursor();
            if (empty($collectionList)) {
                return $this->sendError('Collection List Not Found');
            }
            return $this->sendResponse($collectionList, 'Collection List');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
    //collection create
    public function collectionCreate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
        ]);
        try {
            // File upload
            $fileUrl = '';
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileUrl = Helper::fileUpload($file, 'collections', $file->getClientOriginalName());

                if (!$fileUrl) {
                    throw new Exception('File upload failed');
                }
            }
            $collection = Collection::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $fileUrl ? asset($fileUrl) : null,
                'slug' => $request->slug ?? Str::slug($request->name),
            ]);
            return $this->sendResponse($collection, 'Collection created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during collection creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for collection name', 409);
        } catch (Exception $e) {
            Log::error('Collection creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //collection update
    public function collectionUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
        ]);
        try {
            $collection = Collection::find($id);
            if (empty($collection)) {
                return $this->sendError('collection Not Found');
            }
            //file upload
            $fileUrl = '';
            if ($request->hasFile('image')) {
                //delete old file
                if (!empty($collection->image)) {
                    Helper::fileDelete($collection->image);
                }
                $file = $request->file('image');
                $fileUrl = Helper::fileUpload($file, 'collections', $file->getClientOriginalName());
            }
            $collection->update([
                'name' => $request->name,
                'description' => $request->description,
                'image' => asset($fileUrl),
                'slug' => Str::slug($request->name),
            ]);

            if (empty($collection)) {
                return $this->sendError('collection Not Updated');
            }
            return $this->sendResponse($collection, 'Collection Updated');
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during collection creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for collection name', 409);
        } catch (Exception $e) {
            Log::error('Collection creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //collection delete
    public function collectionDelete($id)
    {
        try {
            $collection = Collection::find($id);
            if (empty($collection)) {
                return $this->sendError('collection Not Found');
            }
            if (!empty($collection->image)) {
                Helper::fileDelete($collection->image);
            }
            $collection->delete();
            return $this->sendResponse($collection, 'Collection Deleted');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
}
