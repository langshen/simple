<?php
namespace Table\System;

defined('APP_NAME') or die('404 Not Found');
class Verify extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'system_verify';
    //表备注
    public $strComment = '系统验证码，现只用邮箱的验证码';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'user_id'=>['int',10,'用户'],
		'send_type'=>['tinyint',3,'1邮箱或2手机'],
		'tpl_key'=>['varchar',50,'发送模块的KEY，对应tpl的KEY'],
		'rad_code'=>['varchar',32,'随机码'],
		'send_to'=>['varchar',80,'接受人'],
		'valid_time'=>['tinyint',3,'有效期，默认为5分钟'],
		'status'=>['tinyint',3,'1为未使用，2为已使用'],
		'ip'=>['varchar',16,'使用人IP'],
		'add_time'=>['datetime',0,'活加时间'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','自增ID','0'],
		'user_id'=>['number','用户','0'],
		'send_type'=>['number','1邮箱或2手机','0'],
		'tpl_key'=>['length:1,50','发送模块的KEY，对应tpl的KEY',''],
		'rad_code'=>['length:1,32','随机码',''],
		'send_to'=>['length:1,80','接受人',''],
		'valid_time'=>['number','有效期，默认为5分钟','5'],
		'status'=>['number','1为未使用，2为已使用','1'],
		'ip'=>['length:1,16','使用人IP',''],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null','自增ID'],
		'user_id'=>['int','10','0','unsigned','false','false','false','0','用户'],
		'send_type'=>['tinyint','3','0','unsigned','false','false','false','0','1邮箱或2手机'],
		'tpl_key'=>['varchar','50','0','utf8_general_ci','false','false','false','Empty String','发送模块的KEY，对应tpl的KEY'],
		'rad_code'=>['varchar','32','0','utf8_general_ci','false','false','false','Empty String','随机码'],
		'send_to'=>['varchar','80','0','utf8_general_ci','false','false','false','Empty String','接受人'],
		'valid_time'=>['tinyint','3','0','unsigned','false','false','false','5','有效期，默认为5分钟'],
		'status'=>['tinyint','3','0','unsigned','false','false','false','1','1为未使用，2为已使用'],
		'ip'=>['varchar','16','0','utf8_general_ci','false','false','false','Empty String','使用人IP'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','活加时间'],
		'active_time'=>['datetime','0','0','','false','false','false','0000-00-00 00:00:00','激活时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."system_verify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户',
  `send_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1邮箱或2手机',
  `tpl_key` varchar(50) NOT NULL DEFAULT '' COMMENT '发送模块的KEY，对应tpl的KEY',
  `rad_code` varchar(32) NOT NULL DEFAULT '' COMMENT '随机码',
  `send_to` varchar(80) NOT NULL DEFAULT '' COMMENT '接受人',
  `valid_time` tinyint(3) unsigned NOT NULL DEFAULT '5' COMMENT '有效期，默认为5分钟',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1为未使用，2为已使用',
  `ip` varchar(16) NOT NULL DEFAULT '' COMMENT '使用人IP',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '活加时间',
  `active_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '激活时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统验证码，现只用邮箱的验证码'";
    }
}