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

    public function ServiceDetailsList(){
        $serviceDetails = ServiceDetails::orderBy('id', 'DESC')->get();
        $serviceDetails->load(['service', 'faqs', 'whatIncludes', 'caseStudies', 'images']);
        return $this->sendResponse($serviceDetails, 'Service Details List');
    }

    public function ServiceDetailsCreate(ServiceDetailsRequest $request)
    {
        // Check if service detail already exists
        $serviceDetail = ServiceDetails::where('service_id', $request->service_id)->first();
        if ($serviceDetail) {
            return $this->sendError('Service detail already exists.');
        }
        // Create service detail
        $serviceDetail = ServiceDetails::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'service_id' => $request->service_id,
        ]);

        // Store related images
        if ($request->has('images')) {
            foreach ($request->images as $image) {
                $serviceDetailFile = Helper::fileUpload($image, 'services', $image->getClientOriginalName());
                ServiceImage::create([
                    'service_details_id' => $serviceDetail->id,
                    'images' => $serviceDetailFile,
                ]);
            }
        }

        // Store related case studies
        if ($request->has('case_studies')) {
            foreach ($request->case_studies as $caseStudy) {
                ServiceCaseStudy::create([
                    'service_details_id' => $serviceDetail->id,
                    'description' => $caseStudy['description'],
                ]);
            }
        }

        // Store related what includes
        if ($request->has('what_includes')) {
            foreach ($request->what_includes as $item) {
                ServiceWhatInclude::create([
                    'service_details_id' => $serviceDetail->id,
                    'item' => $item['item'],
                ]);
            }
        }

        // Store related FAQs
        if ($request->has('faqs')) {
            foreach ($request->faqs as $faq) {
                ServiceFAQ::create([
                    'service_details_id' => $serviceDetail->id,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        $serviceDetail->load('caseStudies', 'whatIncludes', 'faqs', 'images');

        return $this->sendResponse($serviceDetail, 'Service Detail Created');
    }

    public function ServiceDetailsUpdate(Request $request, $id)
    {
        $serviceDetail = ServiceDetails::findOrFail($id);

        // Update only the service detail fields, NOT the service_id
        $serviceDetail->update([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            // Removed service_id update - it should stay with the original service
        ]);

        // Update case studies
        if ($request->has('case_studies')) {
            foreach ($request->case_studies as $caseStudy) {
                ServiceCaseStudy::updateOrCreate(
                    [
                        'service_details_id' => $serviceDetail->id,
                        'id' => $caseStudy['id'] ?? null
                    ],
                    [
                        'description' => $caseStudy['description'],
                        'image' => $caseStudy['image'] ?? null,
                    ]
                );
            }
        }

        // Update what includes
        if ($request->has('what_includes')) {
            foreach ($request->what_includes as $whatInclude) {
                ServiceWhatInclude::updateOrCreate(
                    [
                        'service_details_id' => $serviceDetail->id,
                        'id' => $whatInclude['id'] ?? null
                    ],
                    [
                        'item' => $whatInclude['item'],
                        'description' => $whatInclude['description'] ?? null,
                        'image' => $whatInclude['image'] ?? null,
                    ]
                );
            }
        }

        // Update FAQs
        if ($request->has('faqs')) {
            foreach ($request->faqs as $faq) {
                ServiceFAQ::updateOrCreate(
                    [
                        'service_details_id' => $serviceDetail->id,
                        'id' => $faq['id'] ?? null
                    ],
                    [
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]
                );
            }
        }

        // Store in images
        if ($request->has('images')) {
            foreach ($request->images as $image) {
                // Delete old image if it exists
                if (!empty($image['id'])) {
                    $serviceImage = ServiceImage::find($image['id']);
                    if ($serviceImage) {
                        Helper::fileDelete($serviceImage->images);
                        $serviceImage->delete();
                    }
                }
                // Upload new image
                $file = $image['image'];
                $serviceDetailFile = Helper::fileUpload($file, 'services', $file->getClientOriginalName());
                ServiceImage::create([
                    'service_details_id' => $serviceDetail->id,
                    'images' => $serviceDetailFile,
                ]);
            }
        }

        $serviceDetail->load('caseStudies', 'whatIncludes', 'faqs', 'images');

        return $this->sendResponse($serviceDetail, 'Service Detail Updated');
    }
}
