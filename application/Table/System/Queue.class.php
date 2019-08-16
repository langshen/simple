<?php
namespace Table\System;

defined('APP_NAME') or die('404 Not Found');
class Queue extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'system_queue';
    //表备注
    public $strComment = '系统发送队列，目前只有邮箱';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'title'=>['varchar',100,'发送标题'],
		'content'=>['text',0,'发送内容'],
		'send_type'=>['tinyint',3,'队列的类型，'],
		'status'=>['tinyint',3,'1是没发，2已发送'],
		'send_to'=>['varchar',80,'接受人'],
		'add_time'=>['datetime',0,'队列时间'],
		'send_time'=>['datetime',0,'发送时间'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','自增ID','0'],
		'title'=>['length:1,100','发送标题',''],
		'content'=>['length','发送内容',''],
		'send_type'=>['number','队列的类型，#1邮件，2手机','1'],
		'status'=>['number','1是没发，2已发送','1'],
		'send_to'=>['length:1,80','接受人',''],
		'send_info'=>['length:1,1000','发送返回的内容',''],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null','自增ID'],
		'title'=>['varchar','100','0','utf8_general_ci','false','false','false','Empty String','发送标题'],
		'content'=>['text','0','0','utf8_general_ci','false','false','false','null','发送内容'],
		'send_type'=>['tinyint','3','0','unsigned','false','false','false','1','队列的类型，#1邮件，2手机'],
		'status'=>['tinyint','3','0','unsigned','false','false','false','1','1是没发，2已发送'],
		'send_to'=>['varchar','80','0','utf8_general_ci','false','false','false','Empty String','接受人'],
		'send_info'=>['varchar','1000','0','utf8_general_ci','false','false','false','Empty String','发送返回的内容'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','队列时间'],
		'send_time'=>['datetime','0','0','','false','false','false','0000-00-00 00:00:00','发送时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."system_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '发送标题',
  `content` text NOT NULL COMMENT '发送内容',
  `send_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '队列的类型，#1邮件，2手机',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1是没发，2已发送',
  `send_to` varchar(80) NOT NULL DEFAULT '' COMMENT '接受人',
  `send_info` varchar(1000) NOT NULL DEFAULT '' COMMENT '发送返回的内容',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '队列时间',
  `send_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '发送时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统发送队列，目前只有邮箱'";
    }
}