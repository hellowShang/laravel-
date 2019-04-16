<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\Wechar\WecharModel;
use App\Model\Wechar\MediaModel;
use Illuminate\Support\Facades\Storage;
// 第三方库
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
        if($xml->MsgType == 'event') {
            // 获取用户基本信息
            $userInfo = $this-> getUserInfo($openid);
            // 用户消息入库
            if($userInfo){
                // 信息返回并输出
                $message = $this-> userInfoAdd($xml,$openid,$userInfo);
            }

        }else{
            if($xml->MsgType == 'text'){
                // 消息回复、天气回复
                $message = $this->weacherMessage($xml,$time);
            }else{
                // 用户消息、素材下载
                $this-> media($xml,$openid);
                echo 'success';die;
            }
        }
        echo $message;

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

    // 用户基本消息入库，消息回复
    public  function userInfoAdd($xml,$openid,$userInfo){
        $time = time();
        if($xml->Event == 'subscribe'){
            // 查询当前openID数据库是否存在
            $arr = WecharModel::where(['openid' => $openid])->first();
            if ($arr) {

                // 检测是否取消关注，如取消则修改
                if($arr['sub_status'] == 0){
                    WecharModel::where(['openid' => $openid])->update(['sub_status' => 1]);
                }
                // 已入库，消息回复
                $message = "<xml>
                            <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                            <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                            <CreateTime>$time</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[欢迎回来，" . $arr['nickname'] . "]]></Content>
                        </xml>";
            } else {
                // 首次关注，消息入库
                $info = [
                    'sub_status' => 1,
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
                                <CreateTime>$time</CreateTime>
                                <MsgType><![CDATA[text]]></MsgType>
                                <Content><![CDATA[你好" . $userInfo['nickname'] . "，欢迎关注]]></Content>
                            </xml>";
                }
            }
        }else if($xml->Event == 'unsubscribe'){

            // 用户状态修改为未关注
            WecharModel::where('openid',$openid)->update(['sub_status' => 0]);
            $message = 'success';
        }
        return $message;
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

    //素材下载
    public function media($xml,$openid){
           // 素材下载接口
           $mdeiaid = $xml->MediaId;
           $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->getAccessToken()."&media_id=".$mdeiaid;
           $client = new Client();
           // 发送请求
           $response = $client->get($url);
           // 获取响应头
           $responseInfo = $response->getHeaders();
           // 获取文件名
           $file_name = $responseInfo['Content-disposition'][0];

           // 判断类型是图片、语音还是视频
           if($xml->MsgType == 'image'){
               // 图片文件新名字
               $new_file_name = substr(md5(time().mt_rand(11111,99999)),10,5).rtrim(substr($file_name,-10),'"');
               // 图片文件路径+名字
               $path = 'wechar/images/'.$new_file_name;
           }else if($xml->MsgType == 'voice'){
               // 语音文件新名字
               $new_file_name = substr(md5(time().mt_rand(11111,99999)),5,10).".MP3";
               // 语音文件路径+名字
               $path = 'wechar/voice/'.$new_file_name;
           }else if($xml->MsgType == 'video'){
                // 视频文件新名字
               $new_file_name = substr(md5(time().mt_rand(11111,99999)),10,5).rtrim(substr($file_name,-10),'"');
               // 视频文件路径+名字
               $path = 'wechar/video/'.$new_file_name;
           }
           // 存放文件  put(路径,文件)
           $res = Storage::put($path,$response->getBody());
           if($res){
               // TODO  请求成功
               $info = [
                   'openid' => $openid,
                   'mediaid' => $mdeiaid,
                   'type' => $xml->MsgType,
                   'url' => "storage/app/".$path,
                   'create_time' => $xml->CreateTime
               ];
               MediaModel::insert($info);
           }else{
               // TODO  请求失败
           }
   }

    // 天气回复接口
    public function weather($city){
        // 调用天气接口
        $url = "https://free-api.heweather.net/s6/weather/now?key=46229c21f97440298467a9f78ca63710&location=".$city;
        // 请求并转为数组
        $weather = json_decode(file_get_contents($url),true);
        return $weather;
    }

    // 天气消息回复
    public function weacherMessage($xml,$time){
        // 判断是否是城市+天气格式
        if(strpos($xml->Content,'+')){
            // 获取城市名称
            $city = explode('+',$xml->Content)[0];
            // 获取该城市的天气状况数据
            $weather  = $this->weather($city);

            // 判断城市输入是否正确
            if($weather['HeWeather6'][0]['status'] == 'ok'){
                $tmp = $weather['HeWeather6'][0]['now']['tmp'];                    // 温度
                $wind_dir = $weather['HeWeather6'][0]['now']['wind_dir'];         // 风向
                $wind_sc = $weather['HeWeather6'][0]['now']['wind_sc'];           // 风力
                $hum = $weather['HeWeather6'][0]['now']['hum'];                    // 湿度
                $cond_txt = $weather['HeWeather6'][0]['now']['cond_txt'];         // 天气

                // 数据拼接
                $noweacher = "天气：".$cond_txt."\n"."气温：".$tmp."\n"."风向：".$wind_dir."\n"."风力：".$wind_sc."\n"."湿度：".$hum."\n";

                // 返回xml格式
                $message = "<xml>
                                <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                                <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                                <CreateTime>$time</CreateTime>
                                <MsgType><![CDATA[text]]></MsgType>
                                <Content><![CDATA[".$noweacher."]]></Content>
                            </xml>";
            }else{
                $message = "<xml>
                                <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                                <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                                <CreateTime>$time</CreateTime>
                                <MsgType><![CDATA[text]]></MsgType>
                                <Content><![CDATA[你输入的城市有误，请重新输入]]></Content>
                            </xml>";
            }
            return $message;
        }
    }

    // 消息群发
    public function massTexting(){
        WecharModel::where('sub_status',1)->get();
    }

    //消息群发接口调用
    public function sendText($openid,$content){
        // 群发消息接口
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=".$this->getAccessToken();

        // 群发数据
        $data = [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ];

        // 发送post请求并返回返回的数据
        $client = new Client();
        $response = $client->request('post',$url,['body' => $data]);
        return $response->getBody();
    }
}
