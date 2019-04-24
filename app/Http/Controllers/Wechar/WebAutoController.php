<?php

namespace App\Http\Controllers\Wechar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WebAutoController extends Controller
{
    // 微信网页授权
    public function webUrl(){
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".env('WX_APPID')."&secret=".env('WX_SECRET')."&code=$code&grant_type=authorization_code";
        $response = json_decode(file_get_contents($url),true);
        $token = $response['access_token'];
        $openid = $response['openid'];
        $urli = "https://api.weixin.qq.com/sns/userinfo?access_token=$token&openid=$openid&lang=zh_CN";
        $responses = json_decode(file_get_contents($urli),true);
        dd($responses);
    }
}
