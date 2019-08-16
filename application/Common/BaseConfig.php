<?php
/*
*项目的基础配置，如果使用SVN或GIT更新生产环境，忽略该文件即可，非常实用
*/
defined('APP_NAME') or die('404 Not Found');
return Array(
    'SITE'=>Array(//站点设置
        'DOMAIN_ROOT'=>'',//根域名：baidu.com,如果是国家级域名 com.cn net.cn 之类的域名需要配置
        'NAME'=>'Spartan主页',
        'KEY_NAME'=>'spartan,framework,db orm',
        'DESCRIPTION'=>'spartan是一个轻量级的PHP框架，非常非常地轻；部署非常常方便。',
    ),
    'DB'=>Array(//数据库设置
        'TYPE'=>'mysqli',//数据库类型
        'HOST'=>'192.168.1.103',//服务器地址
        'NAME'=>'ele_lift',//数据库名
        'USER'=>'ele_lift',//用户名
        'PWD'=>'ele_lift',//密码
        'PORT'=>'3306',//端口
        'PREFIX'=>'j_',//数据库表前缀
        'CHARSET'=>'utf8',//数据库编码默认采用utf8
    ),
    'SESSION_HANDLER'=>Array(//Session服务器，如果启用，可以共享session
        'OPEN'=>false,
        'NAME'=>'redis',
        'PATH'=>'',
    ),
    'SMS'=>Array(//短信发送配置
        'SENDER'=>'Sms',//短信发送类，默认使用“创世漫道”
        'PROTOCOL'=>'http://',
        'SERVER'=>'sdk.entinfo.cn',
        'USER_NAME'=>'',
        'PASS_WORD'=>'',
        'PORT'=>'8060',
        'INTERVAL'=>3,//间隔时间，秒
        'CHARSET'=>'GBK',
        'ACTION'=>'/webservice.asmx/mdSmsSend_u',//发送的动作，
        'DEBUG'=>true,//测试发送是模拟发送
    ),
    'EMAIL'=>Array(//邮件服务器配置
        'SENDER'=>'Mailer',//邮件发送类
        'SERVER'=>'',//邮件STMP地址
        'USER_NAME'=>'',//地址
        'PASS_WORD'=>'',//密码
        'PORT'=>25,//端口
        'FROM_EMAIL'=>'',//发件人EMAIL
        'FROM_NAME'=>'', //发件人名称
    ),
    'WX_PAYMENT'=>Array(
        'APP_ID' => '',//,
        'MCH_ID' => '',//商户号（必须配置，开户邮件中可查看）
        'APP_KEY' =>'',//商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）https://pay.weixin.qq.com/index.php/account/api_cert
        'APP_SECRET'=> '',//公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
        'NOTIFY_URL'=> '',//异步通知url
        'API_CLIENT_CERT'=>APP_ROOT.'Common'.DS.'keys'.DS.'wx'.DS.'apiclient_cert.pem',
        'API_CLIENT_KEY'=>APP_ROOT.'Common'.DS.'keys'.DS.'wx'.DS.'apiclient_key.pem',
    ),
    'XCX_CONFIG'=>Array(
        'APP_ID'=>'',
        'APP_SECRET'=>'',
        'TOKEN'=>'',
        'ENCODING_AES_KEY'=>'',
    ),
    'MP_CONFIG'=>Array(
        'APP_ID'=>'',
        'APP_SECRET'=>'',
        'TOKEN'=>'',
        'ENCODING_AES_KEY'=>'',
    )
);