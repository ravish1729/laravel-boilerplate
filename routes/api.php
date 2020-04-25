<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('{provider}/verifyAccessToken',['uses'=>'ApiAuthController@verifyAccessToken']);
Route::post('refresh',['uses'=>'ApiAuthController@refresh']);
Route::get('{provider}/authorize',['uses'=>'ApiAuthController@auth']);
Route::get('{provider}/login',['uses'=>'ApiAuthController@login']);

/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
