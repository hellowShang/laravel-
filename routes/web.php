<?php

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


// 配置提交时的路由
Route::get('/weixin/valid','WxController@valid');
// 扫码时的路由
Route::post('/weixin/valid','WxController@wx');