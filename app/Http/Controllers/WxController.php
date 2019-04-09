<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WxController extends Controller
{
    // 处理微信第一次接入
    public function valid(){
        // 开发者通过检验signature对请求进行校验（下面有校验方式）。
        // 若确认此次GET请求来自微信服务器，请原样返回echostr参数内容，则接入生效，成为开发者成功，否则接入失败。
        echo $_GET['echostr'];
    }

    // 接收微信消息推送
    public function wx(){
        // 接收微信消息推送post过来的信息并写入到自定义的log日志中
        $content = file_get_contents('php://input');
        $time = date('Y-m-d H:i:s');
        $str = $time.$content."\n";
        is_dir('logs') or mkdir('logs',0777,true);
        file_put_contents("logs/wx.log",$str,FILE_APPEND);

        // 回应微信
        echo 'success';
    }

    // 获取access_token
    public  function getAccessToken(){
        // 接口调用请求说明
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_SECRET');
        // echo $url;
        $response = file_get_contents($url);
    }
}
