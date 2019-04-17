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


// 处理首次介入的路由
Route::get('/weixin/valid','WxController@valid');

// 微信消息推送事件
Route::post('/weixin/valid','WxController@wx');

// 获取access_token
Route::get('/weixin/access_token','WxController@getAccessToken');

// 自定义菜单
Route::get('/menu','WxController@menu');

// 消息群发
Route::get('/send','WxController@massTexting');

// 微信支付并生成二维码
Route::get('/wechar/pay','Wechar\WecharPayController@wecharPay');

// 异步通知
Route::post('/wechar/notify','Wechar\WecharPayController@notify');
