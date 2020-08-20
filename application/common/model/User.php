<?php
/**
 * 用户模型
 */

namespace app\common\model;

use think\model\concern\SoftDelete;

class User extends Model
{
    use SoftDelete;
    public $softDelete = true;
    protected $name = 'user';
    protected $autoWriteTimestamp = true;

    //可搜索字段
    protected $searchField = ['username', 'mobile', 'nickname',];

    //绑定帖子模型
    public function posts()
    {
        return $this->hasMany('posts','uid','id');
    }

    //是否启用获取器
    public function getStatusTextAttr($value, $data)
    {
        return self::BOOLEAN_TEXT[$data['status']];
    }

    public static function init()
    {
        //添加自动加密密码
        self::event('before_insert', static function ($data) {
            $data->password = base64_encode(password_hash($data->password, 1));
        });

        //修改密码自动加密
        self::event('before_update', function ($data) {
            $old = (new static())::get($data->id);
            if ($data->password !== $old->password) {
                $data->password = base64_encode(password_hash($data->password, 1));
            }
        });
    }

    //关联用户等级
    public function userLevel(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(UserLevel::class);
    }


    /**
     * 用户登录
     * @param $param
     * @return mixed
     * @throws \Exception
     */
    public static function login($param)
    {
        $username = $param['username'];
        $password = $param['password'];
        $user     = self::get(['username' => $username]);
        if (!$user) {
            exception('用户不存在');
        }

        if (!password_verify($password, base64_decode($user->password))) {
            exception('密码错误');
        }

        if ((int)$user->status !== 1) {
            exception('用户被冻结');
        }
        return $user;
    }

    //加密字符串，用在登录的时候加密处理
    protected function getSignStrAttr($value, $data)
    {
        $ua = request()->header('user-agent');
        return sha1($data['id'] . $data['username'] . $ua);
    }

    //加密字符串，用在登录的时候加密处理
    public static function getSignStr($openid)
    {
        $ua = request()->header('user-agent');
        return sha1($openid . $ua);
    }


    public static function addWxUser(\Overtrue\Socialite\User $wechat_user)
    {
        //todo 保存用户的授权信息
        $user = self::where('openid',$wechat_user->getId())->find();
        if (empty($user)) {
            $user = [
                'avatar' => $wechat_user->getAvatar(),
                'username' => $wechat_user->getName(),
                'nickname' => $wechat_user->getName(),
                'openid' => $wechat_user->getId(),
                'create_time' => time(),
            ];

            self::create($user);
        } else {
            $update = [];
            if (!empty($wechat_user->getName()) && $user['nickname'] != $wechat_user->getName()) {
                $update['nickname'] = $wechat_user->getName();
            }
            if (!empty($wechat_user->getAvatar()) && $user['avatar'] != $wechat_user->getAvatar())
                $update['avatar'] = $wechat_user->getAvatar();

            if (!empty($update))
                self::update($update);
        }
        return $user;
    }

    public static function addPassNum($uid)
    {
        //增加用户发帖通过数
        User::where('id', 'in', $uid)->setInc('pass_num');
//        foreach ($uid as $id) {
//            User::where('id', $id)->setInc('pass_num');
//            self::upgrade($id);
//        }
    }

    public static function upgrade()
    {

    }

}
