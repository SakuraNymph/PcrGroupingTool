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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['web'])->group(function () {

});



// 往用户邮箱发送验证码
Route::post('send_email', [App\Http\Controllers\RegisterController::class, 'sendEmail']);

// 用户注册
Route::post('do_register', [App\Http\Controllers\RegisterController::class, 'doRegister']);

