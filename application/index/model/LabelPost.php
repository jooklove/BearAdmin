<?php

namespace app\index\model;

use think\Model;

class LabelPost extends Model
{
    public function posts()
    {
        return $this->belongsTo('posts','id','postid');
    }
}
