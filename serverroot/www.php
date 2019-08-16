#!/usr/bin/env php
<?php
require('../../spartan/Spartan.php');
Spt::start(
    Array(
        'APP_NAME'=>'Server',
        'APP_ROOT'=>dirname(__DIR__).DIRECTORY_SEPARATOR.'application',
        'CONTROLLER'=>'Main',//入口控制器
        'MAIN_FUN'=>'runMain',//入口函数
        'DEBUG'=>true,//调试模式
        'SAVE_LOG'=>true,//保存日志
    )
);
