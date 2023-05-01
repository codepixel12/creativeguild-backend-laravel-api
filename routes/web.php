<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dbconnection', function () {
    return view('dbconnection');
});

//Send Mail
Route::get('/send-mail', function() {
    $userEmail = 'jimi.kanoja93@gmail.com';
    dispatch(new App\Jobs\SendEmailJob($userEmail));
    dd('Send Mail Successfully!');
});