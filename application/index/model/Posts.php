<?php

namespace app\index\model;

use think\Model;
use think\model\concern\SoftDelete;

class Posts extends Model
{
    use SoftDelete;

    //自动时间戳
    protected $autoWriteTimestamp = 'added_on';
    //只读字段
    protected $readonly = ['uid', 'cid', 'added_on'];
    //软删除
    protected $deleteTime = 'del_time';
    //类型转换
    protected $type = [
//        'status'    =>  'integer',
//        'score'     =>  'float',
//        'birthday'  =>  'datetime',
//        'info'      =>  'array',
    ];

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status',1);
    }

    //热度前三的帖子
    public static function hot($where)
    {
        return self::where($where)->order('browse','desc')->limit(3)->select();
    }

    //绑定置顶模型
    public function postTop()
    {
        return $this->hasMany('postTop','postid','id');
    }

    //绑定评论模型
    public function comments()
    {
        return $this->hasMany('comment','postid','id');
    }

    //绑定标签模型
    public function labelPost()
    {
        return $this->hasMany('labelPost','postid','id');
    }

    //获取置顶贴子
    public static function getTopPost()
    {
        //获取置顶贴子id
        $topPostId = PostTop::topPost(time());
        //获取置顶贴子
        $topPost = self::get($topPostId);
        if (!empty($topPost))
            $topPost['is_top'] = 1;
        return $topPost;
    }
}
