<?php

namespace app\index\model;

use think\Model;

class PostLike extends Model
{
    protected $autoWriteTimestamp  = true;
    // 定义时间戳字段名
    protected $createTime = 'added_on';
}
