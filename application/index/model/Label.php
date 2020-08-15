<?php

namespace app\index\model;

use think\Model;

class Label extends Model
{
    public static function getLabel()
    {
        $label = self::all();

        if (empty($label))
            return [];

        $labelTree = [];
        foreach ($label as $top) {
            if ($top['pid'] == 0) {
                $labelTree['top'][] = $top;

                foreach ($label as $sub) {
                    if (!$sub['pid'])
                        continue;
                    if ($top['id'] == $sub['pid']) {
                        $labelTree['sub'][$top['id']][] = $sub;
                    }
                }

            }
        }

        return $labelTree;
    }
}
