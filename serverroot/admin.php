#!/usr/bin/env php
<?php
require('../../spartan/Spartan.php');
Spt::start(
    Array(
        'APP_NAME'=>'Server',//项目名称
        'APP_ROOT'=>dirname(__DIR__).DIRECTORY_SEPARATOR.'application',//项目目录
        'CONTROLLER'=>'Admin',//入口控制器
        'MAIN_FUN'=>'runMain',//入口函数
        'DEBUG'=>true,//调试模式
        'SAVE_LOG'=>true,//保存日志
    )
);
