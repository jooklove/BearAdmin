<?php

namespace app\index\model;

use think\Model;

class Comments extends Model
{
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'added_on';

    public function posts()
    {
        return $this->belongsTo('posts','id','postid');
    }
}
