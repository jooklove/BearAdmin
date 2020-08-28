<?php
namespace app\admin\controller;

use app\common\model\User;
use app\index\model\Comments;
use app\index\model\Label;
use app\index\model\Posts;
use think\Request;

class PostController extends Controller
{
    const EX_TEAM = 'team';
    const EX_COMMENT = 'comment';

    public $classify = [1=>'我有金点子',2=>'我有疑难杂症'];

    public function deleted(Posts $model, Request $request)
    {
        return $this->index($model, $request, true);
    }

	public function index(Posts $model, Request $request, $deleted = false)
	{
        $param = $request->param();
        $where = '';
        //二级审核员只显示本单位的发帖信息
        if ($this->auditor['level'] == 2) {
            $where = 'unit_lid='.$this->auditor['lid'];
        }
        //导出数据
        if ($request->has('export_data')) {
            return $this->export_data($param,$where,$request->param('export_type'),$model);
        }
        //仅查询软删除的数据
        if ($deleted)
            $model = $model->onlyTrashed();
        else
            $model = $model->useGlobalScope(false);

        $post = $model->alias('p')
            ->leftJoin('user u','u.id=p.uid')
            ->scope('where', $param)
            ->where($where)
            ->field('p.*, u.username')
            ->paginate($this->admin['per_page'], false, ['query' => $request->get()]);
//        dump($post);die();
        $post->each(function ($item){
            $item['comment_num'] = Comments::where('postid',$item['id'])->count();
        });
        //关键词，排序等赋值
        $this->assign($request->get());
        $this->assign([
            'data'  => $post,
            'deleted' => $deleted,
            'page'  => $post->render(),
            'total' => $post->total(),
            'classify'  => $this->classify,
        ]);
		return $this->fetch('index');
	}

	public function add()
    {
        return $this->fetch();
    }

    public function edit()
    {
        return $this->fetch();
    }

    public function save($id)
    {
        return $this->fetch();
    }

    public function del($id,$rollback=0)
    {
        if ($rollback) {
            $posts = Posts::onlyTrashed()->find($id);
            $res = $posts->restore();
        } else {
            $res = Posts::destroy($id);
        }
        return $res ? success('操作成功', URL_RELOAD) : error();
    }

    //审核通过
    public function enable($id, Posts $model)
    {
        $status = $this->auditor['level'];
        if (empty($status))
            error('无权限', $this->request->isGet() ? null : URL_CURRENT);
        $result = $model->useGlobalScope(false)
            ->whereIn('id', $id)->update(['status' => $status,'pass_time'=>time()]);
        if ($result) {
            //增加用户发帖通过数
            $uid = $model::where('id','in', $id)->column('uid');
            User::passNum($uid);
        }
        return $result ? success('操作成功',url('index')) : error();
    }

    //审核不通过
    public function disable($id, Posts $model)
    {
        $status = $this->auditor['level'] * -1;
        if (empty($status))
            error('无权限', $this->request->isGet() ? null : URL_CURRENT);
        $result = $model->useGlobalScope(false)
            ->whereIn('id', $id)->update(['status' => $status,'pass_time'=>0]);
        if ($result) {
            //增加用户发帖通过数
            $uid = $model::where('id','in', $id)->column('uid');
            User::passNum($uid,false);
        }
        return $result ? success('操作成功',url('index')) : error();
    }
    //导出数据
    private function export_data($param,$where,$export_type,Posts $model)
    {
        $postid = $model->useGlobalScope(false)
            ->scope('where', $param)
            ->where($where)
            ->column('id');

        switch ($export_type) {
            case self::EX_TEAM:
                $data = $model->exportTeam($postid);
                $head = ['ID','战队帖子','用户名','时间'];
                $name = '战队人员清单'.date('Y-m-d_h-i-s');
                break;
            case self::EX_COMMENT:
                $data = $model->exportComment($postid);
                $head = ['评论帖子','评论用户','评论','图片','点赞量','时间'];
                $name = '评论'.date('Y-m-d_h-i-s');
                break;
            default:
                $data = $model->export($postid);
                $head = ['分类','标题','浏览量','点赞量','用户名','时间'];
                $name = '帖子'.date('Y-m-d_h-i-s');
                break;
        }
        foreach ($data as &$val) {
            $val['added_on'] = date('Y-m-d h:i:s', $val['added_on']);
        }
//        dump($data);die();
        $this->exportData($head,$data,$name);
    }
    //查看战队成员
    public function team($id, Posts $model)
    {
        $data = $model->useGlobalScope(false)->alias('p')
            ->leftJoin('post_team t','p.id = t.postid')
            ->leftJoin('user u','t.uid = u.id')
            ->whereIn('p.id',$id)
            ->where('t.id > 0')
            ->field('t.id,p.title,u.username,t.added_on')
            ->paginate(15);

        if (empty($data))
            return '暂无数据';

        $this->assign([
            'data'  => $data,
            'page'  => $data->render(),
            'total' => $data->total(),
            'classify' => $this->classify,
        ]);

        return $this->fetch();
    }
    //查看评论
    public function comment($id,Posts $posts)
    {
        $comment = $posts->useGlobalScope(false)->alias('p')
            ->leftJoin('comments c','p.id = c.postid')
            ->leftJoin('user u','c.uid = u.id')
            ->whereIn('p.id',$id)
            ->where('c.id','>',0)
            ->field('p.title,u.username,u.avatar,c.*')
            ->paginate(15);

//        $data = Comments::getComment(0,$comment);

        $this->assign('data',$comment);
        $this->assign('page',$comment->render());
        $this->assign('total',$comment->count());

        return $this->fetch();
    }

    public function del_comment($id)
    {
        return Comments::destroy($id) ? success('操作成功') : error();
    }
    //设置置顶
    public function top(int $id)
    {
        //包含软删除的数据
        $top_exist = Posts::withTrashed()->where('sort',1)->value('id');
        if (!empty($top_exist)) {
            $res = Posts::withTrashed()->where('sort',1)->setField('sort',0);
            if ($id!=$top_exist)
                $res = Posts::withTrashed()->where('id',$id)->setField('sort',1);
        } else {
            $res = Posts::withTrashed()->where('id',$id)->setField('sort',1);
        }

        return $res ? success('操作成功',URL_CURRENT):error();
    }
}