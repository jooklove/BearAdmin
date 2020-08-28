<?php

namespace app\index\model;

use app\common\model\User;
use think\Db;
use think\db\Query;
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
    // 设置json类型字段
//    protected $json = ['images'];
    //类型转换
    protected $type = [
//        'status'    =>  'integer',
//        'score'     =>  'float',
//        'birthday'  =>  'datetime',
//        'images'      =>  'array',
    ];
    //可搜索字段
    protected $searchField = ['title', 'uid', 'unit_lid', 'major_lid'];
    //可作为条件的字段
    protected $whereField = ['cid', 'status'];
    //可做为时间范围查询的字段
    protected $timeField = [];
    //用户发布的帖子
    public static function getUsrePost($where)
    {
        return self::where($where)->order('added_on', 'desc')->select();
    }
    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('status',1);
    }
    //状态获取器
    public function getStatusAttr($value)
    {
        $status = [-2=>'二级审核未通过',-1=>'一级审核未通过',0=>'待审核',1=>'一级审核通过',2=>'二级审核通过'];
        return $status[$value];
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

    //绑定标签模型
    public function postTeam()
    {
        return $this->hasMany('postTeam','postid','id');
    }

    //热度前三的帖子
    public static function hot($where)
    {
        if (is_numeric($where))
            $where = "id <> " . $where;
        return self::where($where)
            ->order('browse','desc')
            ->limit(3)
            ->select()
            ->toArray();
    }

    //获取置顶贴子
    public static function getTopPost()
    {
        //获取置顶贴子id
        $topPostId = PostTop::topPost(time());
        //获取置顶贴子
        $topPost = self::get($topPostId)->toArray();
        if (!empty($topPost)) {
            $topPost['is_top'] = 1;
        }
        return $topPost;
    }

    //openid搜索器
    public function searchUidAttr($query, $value, $data)
    {
        $query->where('uid','=', $value);
        if (isset($data['sort'])) {
            $query->order($data['sort']);
        }
    }

    /**
     * 查询处理
     * @var Query $query
     * @var array $param
     */
    public function scopeWhere($query, $param): void
    {
        $alias = $query->getOptions('alias');
        $alias = $alias['league_posts'] ? $alias['league_posts'].'.':'';
        //关键词like搜索
        $keywords = $param['_keywords'] ?? '';
        if ('' !== $keywords && count($this->searchField) > 0) {
            $this->searchField = implode('|', $this->searchField);
            $query->where($alias.$this->searchField, 'like', '%' . $keywords . '%');
        }

        //字段条件查询
        if (count($this->whereField) > 0 && count($param) > 0) {
            foreach ($param as $key => $value) {
                if ($value !== '' && in_array((string)$key, $this->whereField, true)) {
                    $query->where($alias.$key, $value);
                }
            }
        }

        //时间范围查询
        if (count($this->timeField) > 0 && count($param) > 0) {
            foreach ($param as $key => $value) {
                if ($value !== '' && in_array((string)$key, $this->timeField, true)) {
                    $field_type = $this->getFieldsType($this->table, $key);
                    $time_range = explode(' - ', $value);
                    [$start_time,$end_time] = $time_range;
                    //如果是int，进行转换
                    if (false !== strpos($field_type, 'int')) {
                        $start_time = strtotime($start_time);
                        if (strlen($end_time) === 10) {
                            $end_time .= '23:59:59';
                        }
                        $end_time = strtotime($end_time);
                    }
                    $query->where($alias.$key, 'between', [$start_time, $end_time]);
                }
            }
        }

        //排序
        $order = $param['_order'] ?? '';
        $by    = $param['_by'] ?? 'desc';
        $query->order($alias.'sort desc');
        $query->order($order ? $alias.$order : $alias.'id', $by ?: 'desc');
    }

    //导出战队成员信息
    public function export($postid)
    {
        return $this->useGlobalScope(false)->alias('p')
            ->leftJoin('classify c','p.cid = c.id')
            ->leftJoin('user u','p.uid = u.id')
            ->whereIn('p.id',$postid)
            ->field('c.name,p.title,p.browse,p.likes,u.username,p.added_on')
            ->select()->toArray();
    }

    //导出战队成员信息
    public function exportTeam($postid)
    {
        return $this->useGlobalScope(false)->alias('p')
            ->leftJoin('post_team t','p.id = t.postid')
            ->leftJoin('user u','t.uid = u.id')
            ->whereIn('p.id',$postid)
            ->where('t.id > 0')
            ->field('t.id,p.title,u.username,t.added_on')
            ->select()->toArray();
    }

    //获取战队成员信息
    public function getPostTeam($postid)
    {
        return $this->useGlobalScope(false)->alias('p')
            ->leftJoin('post_team t','p.id = t.postid')
            ->leftJoin('user u','t.uid = u.id')
            ->whereIn('p.id',$postid)
            ->where('t.id > 0')
            ->field('t.uid,u.username,u.avatar,u.pass_num,u.unit_lid,t.added_on')
            ->select()->toArray();
    }

    //获取点赞成员信息
    public function getPostLike($postid)
    {
        return $this->useGlobalScope(false)->alias('p')
            ->leftJoin('post_like t','p.id = t.postid')
            ->leftJoin('user u','t.uid = u.id')
            ->whereIn('p.id',$postid)
            ->where('t.id > 0')
            ->field('t.uid,u.username,u.avatar,t.added_on')
            ->select()->toArray();
    }

    //导出评论信息
    public function exportComment($postid)
    {
        return $this->useGlobalScope(false)->alias('p')
            ->leftJoin('comments c','p.id = c.postid')
            ->leftJoin('user u','c.uid = u.id')
            ->whereIn('p.id',$postid)
            ->field('p.title,u.username,c.content,c.img,c.likes,c.added_on')
            ->select()->toArray();
    }

    //获取评论信息
    public function getComment($postid,$uid)
    {
        $comment = $this->useGlobalScope(false)->alias('p')
            ->leftJoin('comments c','p.id = c.postid')
            ->leftJoin('user u','c.uid = u.id')
            ->where('p.id',$postid)
            ->where('c.id','>',0)
            ->field('p.title,u.username,u.nickname,u.delete_time,u.avatar,c.*')
            ->select()->toArray();

        if (empty($comment))
            return [];

        foreach ($comment as &$item) {
            if ($item['reply_uid']) {
                $item['reply_nickname'] = User::where('id',$item['reply_uid'])->value('nickname');
            }
            $item['is_like'] = Db::table('league_comment_like')
                ->where('uid',$uid)
                ->where('comment_id',$item['id'])
                ->count('uid');
        }

        return Comments::getComment(0,$comment);
    }
}
