<?php

namespace App\Http\Controllers\API\V1;

use Exception;
use App\Helpers\Helper;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Requests\V1\ServiceRequest;
use Illuminate\Database\UniqueConstraintViolationException;

class ServiceController extends Controller
{

    use ResponseTrait;
    // service list
    public function ServiceList()
    {
        try {
            $serviceList = Service::latest()->select(['id', 'name', 'description', 'image', 'slug'])->cursor();
            if (empty($serviceList)) {
                return $this->sendError('Service List Not Found');
            }
            return $this->sendResponse($serviceList, 'Service List');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
    //service create
    public function ServiceCreate(ServiceRequest $request)
    {
        try {
            // File upload
            $fileUrl = '';
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileUrl = Helper::fileUpload($file, 'services', $file->getClientOriginalName());

                if (!$fileUrl) {
                    throw new Exception('File upload failed');
                }
            }
            $service = Service::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $fileUrl ? asset($fileUrl) : null,
                'slug' => $request->slug ?? Str::slug($request->name),
            ]);
            return $this->sendResponse($service, 'Service created successfully', 201);
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during service creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for service name', 409);
        } catch (Exception $e) {
            Log::error('Service creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //service update
    public function ServiceUpdate(ServiceRequest $request, $id)
    {
        try {
            $service = Service::find($id);
            if (empty($service)) {
                return $this->sendError('Service Not Found');
            }
            //file upload
            $fileUrl = '';
            if ($request->hasFile('image')) {
                //delete old file
                if (!empty($service->image)) {
                    Helper::fileDelete($service->image);
                }
                $file = $request->file('image');
                $fileUrl = Helper::fileUpload($file, 'services', $file->getClientOriginalName());
            }
            $service->update([
                'name' => $request->name,
                'description' => $request->description,
                'image' => asset($fileUrl),
                'slug' => Str::slug($request->name),
            ]);

            if (empty($service)) {
                return $this->sendError('Service Not Updated');
            }
            return $this->sendResponse($service, 'Service Updated');
        } catch (UniqueConstraintViolationException $e) {
            Log::error('Duplicate entry during service creation: ' . $e->getMessage());
            return $this->sendError('Duplicate entry for service name', 409);
        } catch (Exception $e) {
            Log::error('Service creation failed: ' . $e->getMessage());
            return $this->sendError('Something went wrong: ' . $e->getMessage(), 500);
        }
    }
    //service delete
    public function ServiceDelete($id)
    {
        try {
            $service = Service::find($id);
            if (empty($service)) {
                return $this->sendError('Service Not Found');
            }
            if (!empty($service->image)) {
                Helper::fileDelete($service->image);
            }
            $service->delete();
            return $this->sendResponse($service, 'Service Deleted');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong');
        }
    }
}
