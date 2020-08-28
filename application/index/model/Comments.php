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

    public static function getComment($postid=0,$comment=[])
    {
        if (empty($comment))
            $comment = self::where('postid',$postid)->select()->toArray();

        if (empty($comment))
            return [];
        $commentTree = [];
        foreach ($comment as $key=>&$top) {
//            $labelTree[$top['lid']] = $top;
            if ($top['pid'] == 0) {
                $commentTree[$key] = $top;
                foreach ($comment as $sub) {
                    if (!$sub['pid'])
                        continue;
                    if ($top['id'] == $sub['pid']) {
                        $commentTree[$key]['sub'][] = $sub;
                    }
                }
                if (empty($commentTree[$key]['sub']))
                    $commentTree[$key]['sub'] = [];
            }
        }
        return $commentTree;
    }
}
