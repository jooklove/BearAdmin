<?php

namespace app\admin\model;


class Mp extends Model
{
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'create_time';

    //状态获取器
    public function getValidStatusAttr($value)
    {
        $status = [0=>'未接入',-1=>'已接入'];
        return $status[$value];
    }
}
