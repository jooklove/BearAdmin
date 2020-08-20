<?php

namespace app\admin\controller;

use app\admin\model\AdminUser;
use app\admin\model\AdminAuditor;
use app\index\model\Label;
use think\facade\Request;
use think\model\Collection;

class AuditorController extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     * @throws \think\Exception\DbException
     */
    public function index()
    {
        $auditor = AdminAuditor::all();
        $admin_user = AdminUser::field('id,username')->all()->toArray();
        $admin_user = array_column($admin_user,null,'id');
        $label = Label::getLabel(Label::UNIT,false);

        foreach ($auditor as &$item) {
            $item['username'] = $admin_user[$item['admin_uid']]['username'];
            $item['label'] = $item['lid'] ?$label[$item['lid']]['full_name'] : '全部';
        }
        unset($item);

        $this->assign('label', $label);
        $this->assign('data', $auditor);
        $this->assign('admin_user', $admin_user);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        $admin_user = AdminUser::all();
        $label = Label::getLabel(Label::UNIT,false);

        $this->assign('label', $label);
        $this->assign('admin_user', $admin_user);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        $param = Request::param();
        if ($id) {
            $res = AdminAuditor::update($param);
        } else {
            $res = AdminAuditor::create($param);
        }

        return $res ? success('操作成功', URL_BACK) : error();
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $auditor = AdminAuditor::get($id);
        $admin_user = AdminUser::all();
        $label = Label::getLabel(Label::UNIT, false);

        $this->assign('label', $label);
        $this->assign('data', $auditor);
        $this->assign('admin_user', $admin_user);
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
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        return AdminAuditor::destroy($id) ? success('操作成功', URL_BACK) : error();
    }
}
