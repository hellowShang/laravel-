<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Layout\Content;
use App\Model\WecharModel;
use GuzzleHttp\Client;

class MessageController extends Controller
{
    use HasResourceActions;

    /**
     * 消息群发
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $openid = WecharModel::all('openid')->toArray();
        return $content->body(view('admin.message',['openid' => $openid]));
    }

    public function create(){
        $data = request()->all();
        $openid = explode(',',rtrim($data['openid'],','));
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.getAccessToken();

        $arr = [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $data['content']
            ]
        ];

        $arr = json_encode($arr,JSON_UNESCAPED_UNICODE);

        $client  = new Client();
        $response = $client->request('post',$url,['body' => $arr]);
        echo $response->getBody();
    }


}
