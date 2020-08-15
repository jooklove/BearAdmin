<?php
/**
 * 后台操作日志模型
 * @author yupoxiong<i@yufuping.com>
 */

namespace app\admin\model;

use think\model\relation\BelongsTo;
use think\model\relation\HasOne;

class AdminLog extends Model
{
    protected $name = 'admin_log';

    public $softDelete = false;

    public $methodText = [
        1 => 'GET',
        2 => 'POST',
        3 => 'PUT',
        4 => 'DELETE',
    ];

    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    protected $searchField = [
        'name',
        'url',
        'log_ip'
    ];

    protected $whereField = [
        'admin_user_id'
    ];

    protected $timeField = [
        'create_time'
    ];

    //关联用户
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    //关联详情
    public function adminLogData(): HasOne
    {
        return $this->hasOne(AdminLogData::class);
    }

    //登录日志
    public static function loginLog()
    {
        $loginLog = self::where('name','=','登录')
            ->limit(10)
            ->order('id','desc')
            ->field('admin_user_id,log_ip,create_time')
            ->select();
        $adminUser = AdminUser::field('id,nickname')->all();
        foreach ($loginLog as &$log) {
            foreach ($adminUser as $user) {
                if ($log['admin_user_id'] == $user['id']) {
                    $log['name'] = $user['nickname'];
                }
            }
        }
        return $loginLog;
    }

}
