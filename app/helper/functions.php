<?php
use Illuminate\Support\Facades\Redis;

/**
 * 获取access_token
 * @return bool
 */
function getAccessToken(){
    // 检测缓存中是否存在access_token
    $key = 'access_token';
    $token = Redis::get($key);
    if($token){
        return $token;
    }else{
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WECHAR_APPID')."&secret=".env('WECHAR_SECRET');
        $response = json_decode(file_get_contents($url),true);
        if(isset($response['access_token'])){
            // 存缓存
            Redis::set($key,$response['access_token']);
            Redis::expire($key,3600);

            return $response['access_token'];
        }else{
            return false;
        }
    }
}

/**
 * 获取jsapi_ticket
 * @return bool
 */
function getJsapiTicket(){
    // 检测缓存中是否存在
    $key = 'jsapi_ticket';
    $ticket = Redis::get($key);
    if($ticket){
        return $ticket;
    }else{
        $access_token = getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=jsapi";
        $response = json_decode(file_get_contents($url),true);
        if(isset($response['ticket'])){
            Redis::set($key,$response['ticket']);
            Redis::expire($key,3600);
            return $response['ticket'];
        }else{
            return false;
        }
    }
}