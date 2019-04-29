<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    // 商品管理
    $router->resource('/goods', GoodsController::class);
    // 文件上传
    $router->get('/upload', 'UploadController@index');
    // 上传临时素材
    $router->post('/getupload', 'UploadController@create');
    // 获取临时素材
    $router->resource('/detail', MediaController::class);
    // 微信用户管理
    $router->resource('/wechar', WecharController::class);
    // 消息群发
    $router->get('/message', 'MessageController@index');
    // 消息群发执行
    $router->post('/messagedo', 'MessageController@create');

});
