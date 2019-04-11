<?php

namespace App\Admin\Controllers;

use App\Model\Wechar\WecharModel;
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
        $grid->id('Id');
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
            return date('Y-m-d H:i:s',$time);
        });

//        $grid->column('address','地址')->display(function($grid){
//            return  $grid->country().$grid->province().'省'.$grid->city().'市';
//        });
        $grid->openid('Openid');
        $grid->subscribe('Subscribe')->display(function($subscribe){
            if($subscribe){
                return '已关注';
            }else{
                return '已取消';
            }
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
        $show = new Show(WecharModel::findOrFail($id));

        $show->id('Id');
        $show->nickname('Nickname');
        $show->sex('Sex');
        $show->headimgurl('Headimgurl');
        $show->subscribe_time('Subscribe time');
        $show->city('City');
        $show->province('Province');
        $show->country('Country');
        $show->openid('Openid');
        $show->subscribe('Subscribe');

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

        $form->text('nickname', 'Nickname');
        $form->number('sex', 'Sex');
        $form->text('headimgurl', 'Headimgurl');
        $form->number('subscribe_time', 'Subscribe time');
        $form->text('city', 'City');
        $form->text('province', 'Province');
        $form->text('country', 'Country');
        $form->text('openid', 'Openid');
        $form->number('subscribe', 'Subscribe');

        return $form;
    }
}
