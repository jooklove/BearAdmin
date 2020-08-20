<?php


namespace app\index\traits;


use app\admin\model\AdminAuditor;
use app\admin\model\Mp;
use app\admin\model\Templatemsg;
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

    public function checkSignature()
    {
        try {
            $response = mp()->server->serve();
            // 将响应输出
            $response->send(); // Laravel 里请使用：return $response;
            // 更新公众号状态为已接入
            Mp::where('id',1)->update(['valid_status'=>1]);
            die();
        } catch (BadRequestException $e) {
            return $e->getMessage();
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        } catch (InvalidConfigException $e) {
            return $e->getMessage();
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
        if (Request::has('code')) {
            // 获取 OAuth 授权结果用户信息
            $oauth_user = mp()->oauth->user();
            // 保存用户授权信息
            $this->user = User::addWxUser($oauth_user);

            $openid = $oauth_user->getId();

            Session::set('wechat_user', $oauth_user->toArray());
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

    //发送模板消息
    public function senTempMsg($data, $lid)
    {
        $temp = Templatemsg::get(1);
        if ($temp['status']==0 || empty($temp['tempid']))
            return null;
        //获取需要发送模板消息的审核员openid
        $send_openid = AdminAuditor::getTempMsgOpenid($lid);

        $tplData = [
            'touser' => 'openid',
            'template_id' => $temp['tempid'],
            'url' => url('admin/post/index'),
            'data' => [
                'first' => "有新的用户发布帖子，请及时确认",
                'keyword1' => $data['username'],
                'keyword2' => $data['mobile'],
                'keyword3' => date("Y-m-d H:i", time()),
                'keyword4' => $data['title'],
                'remark' => '点击处理客户提交的表单',
            ],
        ];
        // $content = mb_substr($content,0,32);
//        $tplData = array();
//        $tplData["first"] = "有新的用户发布帖子，请及时确认";
//        $tplData["keyword1"] = $data['user_name'];
//        $tplData["keyword2"] = $data['mobile'];
//        $tplData["keyword3"] = date("Y-m-d H:i", time());
//        $tplData["keyword4"] = $data['content'];
//        $tplData["remark"] = "点击处理客户提交的表单";
//        $tplData["href"] = url('admin/post');
        // print_r($tplData);die();
        foreach($send_openid as $openid){
            $tplData["touser"] = $openid['wecha_id'];
            $tplResult = mp()->template_message->send($tplData);
        }
    }
}