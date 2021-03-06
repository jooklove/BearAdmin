<?php
/**
 * 用户验证器
 */

namespace app\common\validate;

class UserValidate extends Validate
{
    protected $rule = [
        'user_level_id|用户等级' => 'require',
        'username|用户名'        => 'require',
        'mobile|手机号'          => 'require|mobile',
        'nickname|昵称'          => 'require',
        'password|密码'          => 'require',
        'unit_lid|单位'          => 'require',
        'job_lid|岗位'           => 'require',
        'status|是否启用'         => 'require',
        'wx_qrcard|微信二维码名片' => 'require',
    ];

    protected $message = [
        'user_level_id.require' => '用户等级不能为空',
        'username.require'      => '用户名不能为空',
        'mobile.require'        => '手机号不能为空',
        'nickname.require'      => '昵称不能为空',
        'password.require'      => '密码不能为空',
        'job.require'           => '岗位不能为空',
        'status.require'        => '是否启用不能为空',
        'wx_qrcard.require'     => '微信二维码名片不能为空',
    ];

    protected $scene = [
        'add'       => ['user_level_id', 'username', 'mobile', 'nickname', 'password', 'status',],
        'edit'      => ['user_level_id', 'username', 'mobile', 'nickname', 'password', 'status',],
        'api_login' => ['username', 'password'],

    ];


}
