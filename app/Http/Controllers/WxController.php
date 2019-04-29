<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\Wechar\WecharModel;
use App\Model\Wechar\MediaModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// 第三方库
use GuzzleHttp\Client;

class WxController extends Controller
{
    /**
     * 处理微信第一次接入
     */
    public function valid(){
        // 开发者通过检验signature对请求进行校验（下面有校验方式）。
        // 若确认此次GET请求来自微信服务器，请原样返回echostr参数内容，则接入生效，成为开发者成功，否则接入失败。
        echo $_GET['echostr'];
    }

    /**
     * 接收微信消息推送
     */
    public function wx(){
        // 接收微信消息推送post过来的信息并写入到自定义的log日志中
        $content = file_get_contents('php://input');
        $time = date('Y-m-d H:i:s');
        $str = $time.$content."\n";
        // 检测是否有logs目录，没有就创建
        is_dir('logs') or mkdir('logs',0777,true);


        // 把xml格式的数据转化成对象格式
        $xml = simplexml_load_string($content);file_put_contents("logs/wx.log",$str,FILE_APPEND);
        // 获取openID
        $openid = $xml->FromUserName;
        if($xml->MsgType == 'event') {
            // 获取用户基本信息
            $userInfo = $this-> getUserInfo($openid);
            if($xml->Event == 'CLICK'){
                // 自定义菜单点击跳转事件
                $this->welfare();
                echo 'success';die;
            }
            /*
            // 关注、取消关注事件
            if($userInfo){
                // 信息返回并输出
                $message = $this-> userInfoAdd($xml,$openid,$userInfo);
            }
            */
            // 扫描带参数的二维码事件
            $message = $this->scan($xml,$openid,$userInfo);
        }else{
            if($xml->MsgType == 'text'){
                // 判断是否是城市+天气格式
                if(strpos($xml->Content,'+')) {
                    // 天气回复
                    $message = $this->weacherMessage($xml, $time);
                }else if($xml->Content == '最新商品'){
                   // 图文回复
                    $message = $this->news($xml,$time);
                }else{
                    // 商品信息回复
                    $message = $this->back($xml,$time);
                }
            }else{
                // 用户消息、素材下载
                $this-> media($xml,$openid);
                echo 'success';die;
            }
        }
        echo $message;

    }

    /**
     * 获取用户基本信息
     * @param $openid
     * @return mixed
     */
    public function getUserInfo($openid){
        // 获取access_token
        $access_token = $this->getAccessToken();

        //获取用户基本信息
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $response = file_get_contents($url);
        $arr = json_decode($response,true);
        return $arr;
    }

    /**
     * 用户基本消息入库，消息回复
     * @param $xml
     * @param $openid
     * @param $userInfo
     * @return string
     */
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

    /**
     * 获取access_token
     * @return mixed
     */
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

    /**
     * 自定义菜单接口
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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
                    "name" => "最新福利",
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

    /**
     * 素材下载
     * @param $xml
     * @param $openid
     */
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

    /**
     * 天气回复接口
     * @param $city
     * @return mixed
     */
    public function weather($city){
        // 调用天气接口
        $url = "https://free-api.heweather.net/s6/weather/now?key=46229c21f97440298467a9f78ca63710&location=".$city;
        // 请求并转为数组
        $weather = json_decode(file_get_contents($url),true);
        return $weather;
    }

    /**
     * 天气消息回复
     * @param $xml
     * @param $time
     * @return string
     */
    public function weacherMessage($xml,$time){
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

    /**
     * 消息群发
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function massTexting(){
        // 获取openID
        $arr = WecharModel::where(['sub_status'=> 1])->get()->toArray();
        $openid = array_column($arr,'openid');

        // 群发内容
        $content = '美好的一天从早上开始，今天你微笑了吗？'.Str::random(6);

        // 响应回来的信息
        $response = $this-> sendText($openid,$content);
        echo $response;
    }

    /**
     * 消息群发接口调用
     * @param $openid
     * @param $content
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendText($openid,$content){
        // 群发消息接口
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=".$this->getAccessToken();

        // 群发数据
        $arr = [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ];

        $data = json_encode($arr,JSON_UNESCAPED_UNICODE);

        // 发送post请求并返回返回的数据
        $client = new Client();
        $response = $client->request('POST',$url,['body' => $data]);
        return $response->getBody();
    }

    /**
     * 最新商品数据获取
     * @param $xml
     * @return bool|\Illuminate\Support\Collection
     */
    public function  getGoodsInfo(){
        $goodsInfo = DB::table('shop_goods')->orderBy('create_time','desc')->limit(5)->get();
        if($goodsInfo){
            return $goodsInfo;
        }else{
            return false;
        }
    }

    /**
     * 图文回复
     * @param $xml
     * @param $time
     * @return string
     */
    public function news($xml,$time){
        // 数据库获取并推送
        $goodsInfo = $this-> getGoodsInfo();
        if($goodsInfo){
                $message = "<xml>       
                                <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                                <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                                <CreateTime>$time</CreateTime>
                                <MsgType><![CDATA[news]]></MsgType>
                                <ArticleCount>1</ArticleCount>
                                <Articles>
                                    <item>
                                        <Title><![CDATA[最新商品查看]]></Title>
                                        <Description><![CDATA[绝对不容错过，今日精选五条商品推荐]]></Description>
                                        <PicUrl><![CDATA[https://i04picsos.sogoucdn.com/778fa0784ef03a8e]]></PicUrl>
                                        <Url><![CDATA[http://www.lab993.com/goods/list]]></Url>
                                    </item>
                                </Articles>
                            </xml>";
        }else{
            $message = "<xml>       
                            <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                            <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                            <CreateTime>$time</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[数据有误]]></Content>
                        </xml>";
        }
        return $message;
    }

    /**
     * 微信jssdk
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function list(){
        // 1. 准备jsapi_ticket、noncestr、timestamp和url
        // 获取jsapi_ticket
        $jsapi_ticket = getJsapiTicket();
        $noncestr=Str::random(10);
        $timestamp=time();
        $url= $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        // 2. 字典序排序
        $str = "jsapi_ticket=$jsapi_ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";

        // 3. 对string1进行sha1签名，得到signature：
        $signature = sha1($str);

        // 4. 数据传递到视图
        $data = [
            'appid'  => env('WX_APPID'),
            'noncestr'      => $noncestr,
            'timestamp'     => $timestamp,
            'url'           => $url,
            'signature'     => $signature,
            'goodsInfo' => $this->getGoodsInfo()
        ];
        return view('goods.list',$data);
    }

    /**
     * 获取ticket
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public  function getTicket(){
        //  请求接口
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.getAccessToken();

        // post 数据
        $data = [
            'expire_seconds' => 604800,
            'action_name' => 'QR_SCENE',
            'action_info' =>  [
                'scene' => [
                    'scene_id' => 10011
                ]
            ]
        ];
        $data = json_encode($data);

        // 发送请求
        $client = new Client();
        $response = $client->request('POST',$url,['body' => $data]);

        // 接收响应并转化为数组
        $arr = json_decode($response->getBody(),true);
        $ticket = $arr['ticket'];

        // 返回ticket
        return $ticket;
    }

    /**
     * 返回二维码
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function ercode(){
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$this->getTicket();
        return view('wechar.code',['url' => $url]);
    }

    /**
     * 扫描带参数的二维码
     * @param $xml
     * @param $openid
     * @param $userInfo
     * @return string
     */
    public function scan($xml,$openid,$userInfo){
        $time = time();
        switch($xml->Event){
            case 'subscribe':       // 关注
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
                $res = DB::table('wechar_wxuser')->insert($info);
                if ($res) {
                    // 消息回复
                    $message = "<xml>
                                <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                                <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                                <CreateTime>$time</CreateTime>
                                <MsgType><![CDATA[news]]></MsgType>
                                <ArticleCount>1</ArticleCount>
                                  <Articles>
                                    <item>
                                      <Title><![CDATA[欢迎关注".$userInfo['nickname']."]]></Title>
                                      <Description><![CDATA[暂时没有什么可以描述的，就这点东西]]></Description>
                                      <PicUrl><![CDATA[https://i04picsos.sogoucdn.com/778fa0784ef03a8e]]></PicUrl>
                                      <Url><![CDATA[http://wechar.lab993.com/goods/list]]></Url>
                                    </item>
                                  </Articles>
                            </xml>";
                }
                break;
            case 'SCAN':          // 关注了的扫码
                // 消息回复
                $message = "<xml>
                                <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                                <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                                <CreateTime>$time</CreateTime>
                                <MsgType><![CDATA[news]]></MsgType>
                                <ArticleCount>1</ArticleCount>
                                  <Articles>
                                    <item>
                                      <Title><![CDATA[欢迎回来]]></Title>
                                      <Description><![CDATA[暂时没有什么可以描述的，就这点东西]]></Description>
                                      <PicUrl><![CDATA[https://i04picsos.sogoucdn.com/778fa0784ef03a8e]]></PicUrl>
                                      <Url><![CDATA[http://wechar.lab993.com/goods/list]]></Url>
                                    </item>
                                  </Articles>
                            </xml>";
                break;
            case 'unsubscribe':     // 取消关注
                $res = DB::table('wechar_wxuser')->where('openid',$openid)->delete();
                if($res){
                    $message = 'success';
                }
                break;
        }
        return $message;
    }

    /**
     * 商品详细数据
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function detail($id){
        if(!$id){
            header('Refresh:3;url=/goods/list');
            die('请重新操作');
        }
        // 商品数据
        $detail = DB::table('shop_goods')->where('goods_id',$id)->first();
        if($detail){
            // 网址拼接
            $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $data = [
                'detail' => $detail,
                'url' => $url
            ];
            return view('goods.detail',$data);
        }else{
            header('Refresh:3;url=/goods/list');
            die('暂无查到商品数据');
        }
    }

    /**
     * 商品信息回复
     * @param $xml
     * @param $time
     * @return string
     */
    public function back($xml,$time){
        // 根据条件搜索商品信息
        $goodsDetail = DB::table('shop_goods')->where('goods_name','like',"%{$xml->Content}%")->first();
        if(!$goodsDetail){
            $id = rand(11,99);
            $goodsDetail =  DB::table('shop_goods')->where(['goods_id'=>$id])->first();
        }
        $message = "<xml>
                        <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
                        <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
                        <CreateTime>$time</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA[$goodsDetail->goods_name]]></Title>
                              <Description><![CDATA[".rtrim(ltrim($goodsDetail->goods_desc,'<p>'),'</p>')."]]></Description>
                              <PicUrl><![CDATA[http://www.lab993.com/uploads/goodsimgs/$goodsDetail->goods_img]]></PicUrl>
                              <Url><![CDATA[http://wechar.lab993.com/goods/detail/".$goodsDetail->goods_id."]]></Url>
                            </item>
                          </Articles>
                    </xml>";
        return $message;
    }

    /**
     * 跳转至福利页面
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function welfare(){
        header('Refresh:3;url=https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxe11e8daa8e892e24&redirect_uri=http%3A%2F%2Fwechar.lab993.com%2Fwechat%2Fauto&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect');
    }
}
