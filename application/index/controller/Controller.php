<?php
/**
 * 前台基础控制器
 * @author yupoxiong<i@yufuping.com>
 */

namespace app\index\controller;

use app\common\model\User;
use app\index\traits\{IndexAuth, IndexTree, Wechat};

class Controller extends \think\Controller
{
    use IndexAuth, IndexAuth, Wechat;

    /**
     * 当前url
     * @var string
     */
    protected $url;

    /**
     * 当前用户ID
     * @var int
     */
    protected $uid = 0;

    /**
     * 当前用户
     * @var User
     */
    protected $user;

    /**
     * 无需验证权限的url
     * @var array
     */
    protected $authExcept = [];

    /**
     * 当前控制器
     * @var string|void
     */
    protected $controller;

    /**
     * 当前方法
     * @var string|void
     */
    private $action;

    protected function initialize()
    {
        $request = $this->request;
        $this->controller = $request->controller();
        $this->action = $request->action(true);
        if (!in_array($request->action(true), $this->authExcept)) {
            //仅使用微信授权登录
            if (config('app.only_wechat_login')) {
                if (!$this->isWebOauth())
                    $this->webOauth();
            } else {
                //使用微信授权或账号密码登录
                if (is_weixin() && !$this->isWebOauth()) {
                    $this->webOauth();
                } elseif (!$this->isLogin()) {
                    error('未登录', 'auth/login');
                } else if ($this->user->id !== 1 && !$this->isLogin()) {
                    error('无权限');
                }
            }

            $isUserReg = $this->controller == 'User' && $this->action == 'register';
            if (empty($this->user['mobile']) && !$isUserReg) {
                //没有手机号的跳转到注册页
                return redirect('User/register');
            }
        }

        if ((int)$request->param('check_auth') === 1) {
            success();
        }
    }

    protected function _result($data, $state = 0)
    {
        if (is_numeric($data)){
            $state = $data;
        }

        if ($state || is_string($data))
            $msg = $data;

        $response = json($data, $state);
        if ($this->request->has('callback')) {
            //以jsonp的格式返回
            $response = jsonp($data, $state);
        }

        return $response;
    }


}
