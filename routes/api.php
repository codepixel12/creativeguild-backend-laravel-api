<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

//For Test Hello World API
Route::get('hello','App\Http\Controllers\HelloController@hello');
//For User Registration
Route::post('/auth/register',[AuthController::class,'register']);
//For User Login
Route::post('/auth/login',[AuthController::class,'login']);
//For Logout
Route::get('/auth/logout',[AuthController::class,'logout']);
//For User Data based on JWT Token
Route::get('/auth/user',[AuthController::class,'getUser']);
//For ForgetPassword
Route::post('/auth/forget-password',[AuthController::class,'forgetPassword']);
//For ChangePassword
Route::post('/auth/reset-password',[AuthController::class,'resetPassword']);
//For ChangePassword
Route::post('/auth/user-album',[AuthController::class,'getUserAlbum']);
//For Upload Profile Picture
Route::post('/auth/user-upload-image',[AuthController::class,'uploadImage']);

