<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Models\GiftBox;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GiftBoxRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftBoxController extends Controller
{
    use ResponseTrait;
    public function giftBoxList()
    {
        try {
            $giftBoxes = GiftBox::latest()->get();


            if ($giftBoxes->isEmpty()) {
                return $this->sendError('No gift boxes found', 404);
            }

            // Prepare response data with image_url
            $responseData = $giftBoxes->map(function ($giftBox) {
                $data = $giftBox->toArray();
                $data['image'] = $giftBox->getRawOriginal('image');
                $data['image_url'] = $data['image'] ? asset($data['image']) : null;
                return $data;
            })->toArray();

            return $this->sendResponse($responseData, 'Gift box list retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve gift box list: ' . $e->getMessage());
            return $this->sendError($e->getMessage(),'Something went wrong', 500);
        }
    }

    public function giftBoxCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'giftle_branded_price' => 'required|integer|min:0',
            'custom_branding_price' => 'required|integer|min:0',
            'plain_price' => 'required|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
        ]);

        DB::beginTransaction();
        try {
            // Handle image upload
            $filePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filePath = Helper::fileUpload($file, 'gift_boxes', $file->getClientOriginalName());
                if (!$filePath) {
                    throw new \Exception('Image upload failed');
                }
            }

            $giftBox = GiftBox::create([
                'name' => $request->name,
                'giftle_branded_price' => $request->giftle_branded_price,
                'custom_branding_price' => $request->custom_branding_price,
                'plain_price' => $request->plain_price,
                'status' => $request->status ?? 'active',
                'image' => $filePath,
            ]);

            // Prepare response data with image_url
            $responseData = $giftBox->toArray();
            $responseData['image'] = $filePath;
            $responseData['image_url'] = $filePath ? asset($filePath) : null;

            DB::commit();
            return $this->sendResponse($responseData, 'Gift box created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            Log::error('Duplicate entry during gift box creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for gift box', 409);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gift box creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    public function giftBoxUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'giftle_branded_price' => 'required|integer|min:0',
            'custom_branding_price' => 'required|integer|min:0',
            'plain_price' => 'required|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
        ]);

        DB::beginTransaction();
        try {
            $giftBox = GiftBox::find($id);
            if (!$giftBox) {
                DB::rollBack();
                return $this->sendError('Gift box not found', 404);
            }

            // Handle image upload
            $filePath = $giftBox->getRawOriginal('image'); // Get raw image path
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($filePath) {
                    Helper::fileDelete($filePath);
                }

                $file = $request->file('image');
                $filePath = Helper::fileUpload($file, 'gift_boxes', $file->getClientOriginalName());
                if (!$filePath) {
                    throw new \Exception('Image upload failed');
                }
            }

            $giftBox->update([
                'name' => $request->name,
                'giftle_branded_price' => $request->giftle_branded_price,
                'custom_branding_price' => $request->custom_branding_price,
                'plain_price' => $request->plain_price,
                'status' => $request->status ?? $giftBox->status,
                'image' => $filePath,
            ]);

            // Prepare response data with image_url
            $responseData = $giftBox->toArray();
            $responseData['image'] = $filePath;
            $responseData['image_url'] = $filePath ? asset($filePath) : null;

            DB::commit();
            return $this->sendResponse($responseData, 'Gift box updated successfully');
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            Log::error('Duplicate entry during gift box update: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for gift box', 409);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gift box update failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    public function giftBoxDelete($id)
    {
        DB::beginTransaction();
        try {
            $giftBox = GiftBox::find($id);
            if (!$giftBox) {
                DB::rollBack();
                return $this->sendError('Gift box not found', 404);
            }

            // Delete image if exists
            $imagePath = $giftBox->getRawOriginal('image');
            if ($imagePath) {
                Helper::fileDelete($imagePath);
            }

            $giftBox->delete();

            DB::commit();
            return $this->sendResponse(null, 'Gift box deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gift box deletion failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
}
