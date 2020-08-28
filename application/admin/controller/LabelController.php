<?php

namespace app\admin\controller;

use app\index\model\Label;
use think\facade\Request;

class LabelController extends Controller
{
    /**
     * 显示资源列表
     *
     * @param $pid
     * @return \think\Response
     */
    public function index($pid=0)
    {
//        $pid = Request::param('pid');
//        $label = Label::getLabel();

        if ($pid) {
            $list = Label::getSubLabel($pid);
        } else {
            $list = Label::getTopLabel();
        }
        $this->assign('pid', $pid);
        $this->assign('label', $list);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add($pid=0)
    {
        $label = [];
        if ($pid)
            $label = Label::where('lid',$pid)->field('name as parent_name,type')->find();
        $this->assign('pid', $pid);
        $this->assign('data', $label);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param $id
     * @param $pid
     * @param Request $request
     * @return void
     */
    public function save($lid, $pid, Request $request)
    {
        $name = $request::param('name');
        $full_name = $request::param('full_name');
        $type = $request::param('type');

        if ($lid) {
            $update = [
                'name' => $name,
                'full_name' => $full_name,
                'type' => $type,
            ];
            $res = Label::where('lid',$lid)->update($update);
        } else {
            $add = [
                'pid' => $pid,
                'name' => $name,
                'full_name' => $full_name,
                'type' => $type,
            ];
            $res = Label::create($add);
        }
        return $res ? success('操作成功', URL_BACK) : error();
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @param $pid
     * @return \think\Response
     */
    public function edit($id)
    {
        $label = Label::get($id);
        if ($label['pid'])
            $label['parent_name'] = Label::where('lid',$label['pid'])->value('name');

        $this->assign('data', $label);
        $this->assign('pid', $label['pid']);
        return $this->fetch('add');
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @param Label $model
     * @return \think\Response
     */
    public function delete($id, Label $model)
    {
        if (empty($id))
            return error('请选择要操作的数据');

        //判断是否有子标签
        $have_son = $model->whereIn('pid', $id)->find();
        if ($have_son) {
            return error('有子菜单不可删除！');
        }

        if (count($model->noDeletionId) > 0) {
            if (is_array($id)) {
                if (array_intersect($model->noDeletionId, $id)) {
                    return error('ID为' . implode(',', $model->noDeletionId) . '的数据无法删除');
                }
            } else if (in_array($id, $model->noDeletionId)) {
                return error('ID为' . $id . '的数据无法删除');
            }
        }

        if ($model->softDelete) {
            $result = $model->whereIn('id', $id)->useSoftDelete('delete_time', time())->delete();
        } else {
            $result = $model->whereIn('id', $id)->delete();
        }

        return $result ? success('操作成功', URL_RELOAD) : error();
    }

}
