<?php

/**
 * 前台用户中心
 */

namespace app\index\controller;

use app\common\model\User;
use app\common\validate\UserValidate;
use app\index\model\Label;
use think\Exception;
use think\exception\PDOException;
use think\facade\Request;

class UserController extends Controller
{
    protected $authExcept = [];

    //个人中心首页
    public function index()
    {

        $this->assign([
            'user'=>$this->user
        ]);

        return $this->fetch();
    }

    //个人中心首页
    public function home()
    {
        $this->assign([
            'user'=>$this->user
        ]);

        return $this->fetch();
    }

    public function register()
    {
        //获取标签
        $label = Label::getLabel();

        $this->assign(compact($label));

        return $this->fetch();
    }

    public function doRegister()
    {
        $lid = Request::param('lid');
        $mobile = Request::param('mobile');
        $sex = Request::param('sex');
        $username = Request::param('username');
        $wx_qrcard = Request::param('wx_qrcard');

        if (empty($lid))
            $this->error('单位或岗位不能为空');

        $user = [
            'mobile' => $mobile,
            'sex' => $sex,
            'username' => $username,
            'nickname' => $this->user['nickname'],
            'password' => $mobile,
            'wx_qrcard' => $wx_qrcard,
            'user_level_id' => 0,
            'status' => 1,
        ];

        $validate = new UserValidate();

        try {
            if ($validate->check($user))
                User::where('id', $this->user['id'])->update($user);
            else
                $this->error($validate->getError());
        } catch (PDOException $e) {
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $label_user = [
            'uid' => $this->user['id'],
        ];
        foreach ($lid as $id) {
            $label_user[] = ['',];
        }

        $this->success('注册成功');
    }

}