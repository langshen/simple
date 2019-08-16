<?php
namespace Table\Admin;

defined('APP_NAME') or die('404 Not Found');
class Menu extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'admin_menu';
    //表备注
    public $strComment = '系统菜单表，用户权限区分';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'pid'=>['int',11,'父级ID'],
		'name'=>['varchar',20,'菜单名'],
		'url'=>['varchar',100,'菜单地址'],
		'ico'=>['varchar',20,'标识'],
		'status'=>['tinyint',3,'是否隐藏'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','','0'],
		'pid'=>['number','父级ID','0'],
		'name'=>['require|length:1,20','菜单名',''],
		'url'=>['length:1,100','菜单地址',''],
		'sort'=>['number','排序','0'],
		'ico'=>['length:1,20','标识',''],
		'action_name'=>['length:1,200','对应的方法名',''],
		'status'=>['number','是否隐藏','1'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','11','0','unsigned','true','true','false','null',''],
		'pid'=>['int','11','0','unsigned','false','false','false','0','父级ID'],
		'name'=>['varchar','20','0','utf8_general_ci','false','false','false','Empty String','菜单名'],
		'url'=>['varchar','100','0','utf8_general_ci','false','false','false','Empty String','菜单地址'],
		'sort'=>['tinyint','4','0','unsigned','false','false','false','0','排序'],
		'ico'=>['varchar','20','0','utf8_general_ci','false','false','false','Empty String','标识'],
		'action_name'=>['varchar','200','0','utf8_general_ci','false','false','false','Empty String','对应的方法名'],
		'status'=>['tinyint','3','0','unsigned','false','false','false','1','是否隐藏'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','添加时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."admin_menu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '菜单名',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单地址',
  `sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `ico` varchar(20) NOT NULL DEFAULT '' COMMENT '标识',
  `action_name` varchar(200) NOT NULL DEFAULT '' COMMENT '对应的方法名',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否隐藏',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8 COMMENT='系统菜单表，用户权限区分'";
    }
}