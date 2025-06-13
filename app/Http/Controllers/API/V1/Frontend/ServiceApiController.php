<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Models\ServiceDetails;
use App\Http\Controllers\Controller;

class ServiceApiController extends Controller
{
    use ResponseTrait;
    public function index(){
        $services = Service::latest()->cursor();
        return $this->sendResponse($services, 'Services fetched successfully');
    }
    //servivice details fetch
    public function serviceShow($slug){
        $serviceDetails = ServiceDetails::where('slug', $slug)->first();
        if(empty($serviceDetails)){
            return $this->sendError('Service Detail not found', 404);
        }
        $serviceDetails->load('service','images','faqs','whatIncludes','caseStudies');
        return $this->sendResponse($serviceDetails, 'Service Details fetched successfully');
    }

}
