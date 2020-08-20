<?php

namespace app\admin\model;

use app\common\model\User;
use think\Model;

class AdminAuditor extends Model
{
    protected $pk = 'admin_uid';

    //获取需要发送模板消息的审核员openid
    public static function getTempMsgOpenid($lid)
    {
        $uid = self::where('lid',$lid)
                ->whereOr('level',1)
                ->where('is_tempmsg',1)
                ->column('admin_uid');

        return User::where('id', 'in', $uid)->column('openid');
    }
}
