<?php
namespace Table\Product;

defined('APP_NAME') or die('404 Not Found');
class Type extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'product_type';
    //表备注
    public $strComment = '商品的分类';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'name'=>['varchar',50,'活动名称'],
		'sort'=>['tinyint',3,'排序'],
		'add_time'=>['datetime',0,'添加时间'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','','0'],
		'pid'=>['number','父类ID','0'],
		'name'=>['require|length:1,50','活动名称',''],
		'sort'=>['number','排序','0'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null',''],
		'pid'=>['int','10','0','unsigned','false','false','false','0','父类ID'],
		'name'=>['varchar','50','0','utf8_general_ci','false','false','false','Empty String','活动名称'],
		'sort'=>['tinyint','3','0','unsigned','false','false','false','0','排序'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','添加时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."product_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父类ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '活动名称',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='商品的分类'";
    }
}