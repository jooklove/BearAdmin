<?php
/**
 * 用户模型
 */

namespace app\common\model;

use app\index\model\Label;
use think\model\concern\SoftDelete;

class User extends Model
{
    use SoftDelete;
    public $softDelete = true;
    protected $name = 'user';
    protected $autoWriteTimestamp = true;

    //可搜索字段
    protected $searchField = ['username', 'mobile', 'nickname',];

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

    //绑定帖子模型
    /*public function posts()
    {
        return $this->hasMany('posts','uid','id');
    }*/

    //绑定标签模型
    public function label()
    {
        return $this->hasOne(Label::class,'lid','unit_lid');
    }

    //是否启用获取器
    public function getStatusTextAttr($value, $data)
    {
        return self::BOOLEAN_TEXT[$data['status']];
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

    public static function getUserOpenid($openid)
    {
        return self::where('openid',$openid)
            ->field('id,openid,username,nickname,mobile,wx_qrcard')
            ->find();
    }

    public static function addWxUser(\Overtrue\Socialite\User $wechat_user)
    {
        //todo 保存用户的授权信息
        $user = self::where('openid',$wechat_user->getId())->find();
        if (empty($user)) {
            $user = [
                'avatar' => $wechat_user->getAvatar(),
                'nickname' => $wechat_user->getName(),
                'openid' => $wechat_user->getId(),
                'create_time' => time(),
                'password' => '123456',
            ];

            $user['id'] = self::create($user)->getLastInsID();
            unset($user['password'],$user['create_time']);
        } else {
            $update = ['password' => '123456'];
            if (!empty($wechat_user->getName()) && $user['nickname'] != $wechat_user->getName()) {
                $update['nickname'] = $wechat_user->getName();
            }
            if (!empty($wechat_user->getAvatar()) && $user['avatar'] != $wechat_user->getAvatar())
                $update['avatar'] = $wechat_user->getAvatar();

            if (!empty($update))
                self::where('id',$user['id'])->update($update);
        }
        return $user;
    }

    public static function passNum($uid,$inc=true)
    {
        //增加用户发帖通过数
        if ($inc)
            User::where('id', 'in', $uid)->setInc('pass_num');
        else
            User::where('id', 'in', $uid)->setDec('pass_num');
        //更新用户等级
        self::upgrade($uid);
    }
    //更新用户等级
    public static function upgrade($uid)
    {
        $pass_num = self::where('id','in',$uid)->column('id,pass_num','id');
        $level = UserLevel::all();
        foreach ($pass_num as $item) {
            foreach ($level as $lv) {
                if ($item['pass_num']>=$lv['level']) {
                    self::where('id',$item['id'])->setField('user_level_id',$lv['id']);
                    break;
                }
            }
        }
    }

}
