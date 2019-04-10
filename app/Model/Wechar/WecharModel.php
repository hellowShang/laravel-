<?php

namespace App\Model\Wechar;

use Illuminate\Database\Eloquent\Model;

class WecharModel extends Model
{
    // 指定表名
    protected $table = 'wechar_userinfo';

    // 关闭时间戳
    public $timestamps = false;
}
