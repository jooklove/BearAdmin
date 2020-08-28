<?php
/**
 * 前台基础控制器
 * @author yupoxiong<i@yufuping.com>
 */

namespace app\index\controller;

use app\common\model\User;
use app\index\model\Attachment;
use app\index\traits\{IndexAuth, IndexTree, Wechat};
use think\facade\Session;
use tools\SafeCookie;

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
    protected $user = [];

    /**
     * 无需验证权限的url
     * @var array
     */
    protected $authExcept = ['testLogin','testupload'];

    /**
     * 无需注册的url
     * @var array
     */
    protected $regExcept = ['up_doc','up_img'];

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
        //主动发起登录
        if ($request->param('login') == 1)
            $this->authExcept = [];
        if (!in_array($this->action, $this->authExcept)) {
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

            if (empty($this->user['mobile']) && !in_array($this->action,$this->regExcept)) {
                //没有手机号的跳转到注册页
                error('请先注册','User/register');
//                redirect();
                die();
            }
        }

        if ((int)$request->param('check_auth') === 1) {
            success();
        }
    }

    protected function _result($data, $state=0, $msg='', $code=200)
    {
        if (is_numeric($data)){
            $state = $data;
        }

        if ($state || is_string($data))
            $msg = $data;

        $result = [
            'error' => $state,
            'msg' => $msg,
            'data' => $data,
        ];
        $response = json($result, $code);
        if ($this->request->has('callback')) {
            //以jsonp的格式返回
            $response = jsonp($data, $state);
        }
//        $this->result($data, $state, $msg);
        return $response;
    }

    //文档上传
    public function up_doc()
    {
        return $this->upload('doc',['size'=>5242880,'ext'=>'pdf,doc'], './uploads/doc');
    }

    //图片上传
    public function up_img()
    {
        return $this->upload('image',['size'=>3145728,'ext'=>'jpg,jpeg,png,gif'], './uploads/img');
    }

    protected function upload($name, $validate, $path)
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file($name);
//        dump($file);die();
        // 移动到框架应用根目录/uploads/ 目录下  3145728 = 3M
        $info = $file->validate($validate)->move($path);
        //        $obj = new \ReflectionObject($info);
//        var_dump($obj->getMethods());
//        dump($info);die();
        if($info){
            //文件上传原始信息
            $originInfo = $info->getInfo();
            // 成功上传后 获取上传信息
            // 输出 jpg  后缀
            $extension = $info->getExtension();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            $save_name = $info->getFilename();
            // 原文件名
            $original_name = $originInfo['name'];
            // size
            $size = $originInfo['size'];
            // image/jpeg
            $mime = $info->getMime();
            // 输出 file
//            $type = $info->getType();
            // 输出 ../uploads\20200818
//            $path = $info->getPath();
            // ../uploads\20200818\99616cdd9e7d23038e9a03264077932e.jpg
            $pathname = $info->getPathname();
            // 完整链接
            $pathname = substr($pathname,1);
            $url = $this->request->domain() . strtr($pathname,['\\'=>'/']);
            // E:\phpstudy_pro\WWW\league\uploads\20200818\99616cdd9e7d23038e9a03264077932e.jpg
            $save_path = $info->getRealPath();
            // hash
            $md5  = $info->md5();
            $sha1 = $info->sha1();

            $attachment = [
                'user_id' => $this->uid,
                'original_name' => $original_name,
                'save_name' => $save_name,
                'save_path' => $save_path,
                'extension' => $extension,
                'url' => $url,
                'mime' => $mime,
                'size' => $size,
                'md5' => $md5,
                'sha1' => $sha1,
                'create_time' => time(),
                'update_time' => time(),
            ];

            $attach_id = (new Attachment)->insertGetId($attachment);
            return $this->_result(['url'=>$url,'attach_id'=>$attach_id],0,'上传成功');
        } else {
            // 上传失败获取错误信息
            return $this->_result($file->getError(),1,'上传失败');
        }
    }

    //测试登录
    public function testLogin()
    {
        $openid = 'oMtW_5nt2tjLyy_tGaM1MxsFdM-k';
        Session::set(self::$openid, $openid);

        $sign = User::getSignStr($openid);
        Session::set(self::$sign, $sign);

        SafeCookie::set(self::$openid, $openid);
        SafeCookie::set(self::$sign, $sign);

        $this->success('登录成功');
    }
    //测试上传
    public function testupload()
    {
        return $this->fetch();
    }
    //测试模板消息
    public function testtemp()
    {
        $tempmsg = [
            'username' => $this->user['username'],
            'mobile' => '15569869888',
            'title' => '某科学的超电磁炮',
        ];
        //发送模板消息通知审核员
        $this->senTempMsg($tempmsg, 1);
    }

}
