<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
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

// Route cho phép CORS (Cross-Origin Resource Sharing)
// Cho phép các domain khác truy cập API với các method và header được chỉ định
Route::options('{any}', function() {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*')
        ->header('Access-Control-Allow-Credentials', 'true');
})->where('any', '.*');


$prefixLogin = '/v1/auth';
// Nhóm các route authentication với prefix /v1/auth
Route::group(['prefix' => $prefixLogin], function () {
    Route::post('/login', [SocialAuthController::class, 'login']);
    Route::post('/password', [LoginController::class, 'login']);
    Route::post('/register', [RegisterController::class, 'register']);
});
