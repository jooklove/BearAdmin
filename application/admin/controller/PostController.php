<?php
namespace app\admin\controller;

use app\index\model\Label;
use app\index\model\Posts;
use think\Request;

class PostController extends Controller
{
    public $classify = [1=>'我有金点子',2=>'我有疑难杂症'];

	public function index(Posts $model, Request $request,$status=0)
	{
        $param = $request->param();
        $where = '';
        //二级审核员只显示本单位的发帖信息
        if ($this->auditor['level'] == 2) {
            $where = 'unit_lid='.$this->auditor['lid'];
        }
        $post = $model->useGlobalScope(false)
            ->scope('where', $param)
            ->where($where)
            ->paginate($this->admin['per_page'], false, ['query' => $request->get()]);
//        dump($post);die();
        //关键词，排序等赋值
        $this->assign($request->get());

        $this->assign([
            'data'  => $post,
            'page'  => $post->render(),
            'total' => $post->total(),
            'classify'  => $this->classify,
        ]);
		return $this->fetch();
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

    public function del($id)
    {
        return Label::destroy($id) ? success('操作成功', URL_RELOAD) : error();
    }

    //审核通过
    public function enable($id, Posts $model)
    {
        $status = $this->auditor['level'];
        if (empty($status))
            error('无权限', $this->request->isGet() ? null : URL_CURRENT);
        $result = $model->useGlobalScope(false)
            ->whereIn('id', $id)->update(['status' => $status]);
        return $result ? success('操作成功', URL_RELOAD) : error();
    }

    //审核不通过
    public function disable($id, Posts $model)
    {
        $status = $this->auditor['level'] * -1;
        if (empty($status))
            error('无权限', $this->request->isGet() ? null : URL_CURRENT);
        $result = $model->useGlobalScope(false)
            ->whereIn('id', $id)->update(['status' => $status]);
        return $result ? success('操作成功', URL_RELOAD) : error();
    }
}