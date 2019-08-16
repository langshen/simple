<?php
namespace Table\Web;

defined('APP_NAME') or die('404 Not Found');
class SliderType extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'web_slider_type';
    //表备注
    public $strComment = '幻灯片分类';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'pid'=>['int',10,'父级ID'],
		'name'=>['varchar',50,'名称'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','自增ID','0'],
		'pid'=>['number','父级ID','0'],
		'name'=>['require|length:1,50','名称',''],
		'sort'=>['number','排序','0'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null','自增ID'],
		'pid'=>['int','10','0','unsigned','false','false','false','0','父级ID'],
		'name'=>['varchar','50','0','utf8_general_ci','false','false','false','Empty String','名称'],
		'sort'=>['tinyint','3','0','unsigned','false','false','false','0','排序'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','添加时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."web_slider_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='幻灯片分类'";
    }
}