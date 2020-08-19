<?php

/**
 * 前台用户中心
 */

namespace app\index\controller;

use app\common\model\User;
use app\common\validate\UserValidate;
use app\index\model\Label;
use app\index\model\LabelUser;
use app\index\model\Posts;
use app\index\model\PostTeam;
use think\Exception;
use think\exception\PDOException;
use think\facade\Request;

class UserController extends Controller
{
    protected $authExcept = [];

    //个人中心首页
    public function index()
    {
        return $this->home();
    }

    //个人中心首页
    public function home()
    {
        $uid = Request::param('uid');
        if (empty($uid)) {
            $user = $this->user;
            $oneself = 1;
        }
        else {
            $user = User::get($uid);
            $oneself = 0;
        }

        $uid = $user['id'];
        //获取发过的帖子
        $post = Posts::alias('p')
            ->leftJoin('label_post l', 'p.id = l.postid')
            ->where("l.lid_type='".Label::MAJOR."'")
            ->where("p.uid=$uid")
            ->limit(6)
            ->select();

        //已发布帖子数
        $issuePostNum = Posts::where("uid", $uid)->count();
        //加入战队帖子数
        $teamPostNum = PostTeam::where("uid", $uid)->count();

        $this->assign(compact($user,$post,$issuePostNum,$teamPostNum,$oneself));

        return $this->fetch();
    }

    public function edit()
    {
        return $this->register();
    }

    public function register()
    {
        //获取标签
        $label = Label::getLabel(Label::UNIT);

        $this->assign(compact($label));

        return $this->fetch();
    }

    public function doRegister()
    {
        $lid = Request::param('lid');
        $mobile = Request::param('mobile');
        $sex = Request::param('sex');
        $job = Request::param('job');
        $username = Request::param('username');
        $wx_qrcard = Request::param('wx_qrcard');

        if (empty($lid))
            $this->error('单位不能为空');

        $user = [
            'mobile' => $mobile,
            'sex' => $sex,
            'job' => $job,
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

        $label_user = [];
        foreach ($lid as $key=>$id) {
            $label_user[$key]['uid'] = $this->user['id'];
            $label_user[$key]['lid'] = $id;
        }
        LabelUser::create($label_user);

        $this->success('注册成功');
    }

}