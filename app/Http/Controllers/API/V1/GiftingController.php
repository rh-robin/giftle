<?php

namespace App\Http\Controllers\API\V1;

use Exception;
use App\Helpers\Helper;
use App\Models\Gifting;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;

class GiftingController extends Controller
{
    use ResponseTrait;
    // gifiting list
    public function GiftingList()
    {
        try {
            $gifitingList = Gifting::latest()->select(['id', 'name', 'description', 'image', 'slug', 'status', 'created_at', 'updated_at'])->cursor();
            if (empty($gifitingList)) {
                return $this->sendError('gifiting List Not Found');
            }
            return $this->sendResponse($gifitingList, 'Gifiting List');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
    //gifiting create
    public function GiftingCreate(Request $request)
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
                $fileUrl = Helper::fileUpload($file, 'gifitings', $file->getClientOriginalName());

                if (!$fileUrl) {
                    throw new Exception('File upload failed');
                }
            }
            $gifiting = Gifting::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $fileUrl ? asset($fileUrl) : null,
                'slug' => $request->slug ?? Str::slug($request->name),
            ]);
            return $this->sendResponse($gifiting, 'gifiting created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during gifiting creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for gifiting name', 409);
        } catch (Exception $e) {
            Log::error('gifiting creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //gifiting update
    public function GiftingUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
        ]);
        try {
            $gifiting = Gifting::find($id);
            if (empty($gifiting)) {
                return $this->sendError('gifiting Not Found');
            }
            //file upload
            $fileUrl = '';
            if ($request->hasFile('image')) {
                //delete old file
                if (!empty($gifiting->image)) {
                    Helper::fileDelete($gifiting->image);
                }
                $file = $request->file('image');
                $fileUrl = Helper::fileUpload($file, 'gifitings', $file->getClientOriginalName());
            }
            $gifiting->update([
                'name' => $request->name,
                'description' => $request->description,
                'image' => asset($fileUrl),
                'slug' => Str::slug($request->name),
            ]);

            if (empty($gifiting)) {
                return $this->sendError('gifiting Not Updated');
            }
            return $this->sendResponse($gifiting, 'gifiting Updated');
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during gifiting creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for gifiting name', 409);
        } catch (Exception $e) {
            Log::error('gifiting creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //gifiting delete
    public function GiftingDelete($id)
    {
        try {
            $gifiting = Gifting::find($id);
            if (empty($gifiting)) {
                return $this->sendError('gifiting Not Found');
            }
            if (!empty($gifiting->image)) {
                Helper::fileDelete($gifiting->image);
            }
            $gifiting->delete();
            return $this->sendResponse($gifiting, 'Gifiting Deleted');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
}
