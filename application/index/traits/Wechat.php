<?php


namespace app\index\traits;


use app\common\model\User;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use think\facade\Session;
use think\facade\Request;
use tools\SafeCookie;

trait Wechat
{
    public static $openid = 'openid';
    public static $sign = 'openid_sign';

    public function index()
    {
        try {
            $response = mp()->server->serve();
            // 将响应输出
            $response->send();exit; // Laravel 里请使用：return $response;
        } catch (BadRequestException $e) {
            $e->getPrevious();
        } catch (InvalidArgumentException $e) {
            $e->getPrevious();
        } catch (InvalidConfigException $e) {
            $e->getPrevious();
        }
    }

    public function isWebOauth()
    {
        $openid = Session::get(self::$openid);
        $user       = false;
        $this->user = &$user;
        if (empty($openid)) {
            if (SafeCookie::has(self::$user_id) && SafeCookie::has(self::$sign)) {
                $openid  = SafeCookie::get(self::$openid);
                $sign    = SafeCookie::get(self::$sign);
                $user    = User::where('openid',$openid)->find();
                if ($user && User::getSignStr($openid) === $sign) {
                    Session::set(self::$openid, $openid);
                    Session::set(self::$sign, $sign);
                    return true;
                }
            }
            return false;
        }

        $user = User::where('openid',$openid)->find();
        if(!$user) {
            return false;
        }
        $this->uid = $user->id;

        return Session::get(self::$sign) === User::getSignStr($openid);
    }

    public function webOauth()
    {
//        dump(request());
        if (Request::has('code')) {
            // 获取 OAuth 授权结果用户信息
            $user = mp()->oauth->user();
            //新增微信用户
            $this->user = User::addWxUser($user);

            $openid = $user->getId();

            Session::set('wechat_user', $user->toArray());
            Session::set(self::$openid, $openid);

            $sign = User::getSignStr($openid);
            Session::set(self::$sign, $sign);

            SafeCookie::set(self::$openid, $openid);
            SafeCookie::set(self::$sign, $sign);
        } else {
            //跳转授权
            $response = mp()->oauth->scopes(['snsapi_userinfo'])->redirect();
            $response->send();
        }
        return false;
    }
}