<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

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
        $arr = json_decode(json_encode($content),true);

        $time = date('Y-m-d H:i:s');
        $str = $time.$content."\n";
        // 检测是否有logs目录，没有就创建
        is_dir('logs') or mkdir('logs',0777,true);
        file_put_contents("logs/wx.log",$str,FILE_APPEND);
        file_put_contents("logs/test.log",$arr,FILE_APPEND);

        // 回应微信
        echo 'success';
    }

    // 获取access_token
    public  function getAccessToken(){
        // 检测是否有缓存
        $key = 'access_token';
        $token = Redis::get($key);
        if($token){
//            echo 111;
        }else{
//            echo 222;
            // 接口调用请求说明
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_SECRET');
            // echo $url;

            // 正常情况下，微信会返回下述JSON数据包给公众号
            $response = file_get_contents($url);
            // var_dump($response);

            $arr = json_decode($response,true);

            // 存缓存
            Redis::set($key,$arr['access_token']);
            // 缓存存储事件1小时
            Redis::expire($key,3600);

            $token = $arr['access_token'];
        }
        // 返回access_token
        return $token;
    }

    // 获取用户基本信息
    public function userInfo(){
        // 获取access_token
        $access_token = $this->getAccessToken();

        echo 'tonke:'.$access_token;
//        //获取用户基本信息
//        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid=o9FAg1Fzv1WOYzX8xGinlYQtRMnc&lang=zh_CN';
////        $response = file_get_contents($url);
//        var_dump($url);

    }
}
