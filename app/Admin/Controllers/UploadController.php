<?php

namespace App\Admin\Controllers;

use App\Model\MediaModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    use HasResourceActions;

    /**
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content->body(view('admin.upload'));
    }

    /**
     * 新增文件素材
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create()
    {

        $type = request()->type;

        // 返回文件路径
        $fileName = $this->uplode('file');
        // echo $fileName;

        // 请求接口
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.getAccessToken().'&type='.$type;
        $client = new \GuzzleHttp\Client();
        $response = $client->request('post',$url,[
            'multipart' => [
                [
                    'name' => 'media',
                    'contents' => fopen("storage/".$fileName, 'r'),
                ]
            ]
        ]);
        // dd($response);

        // 接收响应
        $json =  json_decode($response->getBody(),true);
        if(isset($json['errcode'])){
            header('Refresh:3;url=/admin/upload');
            die('上传出错，请重新上传');
        }else{
            // 文件路径追加
            $json['url'] = $fileName;

            // 数据入库
            $res = MediaModel::insert($json);
            if($res){
                header('Refresh:3;url=/admin/detail');
                die('上传素材成功');
            }else{
                header('Refresh:3;url=/admin/upload');
                die('上传素材失败');
            }
        }


    }

    /**
     *  文件上传
     * @param $fileName
     * @return false|string
     */
    public function uplode($fileName){
        if (request()->hasFile($fileName) && request()->file($fileName)->isValid()) {
            $photo = request()->file($fileName);
            // 返回文件后缀
            $extension = $photo->getClientOriginalExtension();

            // 创建目录 根据时间创建
            // $store_result = $photo->store('upload/'.date('Ymd'));
            // 文件自定义名字
            $name = time().Str::random(10);
            $store_result = $photo->storeAs('upload/'.date('Ymd'), $name.'.'.$extension);

            return $store_result;
        }
        exit('未获取到上传文件或上传过程出错');
    }



}
