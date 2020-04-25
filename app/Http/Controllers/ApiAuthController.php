<?php

namespace App\Http\Controllers;

use Auth;
use Socialite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiResponse;
use Validator;
use App\User;
use App\UserSocial;
use GuzzleHttp\Client; 
use Illuminate\Foundation\Application;
use Carbon\Carbon;

class ApiAuthController extends Controller
{
    private $msg;
    private $apiResponse;
    private $json_data;
    private $apiConsumer;
    private $db;
 	private $auth;

 	public function __construct(Application $app, ApiResponse $apiResponse){
 		$this->msg="";
        $this->apiResponse=$apiResponse;
        $this->apiConsumer = new Client();
        $this->auth = $app->make('auth');
        $this->db = $app->make('db');
 	}
 	
 	public function verifyAccessToken(Request $request,$provider){
    	try{
    		$validator = Validator::make($request->all(), [
	            'access_token' => 'required',
	        ]);

	        if($validator->fails()){
	        	return $this->apiResponse->sendResponse(400,'Parameters missing.',$validator->errors());
	        }

	        $email = "";
//	        Provider instance. To extract user details
	        $provider_obj=NULL;
    		if($provider=='google'){
	    		$config = [
	    			'client_id' => env('GOOGLE_API_ID'),
	    			'client_secret' => env('GOOGLE_API_SECRET'), 
	    			'redirect' => env('GOOGLE_API_REDIRECT')
				];
				$provider_obj = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);
	    	}
    		$user = $provider_obj->userFromToken($request->access_token);
//    		Check account in own database
    		$check_account = UserSocial::where('provider_id', $user->id)->first();
    		if($check_account){
    			$email = $check_account->user->email; 
    		}
    		else{
    			if($provider == 'google'){
    				$new_user = new User();
            		$new_user->name = $user->name;
            		$new_user->email = $user->email;
            		//$new_user->avatar = $user->avatar;
            		$new_user->save();
            		$new_user->social_accounts()->create(
                		['provider_id' => $user->id, 'provider' => $provider]
            		);
    			}
    			elseif ($provider == 'facebook') {
    				$new_user = new User();
		            $new_user->name = $user->name;
		            $new_user->email = $user->email;
		            $new_user->avatar = $user->avatar;
		            $new_user->save();
		            $new_user->social_accounts()->create(
		                ['provider_id' => $user->id, 'provider' => $provider]
		            );
    			}
    			else{
    				return $this->apiResponse->sendResponse(500,'Internal server error 1','Provider error');
    			}

    			$email = $user->email;
    		}
	        $response = $this->proxyLogin($email, 'password');
			return $response;

    	}
    	catch(\GuzzleHttp\Exception\BadResponseException $e){
    		return $this->apiResponse->sendResponse($e->getCode(),'Invalid Access Tokens','');
    	}
    }

    public function proxy($grantType, array $data = []){
//    	Get Laravel app config
 	    $config = app()->make('config');
        $data = array_merge($data, [
            'client_id'     => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
            'grant_type'    => $grantType
        ]);
        try{
        	
        	$response = $this->apiConsumer->post(sprintf('%s/oauth/token', $config->get('app.url')), [
                'form_params' => $data
            ]);
            $data = json_decode($response->getBody());

	        $token_data = [
	        	'access_token' => $data->access_token,
	        	'expires_in' => $data->expires_in,
	            'refresh_token' => $data->refresh_token,
	        ];
            return $this->apiResponse->sendResponse(200,'Login Successful',$token_data);
        }
        catch(\GuzzleHttp\Exception\BadResponseException $e){
        	$response = json_decode($e->getResponse()->getBody());
        	$data = [
                'access_token' => '',
                'expires_in' => '',
                'refresh_token' => '',
            ];
            return $this->apiResponse->sendResponse($e->getCode(),'Internal Server Error 2',$response);
        }
    }

    public function proxyLogin($email,$password){
    	$user = User::where('email',$email)->first();
    	if (!is_null($user)) {
//            $res = 1;
////          Returns all existing tokens (Active devices
//            $accessTokens = $this->token($user->id);
////          Logout everyone who is logged in with this account
//            foreach ($accessTokens as $accessToken) {
//                $res = $res * $this->proxyLogout($accessToken->id);
//            }

            return $this->proxy('password', [
                'username' => $email,
                'password' => $password
            ]);
        }
        else{

        	$data = [
        		'access_token' => '',
            	'expires_in' => '',
            	'refresh_token' => '',
            ];

            return $this->apiResponse->sendResponse(401,'The user credentials were incorrect.',$data);
        }
    }

    public function proxyLogout($accessToken){
    	try{
        	$refreshToken = $this->db
            	->table('oauth_refresh_tokens')
            	->where('access_token_id', $accessToken)
            	->update([
                	'revoked' => true
            	]);
        	if($refreshToken){
                if($this->revoke($accessToken)){
                    return 1;
                }    
            }
            return 0;
    	}
    	catch(Exception $e){

    	}
    }

    public function logout(Request $request){
        try{
            $response=1;
            $user_id = $request->user()->id;
            $accessTokens = $this->token($user_id);
            foreach ($accessTokens as $accessToken) {
                $response = $response * $this->proxyLogout($accessToken->id);
            }
            if($response){
                return $this->apiResponse->sendResponse(200,'Token successfully destroyed',$this->json_data);
            }
            return $this->apiResponse->sendResponse(500,'Internal server error 3','logout error');
        }
        catch(Exception $e){
            return $this->apiResponse->sendResponse($e->getCode(),'Internal server error 4',$e);
        }
    }

    public function revoke($accessToken){
        try{
            $Token = $this->db
                ->table('oauth_access_tokens')
                ->where('id', $accessToken)
                ->update([
                    'revoked' => true
                ]);
            if($Token){
                return 1;
            }
            return 0;
        }
        catch(Exception $e){
        	return $this->apiResponse->sendResponse($e->getCode(),'Internal server error 5',$e);
        }
    }

    public function token($user_id){
        try{
            $token = $this->db
                ->table('oauth_access_tokens')
                ->where('user_id',$user_id)
                ->where('revoked',0)
                ->get(['id']);
            return $token;
        }
        catch(Exception $e){
        	return $this->apiResponse->sendResponse($e->getCode(),'Internal server error 6',$e);
        }
        
    }

    public function refresh(Request $request){
    	try{
    		$validator = Validator::make($request->all(), [
	            'email' => 'required|email',
	            'refresh_token' => 'required',
	        ]);

	        if($validator->fails()){
	        	return $this->apiResponse->sendResponse(400,$validator->errors(),'');
	        }

	        if(!User::where('email',$request->email)->first()){
	        	return $this->apiResponse->sendResponse(404,'User not found.',''); 
	        }

    		$refreshToken = $request->get('refresh_token');
            $email = $request->get('email');
            $response = $this->proxyRefresh($refreshToken,$email);
            return $response;
    	}
    	catch(Exception $e){
    		return $this->apiResponse->sendResponse($e->getCode(),'Internal server error 7',$e);
    	}
    }

    public function proxyRefresh($refreshToken,$email){
    	return $this->proxy('refresh_token', [
            'refresh_token' => $refreshToken,
            'username' => $email
        ]);
    }

    public function auth($provider){
    	if($provider=='google'){
    		$config = [
    			'client_id' => env('GOOGLE_API_ID'),
    			'client_secret' => env('GOOGLE_API_SECRET'), 
    			'redirect' => env('GOOGLE_API_REDIRECT')
			];
			$provider = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);
    		return $provider->stateless()->redirect();
    	}
    	elseif($provider=='facebook'){
    		//return Socialite::driver($provider)->redirectUrl(env('FACEBOOK_API_REDIRECT'))->stateless()->redirect();	
    	}
    	else{
    		//
    	}
    }
    
    public function login($provider){
    	try{
    		$provider_obj=NULL;
    		if($provider=='google'){
	    		$config = [
	    			'client_id' => env('GOOGLE_API_ID'),
	    			'client_secret' => env('GOOGLE_API_SECRET'), 
	    			'redirect' => env('GOOGLE_API_REDIRECT')
				];
				$provider_obj = Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);
	    	}
			$user = $provider_obj->stateless()->user();
        $data = array("token"=>$user->token, "first_name"=>$user->user['given_name'], "last_name"=>$user->user['family_name'], "email"=>$user->email/*, "avatar"=>$user->avatar*/);
			
			return $this->apiResponse->sendResponse(200,'Success', $data);
//    		dd($user);
    	}
    	catch(Exception $e){
    		return $this->apiResponse->sendResponse($e->getCode(),'Internal server error 8',$e);
    	}
    }

}
