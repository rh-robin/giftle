<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\Helper;
use App\Models\ServiceFAQ;
use Illuminate\Support\Str;
use App\Models\ServiceImage;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Models\ServiceDetails;
use App\Models\ServiceCaseStudy;
use App\Models\ServiceWhatInclude;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ServiceDetailsRequest;

class ServiceDetailsController extends Controller
{
    use ResponseTrait;

    public function ServiceDetailsList()
    {
        $serviceDetails = ServiceDetails::orderBy('id', 'DESC')->get();
        $serviceDetails->load(['service', 'faqs', 'whatIncludes', 'caseStudies', 'images']);
        return $this->sendResponse($serviceDetails, 'Service Details List');
    }

    public function ServiceDetailsCreate(ServiceDetailsRequest $request)
    {

        // Check if service detail already exists
        if (ServiceDetails::where('service_id', $request->service_id)->exists()) {
            return $this->sendError('Service detail already exists.', 409);
        }

        // Create service detail
        $serviceDetail = ServiceDetails::create([
            'name' => $request->name,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'service_id' => $request->service_id,
        ]);

        // Store related images (if provided)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $serviceDetailFile = Helper::fileUpload($image, 'service_details', $image->getClientOriginalName());
                ServiceImage::create([
                    'service_details_id' => $serviceDetail->id,
                    'images' => $serviceDetailFile,
                ]);
            }
        }

        // Store related case studies (if provided)
        if ($request->filled('case_studies')) {
            foreach ($request->case_studies as $caseStudy) {
                ServiceCaseStudy::create([
                    'service_details_id' => $serviceDetail->id,
                    'description' => $caseStudy['description'],
                ]);
            }
        }

        // Store related "what includes" (if provided)
        if ($request->filled('what_includes')) {
            foreach ($request->what_includes as $item) {
                ServiceWhatInclude::create([
                    'service_details_id' => $serviceDetail->id,
                    'item' => $item['item'],
                ]);
            }
        }

        // Store related FAQs (if provided)
        if ($request->filled('faqs')) {
            foreach ($request->faqs as $faq) {
                ServiceFAQ::create([
                    'service_details_id' => $serviceDetail->id,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        $serviceDetail->load('caseStudies', 'whatIncludes', 'faqs', 'images');

        return $this->sendResponse($serviceDetail, 'Service Detail Created', 201);
    }

    public function ServiceDetailsUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'description' => 'required|string',
            'service_id' => 'required|exists:services,id',
            'images' => 'nullable|array|min:1',
            'images.*' => 'file|mimes:jpg,png,jpeg,gif|max:20048',
            'case_studies.*.description' => 'required|string',
            'what_includes.*.item' => 'required|string',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
        ]);

        $serviceDetail = ServiceDetails::find($id);
        if (!$serviceDetail) {
            return $this->sendError('Service Detail not found');
        }


        // Update service detail fields (excluding service_id)
        $serviceDetail->update([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
        ]);

        // Update or create case studies
        if ($request->filled('case_studies')) {
            foreach ($request->case_studies as $caseStudy) {
                ServiceCaseStudy::updateOrCreate(
                    ['id' => $caseStudy['id'] ?? null],
                    [
                        'service_details_id' => $serviceDetail->id,
                        'description' => $caseStudy['description'],
                    ]
                );
            }
        }

        // Update or create "what includes"
        if ($request->filled('what_includes')) {
            foreach ($request->what_includes as $whatInclude) {
                ServiceWhatInclude::updateOrCreate(
                    ['id' => $whatInclude['id'] ?? null],
                    [
                        'service_details_id' => $serviceDetail->id,
                        'item' => $whatInclude['item'],
                    ]
                );
            }
        }

        // Update or create FAQs
        if ($request->filled('faqs')) {
            foreach ($request->faqs as $faq) {
                ServiceFAQ::updateOrCreate(
                    ['id' => $faq['id'] ?? null],
                    [
                        'service_details_id' => $serviceDetail->id,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]
                );
            }
        }

        // Keep existing image unless new one is uploaded
        if ($request->hasFile('images')) {
            // Delete old images (optional)
            if ($request->delete_old_images) {
                foreach ($serviceDetail->images as $image) {
                    Helper::fileDelete($image->images);
                    $image->delete();
                }
            }

            // Upload new images
            foreach ($request->file('images') as $image) {
                // Upload new image
                $serviceDetailFile = Helper::fileUpload($image, 'service_details', $image->getClientOriginalName());
                ServiceImage::create([
                    'service_details_id' => $serviceDetail->id,
                    'images' => $serviceDetailFile,
                ]);
            }
        }
        $serviceDetail->load('caseStudies', 'whatIncludes', 'faqs', 'images');

        return $this->sendResponse($serviceDetail, 'Service Detail Updated');
    }

    public function ServiceDetailsDelete($id)
    {
        $serviceDetail = ServiceDetails::find($id);
        if (!$serviceDetail) {
            return $this->sendError('Service Detail not found');
        }
        //delete images
        foreach ($serviceDetail->images as $image) {
            Helper::fileDelete($image->images);
            $image->delete();
        }
        $serviceDetail->delete();
        return $this->sendResponse([], 'Service Detail Deleted');
    }

    public function ServiceDetailsDeleteImage($id)
    {
        $serviceImage = ServiceImage::find($id);

        if (!$serviceImage) {
            return $this->sendError('Service Image not found');
        }

        // Get the actual file path (remove the asset() part if present)
        $filePath = str_replace(asset(''), '', $serviceImage->images);
        $absolutePath = public_path($filePath);

        // Delete the physical file
        Helper::fileDelete($absolutePath);

        // Delete the image record from the database
        $serviceImage->delete();

        return $this->sendResponse([], 'Image Deleted');
    }
}
