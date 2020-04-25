<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ApiResponse extends Controller
{
    public function sendResponse($code,$message,$data){
    	if($code == 200 || $code == 201){
			if (array_key_exists("token", $data)){
				$cookie = Cookie::make('access_token', $data["token"], null, null, null, false, false);
				return response([
					'status' => 'success',
					'status_code' => $code,
					'message' => $message,
					'data' => $data
				],$code)->withCookie($cookie);
			}
    		return response([
    			'status' => 'success',
    			'status_code' => $code,
    			'message' => $message,
    			'data' => $data
    		],$code);
    	}
    	else{
    		return response([
    			'status' => 'error',
    			'status_code' => $code,
    			'message' => $message,
    			'data' => $data
    		],$code);
    	}
    }
}