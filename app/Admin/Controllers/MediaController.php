<?php

namespace App\Admin\Controllers;

use App\Model\MediaModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class MediaController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('展示素材')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MediaModel);

        $grid->id('素材id');
        $grid->media_id('Media id');
        $grid->created_at('新增时间')->display(function($time){
            return date('Y-m-d H:i:s',$time);
        });
        $grid->type('素材类型');
        $grid->url('素材路径')->display(function($img){
            return '<img src="/storage/'.$img.'" width="30px" height="30px" >';
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(MediaModel::findOrFail($id));

        $show->id('Id');
        $show->media_id('Media id');
        $show->created_at('Created at');
        $show->type('Type');
        $show->url('Url');

        return $show;
    }

}
