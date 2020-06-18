<?php
/**
 * @routeNamespace("App\Http\Controllers")
 */

use Illuminate\Http\Request;

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

// app 登录
Route::any('app_login', 'Me\MeController@login')->name('login');

Route::middleware('auth:api')->group(function () {

    // app 查看自己的信息
    Route::post('app_my_info', 'Me\MeController@myInfo');

    // 刷新token
    Route::post('app_refresh_token', 'Me\MeController@refreshToken');

    // 添加队列任务
    Route::post('add_queue', 'Me\MeController@addQueue');

    // 用户余额增加
    Route::post('user_income', 'Me\MeController@income');

    // 长连接
    Route::post('push_message','Me\MeController@pushMessage');

});
