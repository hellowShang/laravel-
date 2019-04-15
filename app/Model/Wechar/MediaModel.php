<?php

namespace App\Model\Wechar;

use Illuminate\Database\Eloquent\Model;

class MediaModel extends Model
{
    // 指定表名
    protected $table = 'wechar_media';

    // 关闭时间戳
    public $timestamps = false;
}
