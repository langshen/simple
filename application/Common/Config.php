<?php
/*
*项目的常用、公共的配置
*/
defined('APP_NAME') or die('404 Not Found');
$arrConfig = include('BaseConfig.php');
$arrTemp =  Array(
    'COOKIE'=>Array(
        'PREFIX'=>'',//cookie 名称前缀
        'EXPIRE'=>0,//cookie 保存时间
        'PATH'=>'/',//cookie 保存路径
        'DOMAIN'=>'',//cookie 有效域名,为空时，默认为：.xx.com
        'HTTPONLY'=>'',//httponly设置
        'SECURE'=>false,//cookie 启用安全传输
        'SETCOOKIE'=>true,//是否使用 setcookie
    ),
    'SESSION'=>Array(
        'AUTO_START'=>true,// 是否自动开启Session
        'PREFIX'=>'',// session 前缀
        'VAR_SESSION_ID'=>'',//SESSION_ID的提交变量,解决flash上传跨域
        'NAME'=>'SPASESSION',//sessionID的变量名
        'DOMAIN'=>'',//为空时，默认为：.xx.com
        'EXPIRE'=>24*3600,//存活时间
        'TYPE'=>'',//驱动方式 支持redis memcache memcached
    ),
    'SUB_APP'=>Array(//是否启用多应用模式，即多个应用模式分离，如：前台和后台
        'Www'=>Array(//key为SUB_APP_NAME,
            'OPEN'=>true,//是否启用
            'NAME'=>'Www',//子应用名称，即SUB_APP_NAME
        ),
        'User'=>Array(
            'OPEN'=>true,
            'NAME'=>'User',
        ),
        'Admin'=>Array(
            'OPEN'=>true,
            'NAME'=>'Admin',
        ),
        'Xcx'=>Array(
            'OPEN'=>true,
            'NAME'=>'Xcx',
        ),
        'Mp'=>Array(
            'OPEN'=>true,
            'NAME'=>'Mp',
        ),
        'App'=>Array(
            'OPEN'=>true,
            'NAME'=>'App',
        ),
    ),
    'ACCOUNT_TYPE'=>Array(//帐目类型，'调用别名'=>Array(ID号,名称);
        'recharge'=>Array(1,'充值'),
        'withdraw'=>Array(2,'提现'),
        'reward'=>Array(3,'奖励'),
        'vip'=>Array(4,'购买Vip'),
        'agent_out'=>Array(5,'代理充值'),
        'agent_in'=>Array(6,'代理充值'),
    ),
);
return array_merge($arrConfig,$arrTemp);