<?php

namespace App\Http\Controllers\API\V1;

use Exception;
use App\Helpers\Helper;
use App\Models\Catalogue;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;

class CataloguesController extends Controller
{
     use ResponseTrait;
    // catalogue list
    public function CatalogueList()
    {
        try {
            $catalogueList = Catalogue::latest()->select(['id', 'name', 'description', 'image', 'slug', 'status', 'created_at', 'updated_at'])->cursor();
            if (empty($catalogueList)) {
                return $this->sendError('catalogue List Not Found');
            }
            return $this->sendResponse($catalogueList, 'catalogue List');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
    //catalogue create
    public function CatalogueCreate(Request $request)
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
                $fileUrl = Helper::fileUpload($file, 'catalogues', $file->getClientOriginalName());

                if (!$fileUrl) {
                    throw new Exception('File upload failed');
                }
            }
            $catalogue = Catalogue::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $fileUrl ? asset($fileUrl) : null,
                'slug' => $request->slug ?? Str::slug($request->name),
            ]);
            return $this->sendResponse($catalogue, 'catalogue created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during catalogue creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for catalogue name', 409);
        } catch (Exception $e) {
            Log::error('catalogue creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //catalogue update
    public function CatalogueUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
        ]);
        try {
            $catalogue = Catalogue::find($id);
            if (empty($catalogue)) {
                return $this->sendError('catalogue Not Found');
            }
            //file upload
            $fileUrl = '';
            if ($request->hasFile('image')) {
                //delete old file
                if (!empty($catalogue->image)) {
                    Helper::fileDelete($catalogue->image);
                }
                $file = $request->file('image');
                $fileUrl = Helper::fileUpload($file, 'catalogues', $file->getClientOriginalName());
            }
            $catalogue->update([
                'name' => $request->name,
                'description' => $request->description,
                'image' => asset($fileUrl),
                'slug' => Str::slug($request->name),
            ]);

            if (empty($catalogue)) {
                return $this->sendError('catalogue Not Updated');
            }
            return $this->sendResponse($catalogue, 'catalogue Updated');
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during catalogue creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for catalogue name', 409);
        } catch (Exception $e) {
            Log::error('catalogue creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //catalogue delete
    public function CatalogueDelete($id)
    {
        try {
            $catalogue = Catalogue::find($id);
            if (empty($catalogue)) {
                return $this->sendError('catalogue Not Found');
            }
            if (!empty($catalogue->image)) {
                Helper::fileDelete($catalogue->image);
            }
            $catalogue->delete();
            return $this->sendResponse($catalogue, 'catalogue Deleted');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
}
