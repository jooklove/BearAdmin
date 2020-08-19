<?php

namespace app\index\model;

use think\Model;

class Label extends Model
{
    const UNIT  = 'unit';
    const MAJOR = 'major';

    protected $pk = 'lid';

    public $softDelete = false;
    //不能删除的ID
    public $noDeletionId = [1, 4];

    public static function getLabel($type='')
    {
        $where = '';
        if (!empty($type))
            $where = "type='$type'";

        $label = self::where($where)->select()->toArray();

        if (empty($label))
            return [];

        foreach ($label as $top) {
            $labelTree[$top['lid']] = $top;
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

        return $labelTree;
    }
}
