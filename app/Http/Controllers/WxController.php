<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\Wechar\WecharModel;
use GuzzleHttp\Client;

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
        // 检测是否有logs目录，没有就创建
        is_dir('logs') or mkdir('logs',0777,true);
        file_put_contents("logs/wx.log",$str,FILE_APPEND);

        // 把xml格式的数据转化成对象格式
        $xml = simplexml_load_string($content);
        // 获取openID
        $openid = $xml->FromUserName;
        // 获取用户基本信息
        $userInfo = $this-> getUserInfo($openid);

        // 用户消息入库
        if($userInfo){
            $message = $this-> userInfoAdd($xml,$openid,$userInfo);
            echo $message;
        }

        // 回复用户消息、素材下载

        echo 'success';
    }

    // 获取用户基本信息
    public function getUserInfo($openid){
        // 获取access_token
        $access_token = $this->getAccessToken();

        //获取用户基本信息
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $response = file_get_contents($url);
        $arr = json_decode($response,true);
        return $arr;
    }

    // 用户基本消息入库
    public  function userInfoAdd($xml,$openid,$userInfo){
        if($xml->MsgType == 'event' && $xml->Event == 'subscribe') {
            // 查询当前openID数据库是否存在
            $res = WecharModel::where(['openid' => $openid])->first();
            if ($res) {
                // 已入库，消息回复
                $message = "<xml>
                            <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                            <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                            <CreateTime>time()</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[欢迎回来，" . $userInfo['nickname'] . "]]></Content>
                        </xml>";
            } else {
                // 首次关注，消息入库
                $info = [
                    'subscribe' => $userInfo['subscribe'],
                    'openid' => $userInfo['openid'],
                    'nickname' => $userInfo['nickname'],
                    'sex' => $userInfo['sex'],
                    'city' => $userInfo['city'],
                    'province' => $userInfo['province'],
                    'country' => $userInfo['country'],
                    'headimgurl' => $userInfo['headimgurl'],
                    'subscribe_time' => $userInfo['subscribe_time'],
                ];

                // 数据入库
                $res = WecharModel::insert($info);
                if ($res) {
                    // 消息回复
                    $message = "<xml>
                                <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                                <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                                <CreateTime>time()</CreateTime>
                                <MsgType><![CDATA[text]]></MsgType>
                                <Content><![CDATA[你好" . $userInfo['nickname'] . "，欢迎关注]]></Content>
                            </xml>";
                }
            }
        }
    }

    // 获取access_token
    public function getAccessToken(){
        // 检测是否有缓存
        $key = 'access_token';
        $token = Redis::get($key);
        if($token){
        }else{
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

    // 自定义菜单接口
    public function menu()
    {
        /**  1 请求路径接口*/
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken();

        /**  2 post的数据 */
        $data = [
            'button' => [
                // 第一个一级菜单
                [
                    "name" => "生活",
                    "sub_button"=> [
                        [
                            "type" => "view",
                            "name" => "搜索",
                            "url" => "http://www.soso.com/"
                        ],
                        [
                            "type" => "pic_photo_or_album",
                            "name" => "拍照或者相册发图",
                            "key"  => "key_menu_001",
                            "sub_button" =>[ ]
                        ],
                        [
                            "type" => "scancode_waitmsg",
                            "name" => "扫码带提示",
                            "key" => "key_menu_002", 
                            "sub_button" => [ ]
                        ]
                    ],
                ],

                // 第二个顶级菜单
                [
                    "type" => "view",
                    "name" => "哔哩哔哩",
                    "url" => "http://www.bilibili.com"
                ],

                // 第三个顶级菜单
                [
                    "type" => "click",
                    "name" => "酷狗新歌",
                    "key" => "key_menu_003"
                ],
            ]
        ];

        /** 3 发送请求*/
        // 实例化第三方类库
        $client = new Client();
        // 数组转化成json字符串
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        // dd($data);
        $response = $client->request('POST',$url,['body' => $data]);

        /** 4 接收响应回来的数据并处理 */
        $arr = json_decode($response->getBody(),true);
        // dd($arr);
        /** 5 判断 */
        if($arr['errcode'] > 0){
            // TODO 请求失败
            echo '创建菜单失败';
        }else{
            // TODO 请求成功
            echo '创建菜单成功';
        }
    }
}
