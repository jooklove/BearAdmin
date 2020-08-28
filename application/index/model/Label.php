<?php

namespace app\index\model;

use app\common\model\User;
use think\Model;

class Label extends Model
{
    const UNIT  = 'unit';
    const MAJOR = 'major';
    const JOB = 'job';

    protected $pk = 'lid';

    public $softDelete = false;
    //不能删除的ID
    public $noDeletionId = [1, 4];

    /**
     * @param string $type //标签类型【unit,major,job】
     * @param bool $group //是否将父级和子级进行分组
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLabel($type='', $group = true)
    {
        $where = '';
        $model = self::where($where);
        if (!empty($type))
            $model->where('type',$type);// = "type='$type'";
        //不分组就只取子级标签
        if (!$group)
            $model->where('pid','>',0);

        $label = $model->select()->toArray();

        if (empty($label))
            return [];

        foreach ($label as $top) {
            $labelTree[$top['lid']] = $top;
            if ($group) {
                if ($top['pid'] == 0) {
                    $labelTree['top'][] = $top;

                    foreach ($label as $sub) {
                        if (!$sub['pid'])
                            continue;
                        if ($top['lid'] == $sub['pid']) {
                            $labelTree['sub'][$top['lid']][] = $sub;
                        }
                    }

                }
            }
        }

        return $labelTree;
    }

    public static function getSubLabel($pid,$type='')
    {
        if (!empty($type))
            $where = "`pid`>0 AND `type`='$type'";
        else
            $where = "`pid`=$pid";
        $sub = self::where($where)->select()->toArray();
        return $sub ?:[];
    }

    public static function getTopLabel()
    {
        $top = self::where('pid',0)->select();
        return $top ?:[];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
