<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Valid extends Model
{
    // 指定表名
    protected $table = 'userinfo';

    // 指定主键
    public  $primaryKey = 'we_id';

    // 关闭时间戳
    public $timestamps = false;

}
