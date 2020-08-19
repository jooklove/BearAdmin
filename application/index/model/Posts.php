<?php

namespace app\index\model;

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
    //类型转换
    protected $type = [
//        'status'    =>  'integer',
//        'score'     =>  'float',
//        'birthday'  =>  'datetime',
//        'info'      =>  'array',
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

    //绑定用户模型
    public function user()
    {
        return $this->belongsTo('user','id','uid');
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
            $where = " p.id <> " . $where;
        return self::alias('p')
            ->leftJoin('label_post l', 'p.id = l.postid')
            ->where($where)
            ->where("l.lid_type='".Label::UNIT."'")
            ->order('browse','desc')
            ->limit(3)
            ->select();
    }

    //获取置顶贴子
    public static function getTopPost()
    {
        //获取置顶贴子id
        $topPostId = PostTop::topPost(time());
        //获取置顶贴子
        $topPost = self::get($topPostId);
        if (!empty($topPost)) {
            $topPost['is_top'] = 1;
            $topPost->labelPost;
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
        //关键词like搜索
        $keywords = $param['_keywords'] ?? '';
        if ('' !== $keywords && count($this->searchField) > 0) {
            $this->searchField = implode('|', $this->searchField);
            $query->where($this->searchField, 'like', '%' . $keywords . '%');
        }

        //字段条件查询
        if (count($this->whereField) > 0 && count($param) > 0) {
            foreach ($param as $key => $value) {
                if ($value !== '' && in_array((string)$key, $this->whereField, true)) {
                    $query->where($key, $value);
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
                    $query->where($key, 'between', [$start_time, $end_time]);
                }
            }
        }

        //排序
        $order = $param['_order'] ?? '';
        $by    = $param['_by'] ?? 'desc';
        $query->order($order ?: 'id', $by ?: 'desc');
    }
}
