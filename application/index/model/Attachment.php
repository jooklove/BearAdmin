<?php

namespace app\index\model;

use think\Model;
use think\model\concern\SoftDelete;

class Attachment extends Model
{
    use SoftDelete;

    protected $autoWriteTimestamp = true;

}
