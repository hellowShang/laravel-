<?php

namespace App\Http\Controllers\Wechar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Wechar\WecharModel;

class WebAutoController extends Controller
{
    // 微信网页授权
    public function webUrl(){
        // https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxe11e8daa8e892e24&redirect_uri=http%3A%2F%2Fwechar.lab993.com%2Fwechat%2Fauto&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect

        // 1. 用户同意授权，获取code
        $code = $_GET['code'];

        // 2. 通过code换取网页授权access_token
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".env('WX_APPID')."&secret=".env('WX_SECRET')."&code=$code&grant_type=authorization_code";
        $response = json_decode(file_get_contents($url),true);

        // 3. 检验授权凭证（access_token）是否有效
        $token = $response['access_token'];
        $openid = $response['openid'];
        $url1 = "https://api.weixin.qq.com/sns/auth?access_token=$token&openid=$openid";
        $response1 = json_decode(file_get_contents($url1),true);
        if($response1['errcode'] == 0){

            // 4. 拉取用户信息(需scope为 snsapi_userinfo) 并入库
            $url2 = "https://api.weixin.qq.com/sns/userinfo?access_token=$token&openid=$openid&lang=zh_CN";
            $response2 = json_decode(file_get_contents($url2),true);

            // 查询当前openID数据库是否存在
            $arr = WecharModel::where(['openid' => $openid])->first();

            if ($arr) {
                // 已入库，消息回复
                $message = "<script>alert('欢迎登录，".$arr['nickname']."')</script>";
            } else {
                // 首次登录，数据入库
                $info = [
                    'sub_status' => 1,
                    'openid' => $response2['openid'],
                    'nickname' => $response2['nickname'],
                    'sex' => $response2['sex'],
                    'city' => $response2['city'],
                    'province' => $response2['province'],
                    'country' => $response2['country'],
                    'headimgurl' => $response2['headimgurl'],
                ];

                // 数据入库
                $res = WecharModel::insert($info);
                if ($res) {
                    // 消息回复
                    $message = "<script>alert('你好" . $response2['nickname'] . "，欢迎登录')</script>";
                }
            }


        }else{
            // 出错消息回复
            $message = "<script>alert('出错了')</script>";
        }
        echo $message;
    }
}
