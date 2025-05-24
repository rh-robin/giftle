<?php

namespace App\Http\Controllers\API;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Models\ServiceDetails;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    use ResponseTrait;
    //service list
    public function serviceList()
    {
        $services = Service::latest()->get();
        if (!$services) {
            return $this->sendError('Service not found', 404);
        }
        return $this->sendResponse($services, 'Service list fetched successfully');
    }
    //service details
    public function serviceDetails($slug)
    {
        $service = Service::where('slug', $slug)->firstOrFail();
        $details = ServiceDetails::with(['faqs', 'whatIncludes', 'caseStudies', 'images'])
            ->findOrFail($service->id);

        // Add full image URLs
        $details->images->each(fn($img) => $img->url = asset("storage/{$img->images}"));

        // Remove unwanted fields
        $unwanted = ['service_id', 'created_at', 'updated_at', 'deleted_at'];
        $details->makeHidden($unwanted);

        // Clean up related models
        collect(['faqs', 'whatIncludes', 'caseStudies', 'images'])
            ->each(fn($rel) => $details->$rel->makeHidden(['created_at', 'updated_at']));

        return $this->sendResponse($details, 'Service details fetched successfully');
    }
}
