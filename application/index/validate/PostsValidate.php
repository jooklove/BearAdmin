<?php

namespace app\index\validate;

use think\Validate;

class PostsValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'cid|分类id' => 'require',
        'title|标题'        => 'require',
        'content|内容'          => 'require',
//        'images|图片'          => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */
    protected $message = [
        'cid.require' => '分类不能为空',
        'title.require'      => '标题不能为空',
        'content.require'      => '内容不能为空',
    ];
}
