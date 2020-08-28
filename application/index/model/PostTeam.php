<?php

namespace app\index\model;

use think\Model;

class PostTeam extends Model
{
    protected $autoWriteTimestamp  = true;
    // 定义时间戳字段名
    protected $createTime = 'added_on';
//    protected $updateTime = 'update_at';
//类型转换
    protected $type = [
        'added_on'  =>  'timestamp',
    ];

    //绑定posts模型
    protected function posts()
    {
        return $this->belongsTo('posts', 'id', 'postid');
    }

}
