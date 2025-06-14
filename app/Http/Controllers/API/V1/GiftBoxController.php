<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Models\GiftBox;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GiftBoxRequest;

class GiftBoxController extends Controller
{
    use ResponseTrait;
    public function GiftBoxList()
    {
        $gift_box = GiftBox::latest()->get();
        return $this->sendResponse($gift_box,'Gift Box List');
    }

    public function GiftBoxCreate(GiftBoxRequest $request)
    {
        $validated = $request->validated();

        $gift_box = new GiftBox();
        $gift_box->name = $request->name;
        $gift_box->description = $request->description;
        $gift_box->price = $request->price;

        //file upload
        if($request->hasFile('file')){
            $file = $request->file('file');
            $file_name = time().'_'.$file->getClientOriginalName();
            $gift_box->file = Helper::fileUpload($file, 'gift_boxes', $file_name);
        }

        $gift_box->save();
        return $this->sendResponse($gift_box,'Gift Box Created');
    }

    public function GiftBoxUpdate(Request $request, $id)
    {
        $request->validate([
             'name' => 'required|string|max:255',
            'gifte_branded_price' => 'required|integer|min:0',
            'custom_branding_price' => 'required|integer|min:0',
            'plain_price' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
        ]);

        //update file
        $gift_box = GiftBox::find($id);
         if ($request->hasFile('image')) {
            // Delete old image if exists
            if (!empty($gift_box->image)) {
                Helper::fileDelete($gift_box->image);
            }

            $file = $request->file('image');
            $file_name = time() . '_' . $file->getClientOriginalName();
            $gift_box->image = Helper::fileUpload($file, 'gift_boxes', $file_name);
        }

        $gift_box->name = $request->name;
        $gift_box->description = $request->description;
        $gift_box->price = $request->price;
        $gift_box->save();
        return $this->sendResponse($gift_box,'Gift Box Updated');
    }

    public function GiftBoxDelete($id)
    {
        $gift_box = GiftBox::find($id);
        if (!$gift_box) {
            return $this->sendError('Gift Box not found');
        }
        //delete image
        if (!empty($gift_box->image)) {
            Helper::fileDelete($gift_box->image);
        }
        $gift_box->delete();
        return $this->sendResponse($gift_box,'Gift Box Deleted');
    }
}
