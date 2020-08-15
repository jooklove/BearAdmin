<?php
namespace app\index\controller;

//微信相关接口
use app\common\model\Mp;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;

class WechatController extends Controller
{
    /**
     * @var \EasyWeChat\OfficialAccount\Application
     */
    private $app;

    public function __construct()
	{
        $config = config('wechat');
        if (empty($config))
            $config = Mp::get();
		$this->app = Factory::officialAccount($config);
	}
	
	public function index()
	{
        try {
            $response = $this->app->server->serve();
            // 将响应输出
            $response->send();exit; // Laravel 里请使用：return $response;
        } catch (BadRequestException $e) {
            $e->getPrevious();
        } catch (InvalidArgumentException $e) {
            $e->getPrevious();
        } catch (InvalidConfigException $e) {
            $e->getPrevious();
        }
	}
	
}

/*公众号表：
CREATE TABLE IF NOT EXISTS `rh_mp` (
`id` int(10) unsigned NOT NULL COMMENT '自增ID',
`user_id` int(10) NOT NULL COMMENT '用户ID',
`name` varchar(50) NOT NULL COMMENT '公众号名称',
`appid` varchar(50) DEFAULT NULL COMMENT 'AppId',
`appsecret` varchar(50) DEFAULT NULL COMMENT 'AppSecret',
`origin_id` varchar(50) NOT NULL COMMENT '公众号原始ID',
`type` int(1) NOT NULL DEFAULT '0' COMMENT '公众号类型（1：普通订阅号；2：认证订阅号；3：普通服务号；4：认证服务号',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态（0：禁用，1：正常，2：审核中）',
`valid_token` varchar(40) DEFAULT NULL COMMENT '接口验证Token',
`valid_status` tinyint(1) NOT NULL COMMENT '1已接入；0未接入',
`token` varchar(50) DEFAULT NULL COMMENT '公众号标识',
`encodingaeskey` varchar(50) DEFAULT NULL COMMENT '消息加解密秘钥',
`mp_number` varchar(50) DEFAULT NULL COMMENT '微信号',
`desc` text COMMENT '描述',
`logo` varchar(255) DEFAULT NULL COMMENT 'logo',
`qrcode` varchar(255) DEFAULT NULL COMMENT '二维码',
`create_time` int(10) NOT NULL COMMENT '创建时间',
`login_name` varchar(50) DEFAULT NULL COMMENT '公众号登录名',
`is_use` tinyint(1) NOT NULL DEFAULT '0' COMMENT '当前使用'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='公众号表';*/