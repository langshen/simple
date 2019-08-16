<?php
/*
*站点的常用、公共的配置
*/
defined('APP_NAME') or die('404 Not Found');
$arrConfig = include(APP_ROOT.'Common'.DS.'Config.php');
$arrTemp = Array(

);
return array_merge($arrConfig,$arrTemp);