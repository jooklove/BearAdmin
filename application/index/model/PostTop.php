<?php

namespace app\index\model;

use think\Model;

class PostTop extends Model
{
    //自动时间戳
    protected $autoWriteTimestamp = true;

    //绑定帖子模型
    public function posts()
    {
        return $this->belongsTo('post');
    }

    public function scopeStartTime($query,$time)
    {
        $query->where('start_time','<=',$time)->field('id,name');
    }

    public function scopeEndTime($query,$time)
    {
        $query->where('end_time','>=',$time)->limit(10);
    }

    public static function topPost($time)
    {
        if (empty($time))
            $time = time();
        $condition = "( start_time <= $time AND end_time >= $time ) OR (start_time=0 AND end_time=0)";
        return self::where($condition)->value('postid');
    }
}
