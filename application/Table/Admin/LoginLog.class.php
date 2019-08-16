<?php
namespace Table\Admin;

defined('APP_NAME') or die('404 Not Found');
class LoginLog extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'admin_login_log';
    //表备注
    public $strComment = '管理员登陆表日志表';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'id'=>['int',11,''],
		'user_name'=>['varchar',50,'错误的用户名'],
		'pass_word'=>['varchar',40,'错误的密码'],
		'ip'=>['varchar',16,'登陆IP'],
		'status'=>['tinyint',4,'错误时的状态,1是正常，2是不正常'],
		'add_time'=>['datetime',0,'登陆时间'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','','0'],
		'user_name'=>['length:1,50','错误的用户名'],
		'pass_word'=>['length:1,40','错误的密码'],
		'ip'=>['length:1,16','登陆IP'],
		'status'=>['number','错误时的状态,1是正常，2是不正常','0'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','11','0','unsigned','true','true','false','null',''],
		'user_name'=>['varchar','50','0','utf8_general_ci','false','false','false','Empty String','错误的用户名'],
		'pass_word'=>['varchar','40','0','utf8_general_ci','false','false','false','Empty String','错误的密码'],
		'ip'=>['varchar','16','0','utf8_general_ci','false','false','false','Empty String','登陆IP'],
		'status'=>['tinyint','4','0','unsigned','false','false','false','1','错误时的状态,1是正常，2是不正常'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','登陆时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."admin_login_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '错误的用户名',
  `pass_word` varchar(40) NOT NULL DEFAULT '' COMMENT '错误的密码',
  `ip` varchar(16) NOT NULL DEFAULT '' COMMENT '登陆IP',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '错误时的状态,1是正常，2是不正常',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登陆时间',
  PRIMARY KEY (`id`),
  KEY `user_name` (`user_name`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=245 DEFAULT CHARSET=utf8 COMMENT='管理员登陆表日志表'";
    }
}