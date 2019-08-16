<?php
namespace Table\Web;

defined('APP_NAME') or die('404 Not Found');
class Slider extends \Spartan\Driver\Model\Table
{
    //表名前缀
	public $strPrefix = 'j_';
    //表名
	public $strTable = 'web_slider';
    //表备注
    public $strComment = '幻灯片内容';
	//别名
	public $strAlias = 'a';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = ['id'=>'int'];
	//支持外露的查询条件
    public $arrCondition = Array(
		'id'=>['int',10,''],
		'type_id'=>['int',10,'类型ID'],
		'img_info'=>['varchar',100,'图片信息'],
		'url'=>['varchar',100,'跳转的URL'],
    );
    //添加时必的字段
    public $arrRequire = Array(
		'id'=>['number','','0'],
		'type_id'=>['number','类型ID','0'],
		'img_info'=>['require|length:1,100','图片信息',''],
		'url'=>['length:1,100','跳转的URL',''],
		'hot'=>['number','1无，2为热','1'],
		'hits'=>['number','点击率','0'],
		'top'=>['number','排序','0'],
		'show'=>['number','1为显示，2为隐藏','1'],
    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
		'id'=>['int','10','0','unsigned','true','true','false','null',''],
		'type_id'=>['int','10','0','unsigned','false','false','false','0','类型ID'],
		'img_info'=>['varchar','100','0','utf8_general_ci','false','false','false','Empty String','图片信息'],
		'url'=>['varchar','100','0','utf8_general_ci','false','false','false','Empty String','跳转的URL'],
		'hot'=>['tinyint','3','0','unsigned','false','false','false','1','1无，2为热'],
		'hits'=>['int','10','0','unsigned','false','false','false','0','点击率'],
		'top'=>['tinyint','3','0','unsigned','false','false','false','0','排序'],
		'show'=>['tinyint','3','0','unsigned','false','false','false','1','1为显示，2为隐藏'],
		'add_time'=>['datetime','0','0','','false','false','false','CURRENT_TIMESTAMP','添加时间'],
    );

    //表的SQL
    public function sql(){
        return "CREATE "." TABLE `".$this->strPrefix."web_slider` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `img_info` varchar(100) NOT NULL DEFAULT '' COMMENT '图片信息',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '跳转的URL',
  `hot` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1无，2为热',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击率',
  `top` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `show` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1为显示，2为隐藏',
  `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='幻灯片内容'";
    }
}