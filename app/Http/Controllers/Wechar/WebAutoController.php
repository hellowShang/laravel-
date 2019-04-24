<?php

namespace App\Http\Controllers\Wechar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WebAutoController extends Controller
{
    // 微信网页授权
    public function webUrl(){
        dd($_GET);
        $code = $_GET['code'];
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".env('WX_APPID')."&redirect_uri=http%3A%2F%2Fwechar.lab993.com%2Fwechar%2Fauto&response_type=".$code."&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        $response = json_decode(file_get_contents($url),true);
        dd($response);
    }
}
