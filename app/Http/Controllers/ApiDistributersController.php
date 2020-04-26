<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Distributer;
use Auth;
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

        try{
        $user = User::find(Auth::user()->id);
        
        $data = new Distributer; 
        $data->user_id=$user->id;
        $data->Latitude = $request->latitude;
        $data->Longitude = $request->longitude;
        $data->Food = $request->food;
        $data->Mask = $request->mask;
        $data->Other = $request->other;
        $data->save();
        return $this->apiResponse->sendResponse(200, "Success", null);
        }catch(Exception $e){
            return $this->apiResponse->sendResponse(500, "Server Error", "Error");
        }
    }
}
