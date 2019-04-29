<?php

namespace App\Admin\Controllers;

use App\Model\WecharModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class WecharController extends Controller
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
            ->header('Index')
            ->description('description')
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
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WecharModel);

        $grid->id('用户Id');
        $grid->nickname('昵称');
        $grid->sex('性别')->display(function($sex){
            if($sex == 1){
                return '男';
            }else{
                return '女';
            }
        });
        $grid->headimgurl('头像')->display(function($img){
            return '<img src="'.$img.'" width="30px" height="30px">';
        });
        $grid->subscribe_time('关注时间')->display(function($time){
            return date("Y-m-d H:i:s",$time);
        });
        $grid->openid('Openid');
        $grid->sub_status('关注状态');

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
        $show = new Show(WecharModel::findOrFail($id));

        $show->id('用户Id');
        $show->nickname('昵称');
        $show->sex('性别');
        $show->headimgurl('头像');
        $show->subscribe_time('关注时间');
        $show->openid('Openid');
        $show->sub_status('关注状态');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new WecharModel);

        $form->text('nickname', '昵称');
        $form->number('sex', '性别');
        $form->text('headimgurl', '头像');
        $form->text('openid', 'Openid');

        return $form;
    }
}
