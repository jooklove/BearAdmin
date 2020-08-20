<?php

namespace app\admin\controller;

use app\admin\model\AdminAuditor;
use app\admin\model\Mp;
use app\admin\model\Templatemsg;
use think\Request;

class MpController extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $mp = Mp::get(1);

        $this->assign('data',$mp);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save($id, Request $request, Mp $model)
    {
        $mp = $request->param();
//        dump($mp);die();
        if (!$id) {
            $res = $model->save($mp);
        } else {
            $res = $model->update($mp);
        }
        return $res ? success('操作成功', URL_CURRENT) : error();
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function template()
    {
        $temp = Templatemsg::get(1);

        $this->assign('data',$temp);
        return $this->fetch();
    }

    public function templateSave($id, Request $request, Templatemsg $model)
    {
        $mp = $request->param();
//        dump($mp);die();
        if (!$id) {
            $res = $model->save($mp);
        } else {
            $res = $model->update($mp);
        }
        return $res ? success('操作成功', URL_CURRENT) : error();
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

}
