<?php

/**
 * 前台用户中心
 */

namespace app\index\controller;

use app\common\model\User;
use app\common\model\UserLevel;
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

    /**
     * 无需注册的url
     * @var array
     */
    protected $regExcept = ['register','do_register'];

    //个人中心首页
    public function index()
    {
        return $this->home();
    }

    //个人中心首页
    public function home(Posts $posts)
    {
        $uid = Request::param('uid');
        if (empty($uid)) {
            $user = $this->user;
            $oneself = 1;
        } else {
            $user = User::get($uid);
            $oneself = 0;
        }

        if (empty($user))
            $this->error('用户不存在或被删除');

        $uid = $user['id'];
        //用户等级
        $level = UserLevel::get($user['user_level_id']);
//        dump($level);die();
        //获取发过的帖子
        $post = Posts::where("uid",$uid)->select()->toArray();
        //获取收藏的帖子
        $teamId = PostTeam::where('uid',$uid)->column('postid');
        $postTeam = $posts->whereIn('id',$teamId)->select();
        /*$postTeam = $posts->alias('p')
            ->leftJoin('post_team t', 't.postid = p.id')
            ->where("t.uid=$uid")
            ->select()
            ->toArray();*/

        //已发布帖子数
        $issuePostNum = count($post);//Posts::where("uid", $uid)->count();
        //加入战队帖子数
        $teamPostNum = count($postTeam);//PostTeam::where("uid", $uid)->count();

        $this->assign([
            'user'  => $user, //用户信息
            'post'  => $post, //发布的帖子
            'level' => $level, //用户等级
            'postTeam' => $postTeam, //加入的帖子
            'issuePostNum' => $issuePostNum,//发布的帖子数
            'teamPostNum' => $teamPostNum,//战队帖子数
            'oneself' => $oneself,//是否为自己的主页
            'label' => Label::getLabel(),//标签
        ]);

        return $this->fetch();
    }

    public function edit()
    {
        return $this->register();
    }

    public function register()
    {
        //获取标签
        $unit = Label::getLabel(Label::UNIT,false);
        $job = Label::getLabel(Label::JOB,false);

        $this->assign('unit',$unit);
        $this->assign('job',$job);
        $this->assign('nickname',$this->user['nickname']);
        $this->assign('user',$this->user);

        return $this->fetch('register');
    }

    public function do_register()
    {
        $unit_lid = Request::param('unit_lid');
        $mobile = Request::param('mobile');
        $sex = Request::param('sex');
        $job_lid = Request::param('job_lid');
        $username = Request::param('username');
        $wx_qrcard = Request::param('wx_qrcard');

        $user = [
            'mobile' => $mobile,    //手机号
            'sex' => $sex,          //性别
            'job_lid' => $job_lid,          //岗位
            'unit_lid' => $unit_lid,     //单位标签id
            'username' => $username,//姓名
            'nickname' => $this->user['nickname'],
            'password' => $mobile,
            'wx_qrcard' => $wx_qrcard,//二维码
            'user_level_id' => 1,
            'status' => 1,
        ];

        $validate = new UserValidate();

        try {
            if ($validate->check($user))
                User::where('id', $this->user['id'])->update($user);
            else
                return $this->_result($validate->getError(),1);
        } catch (PDOException $e) {
            return $this->_result($e->getMessage(),1);
        } catch (Exception $e) {
            return $this->_result($e->getMessage(),1);
        }

        $label_user[] = ['uid'=>$this->user['id'],'lid'=>$unit_lid];
        $label_user[] = ['uid'=>$this->user['id'],'lid'=>$job_lid];
        LabelUser::create($label_user);

        return $this->_result('注册成功');
    }

}