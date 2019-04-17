<?php

namespace App\Http\Controllers\Wechar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Wechar\WXBizDataCryptController;
use Illuminate\Support\Str;

class WecharPayController extends Controller
{
    public $values = [];

    // 支付接口调用
    public $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

    // 异步回调
    public $notify = "https://1809liuaibin.comcto.com/wechar/notify";

    /**
     * 测试-微信支付
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     */
    public function wecharPay(){

        $total_fee =1;                                              // 支付金额
        $string =substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'),10,5);
        $out_trade_no = $string.time().mt_rand(1111,9999);                  // 订单号

        $info = [
            'appid' => env('APPID'),                        // 公众账号ID
            'mch_id' => env('MCH_ID'),                      // 商户号
            'nonce_str' => Str::random(16),               // 随机字符串
            'body' => '订单测试',                                 // 商品描述
            'out_trade_no' => $out_trade_no,                      // 商户订单号
            'total_fee' => $total_fee,                             // 标价金额
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],      // 客户端ip
            'notify_url' => $this->notify,                        // 异步通知地址
            'trade_type' =>'NATIVE'                               // 交易类型
        ];

        $this->values = $info;

        // 签名
        $this->getSign();

        // 数据转化成XML格式
        $XMLInfo = $this-> ToXml();
        // dd($XMLInfo);

        // 请求支付接口
        $arr = $this-> postXmlCurl($XMLInfo,$this->url);

        // XML数据转化成对象
        $data = simplexml_load_string($arr);

        // 将 code_url 返回给前端，前端生成 支付二维码
        return view("wechar.pay",['code_url' => $data->code_url]);
    }

    /**
     * 设计签名
     */
    public function getSign(){
        // 一、参数名ASCII码从小到大排序（字典序 A-Z排序）；
        ksort($this->values);

        // 二、签名拼接
        $str = "";
        foreach($this->values as $k => $v){
            if($k != 'sign' && $v != '' && !is_array($v)){
                $str .= $k . "=" . $v . "&";
            }
        }

        // 三、MD5加密并全部转化成大写
        $sign = strtoupper(md5($str."key=".env('MCH_KEY')));

        // 四、追加到$info里边
        $this->values['sign'] = $sign;
    }

    /**
     * 数据转化成XML格式
     * @return string
     */
    protected function ToXml()
    {
        if(!is_array($this->values)
            || count($this->values) <= 0)
        {
            die("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 请求支付接口
     * @param $xml
     * @param $url
     * @param bool $useCert
     * @param int $second
     * @return mixed
     */
    private  function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//		if($useCert == true){
//			//设置证书
//			//使用证书：cert 与 key 分别属于两个.pem文件
//			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
//			curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
//			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
//			curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
//		}
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            die("curl出错，错误码:$error");
        }
    }

    // 异步通知
    public function notify(){
        $data = file_get_contents("php://input");
        //记录日志
        $log_str = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents('logs/wx_pay_notice.log',$log_str,FILE_APPEND);
        $xml = simplexml_load_string($data);
        if($xml->result_code=='SUCCESS' && $xml->return_code=='SUCCESS'){      //微信支付成功回调
            //验证签名
            $sign = true;
            if($sign){       //签名验证成功
                //TODO 逻辑处理  订单状态更新
            }else{
                //TODO 验签失败
                echo '验签失败，IP: '.$_SERVER['REMOTE_ADDR'];
                // TODO 记录日志
            }
        }
        $response = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        echo $response;
    }
}
