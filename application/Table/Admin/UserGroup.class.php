<?php
namespace Table\Admin;

defined('APP_NAME') or die('404 Not Found');
class UserGroup extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'admin_user_group';
    //表备注
    public $strComment = '后台管理员权限分组';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'pid'=>['int',10,'父ID'],
		'name'=>['varchar',20,'权限组名'],
		'menu_list'=>['varchar',4000,'菜单列表'],
		'power_list'=>['varchar',4000,'权限列表'],
		'tip'=>['varchar',50,'备注'],
		'add_time'=>['datetime',0,''],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','','0'],
		'pid'=>['number','父ID','0'],
		'name'=>['require|length:1,20','权限组名'],
		'menu_list'=>['length:1,4000','菜单列表'],
		'power_list'=>['length:1,4000','权限列表'],
		'tip'=>['length:1,50','备注'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null',''],
		'pid'=>['int','10','0','unsigned','false','false','false','0','父ID'],
		'name'=>['varchar','20','0','utf8_general_ci','false','false','false','Empty String','权限组名'],
		'menu_list'=>['varchar','4000','0','utf8_general_ci','false','false','false','Empty String','菜单列表'],
		'power_list'=>['varchar','4000','0','utf8_general_ci','false','false','false','Empty String','权限列表'],
		'tip'=>['varchar','50','0','utf8_general_ci','false','false','false','Empty String','备注'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP',''],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."admin_user_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '权限组名',
  `menu_list` varchar(4000) NOT NULL DEFAULT '' COMMENT '菜单列表',
  `power_list` varchar(4000) NOT NULL DEFAULT '' COMMENT '权限列表',
  `tip` varchar(50) NOT NULL DEFAULT '' COMMENT '备注',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='后台管理员权限分组'";
    }
}