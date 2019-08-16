<?php
namespace Table\Admin;

defined('APP_NAME') or die('404 Not Found');
class Users extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'admin_users';
    //表备注
    public $strComment = '管理员表';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'id'=>['int',10,''],
		'user_name'=>['varchar',20,'用户名'],
		'real_name'=>['varchar',10,'真实姓名'],
		'pass_word'=>['varchar',32,'密码'],
		'email'=>['varchar',100,'电子邮箱，用于找回密码'],
		'login_time'=>['datetime',0,'登陆时间'],
		'login_ip'=>['varchar',16,'登陆IP'],
		'login_count'=>['int',10,'登陆次数'],
		'status'=>['tinyint',3,'状态，1是正常'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','','0'],
		'user_name'=>['require|length:1,20','用户名',''],
		'real_name'=>['require|length:1,10','真实姓名',''],
		'pass_word'=>['length:1,32','密码',''],
		'email'=>['length:1,100','电子邮箱，用于找回密码',''],
		'face'=>['length:1,100','头像',''],
		'group_id'=>['require|number|gt:0','所属组','0'],
		'user_menu'=>['length:1,2000','额外菜单',''],
		'user_power'=>['length:1,2000','额外权限',''],
		'login_ip'=>['length:1,16','登陆IP',''],
		'status'=>['require|number','状态，1是正常','1'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null',''],
		'user_name'=>['varchar','20','0','utf8_general_ci','false','false','false','Empty String','用户名'],
		'real_name'=>['varchar','10','0','utf8_general_ci','false','false','false','Empty String','真实姓名'],
		'pass_word'=>['varchar','32','0','utf8_general_ci','false','false','false','Empty String','密码'],
		'email'=>['varchar','100','0','utf8_general_ci','false','false','false','Empty String','电子邮箱，用于找回密码'],
		'face'=>['varchar','100','0','utf8_general_ci','false','false','false','Empty String','头像'],
		'group_id'=>['int','10','0','unsigned','false','false','false','0','所属组'],
		'user_menu'=>['varchar','2000','0','utf8_general_ci','false','false','false','Empty String','额外菜单'],
		'user_power'=>['varchar','2000','0','utf8_general_ci','false','false','false','Empty String','额外权限'],
		'login_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','登陆时间'],
		'login_ip'=>['varchar','16','0','utf8_general_ci','false','false','false','Empty String','登陆IP'],
		'login_count'=>['int','10','0','unsigned','false','false','false','0','登陆次数'],
		'status'=>['tinyint','3','0','unsigned','false','false','false','1','状态，1是正常'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','添加时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `real_name` varchar(10) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `pass_word` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '电子邮箱，用于找回密码',
  `face` varchar(100) NOT NULL DEFAULT '' COMMENT '头像',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属组',
  `user_menu` varchar(2000) NOT NULL DEFAULT '' COMMENT '额外菜单',
  `user_power` varchar(2000) NOT NULL DEFAULT '' COMMENT '额外权限',
  `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登陆时间',
  `login_ip` varchar(16) NOT NULL DEFAULT '' COMMENT '登陆IP',
  `login_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态，1是正常',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='管理员表'";
    }
}