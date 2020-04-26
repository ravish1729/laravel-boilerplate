<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiResponse;

class ApiDistributersController extends Controller
{
    private $apiResponse;

 	public function __construct(ApiResponse $apiResponse){
        $this->apiResponse=$apiResponse;
     }

    public function temp(){
        return $this->apiResponse->sendResponse(200,"Success","Hey");
    }

    public function add_details(Request $request){
        
    }
}
