<p align="center">
    <a href="https://github.com/langshen/spartan" target="_blank">
        <img src="https://raw.githubusercontent.com/langshen/spartan/master/logo.png" width="100" height="100" align="middle" />
        <span style="font-size:18px;">Spartan Framework</span>
    </a>
</p>

[![Latest Version](https://img.shields.io/badge/beta-v1.0.0-green.svg?maxAge=2592000)](https://github.com/swoft-cloud/swoft/releases)
[![Build Status](https://travis-ci.org/swoft-cloud/swoft.svg?branch=master)](https://travis-ci.org/swoft-cloud/swoft)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Spartan Doc](https://img.shields.io/badge/docs-passing-green.svg?maxAge=2592000)](http://doc.qz98.net)
[![Spartan License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/langshen/spartan/blob/master/README.md)


### **简介**

一个基于Spartan Framework框架的初始化项目，介绍如何使用Spartan并快速进入开发。

- 基于PSR-4协议，使用命名空间自动懒加载全部所需类。
- 基于OOP，把逻辑层、控制层、视图层和应用层的各类独立分解，方便新手更好地理解和专注于学习功能开发。
- 使用函数助手，把应用类（Cookies、Session、Request、Response、Validate、Db等）单实例化方便新手全局使用。
- 更方便新手的数据库ORM，后台常用的CURD自动生成，规范调用，代码少，思路清清晰。
- 方便的Session共享（默认Redis），让新手更容易做出一套跨环境（APP、小程序、公众号等）的应用。
- 独立Model目录，应用逻辑代码可重复应用于多项目，合适新手建站。


## 文档

[**中文文档（http://doc.qz98.net）**](http://doc.qz98.net)

QQ 交流群1: 233349570


## 环境要求

1. [PHP 7.0 +](http://php.net/)
2. [MySQL5.6 +](https://www.mysql.com/downloads/)


### 安装使用
* ##项目部署（主站）
    
    1、下载Spartan【[https://github.com/langshen/spartan](https://github.com/langshen/spartan)】框架到非中文目录，如：d:/spartan。
    
    2、新建项目目录(项目名称/站点目录)，如：d:/demo/wwwroot，并建立Apache站点指向该目录。
    使用域名 http://www.test.com ，并设置hosts指向：127.0.0.1 www.test.com
    
    3、新建index.php到d:/demo/wwwroot，输入如下代码：
    ```
    <?php
    require('../../spartan/Spartan.php');//Spartan框架位置，可放在任意目录
    Spt::start(
        Array(
            'DEBUG'=>true,//调试模式
            'SAVE_LOG'=>true,//保存日志
        )
    );
    ```

    4、运行站点（如： http://www.test.com ）即可自动生成相应项目及目录。
    
* ##项目部署（子站点）

    1、在application/Common/Config.php下可配置SUB_APP的子站点信息，默认前台站点为：Www，后台站点为Admin。
    
    2、默认可使用 http://www.test.com/admin 访问后台子站点，将映射到application/Admin目录，也可另建独立站点指向该目录，方法如下：
    
    3、新建目录：d:/demo/adminroot，并新建index.php，输入以下代码：
    ```
    <?php
        require('../../spartan/Spartan.php');//Spartan框架位置，可放在任意目录
        Spt::start(
            Array(
                'APP_NAME'=>'Admin',//配置SUB_APP的子站点Admin.OPEN = false;
                'DEBUG'=>true,//调试模式
                'SAVE_LOG'=>true,//保存日志
            )
        );
    ```

    4、建立Apache站点指向该d:/demo/adminroot，使用域名 http://admin.test.com ，并设置hosts指向：127.0.0.1 admin.test.com。
    
    5、运行站点（如： http://admin.test.com ）即可自动生成相应项目及目录并映射到：application/Admin目录。
    配置SUB_APP的子站点Admin.OPEN = false;之后，原来“ http://www.test.com/admin ”将失效。
    
* ##项目部署（cli模式）
    1、新建目录：d:/demo/serverroot，并新建入口文件www.php，输入以下代码：
    ```
    <?php
        require('../../spartan/Spartan.php');//Spartan框架位置，可放在任意目录
        Spt::start(
            Array(
                'APP_NAME'=>'Server',//项目名称，cli模式必需
                'APP_ROOT'=>dirname(__DIR__).DIRECTORY_SEPARATOR.'application',//项目目录，cli模式必需
                'DEBUG'=>true,//调试模式
                'SAVE_LOG'=>true,//保存日志
            )
        );
    ```

    2、在cli模式下运行，php www.php ，即可自动生成相应项目及目录并映射到：application/Server目录。
    
    3、多个cli模式可并存，可增加入口文件admin.php，指定不同的控制器，输入以下代码：
    ```
    <?php
        require('../../spartan/Spartan.php');//Spartan框架位置，可放在任意目录
        Spt::start(
            Array(
                'APP_NAME'=>'Server',//项目名称，cli模式必需
                'APP_ROOT'=>dirname(__DIR__).DIRECTORY_SEPARATOR.'application',//项目目录，cli模式必需
                'CONTROLLER'=>'Admin',//入口控制器，相同项目，不同控制器
                'MAIN_FUN'=>'runMain',//入口函数
                'DEBUG'=>true,//调试模式
                'SAVE_LOG'=>true,//保存日志
            )
        );
    ```
    4、多个cli模式的项目也可以修改：项目名称【APP_NAME】来实现，也可能过修改：入口控制器【CONTROLLER】来实现。
    
    5、什么情况下需要多个cli模式入口？比如：需要和系统配合实现多任务多功能的定时任务（linux下的crontab，windows下的定时任务）触发；使用Swoole监听不同端口或实现不同功能的WebSocket服务。
 
* ##在哪里开始写代码？
    
    1、根据访问URL，所有的请求都指向了application下的APP_NAME（项目名）下的Controller目录中的控制器。
    
    2、每个项目下，个性化的只有Common下的配置、Controller下的控制器和View下的视图。其它的Model和Table是共用的，在任何控制器下使用命名空间来New相应的类即可，或者使用助手函数。
    
    3、专照MVC，开发者只需要关这三部份即可，且部份的Model可在后台以插件的型式直接下载并使用。

## 目录结构
```
├─simple                                框架根目录（一般和项目目录同级）
│  ├─application                        公共目录
│  │  ├─Admin                           管理后台
│  │  │  ├─Common                       子项目配置和函数目录
│  │  │  ├─Controller                   控制器
│  │  │  ├─View                         视图模版
│  │  ├─Common                          项目公共配置和函数
│  │  │  ├─BaseConfig.php               项目公共配置，每个开发者不一样，在git版本管理时，选择过滤
│  │  │  ├─Config.php                   项目公共配置
│  │  │  ├─Functions.php                项目公共函数集
│  │  ├─Model                           公共模型
│  │  ├─Runtime                         运行日志、编译目录、缓存目录，要求可读写
│  │  ├─Server                          cli子项目
│  │  │  ├─Common                       子项目配置和函数目录
│  │  │  ├─Controller                   控制器
│  │  │  ├─Model                        cli项目无需模版，需要个性模型
│  │  ├─Table                           数据库表模型，可在后台自动生成
│  │  ├─Www                             Web前台目录
│  │  │  ├─Common                       子项目配置和函数目录
│  │  │  ├─Controller                   控制器
│  │  │  ├─View                         视图模版
│  ├─attachroot                         用户上传附件的根目录
│  │  ├─README.md
│  ├─extend                             第三方扩展
│  │  ├─README.md
│  ├─serverroot                         cli子项目
│  │  ├─admin.php                       子项目1入口(可选)
│  │  ├─www.php                         子项目2入口(可选)
│  ├─wwwroot                            Web子项目（默认项目）
│  │  ├─adm                             admin项目静态资源文件
│  │  ├─attach                          用户上传文件快捷方式，指向attachroot
│  │  ├─static                          www项目静态资源文件
│  │  ├─.htaccess                       Apache重写规则
│  │  ├─index.php                       项目入口(必需)
```

## 更多用法请关注在线文档

[**在线文档（http://doc.qz98.net）**](http://doc.qz98.net)


## 更新日志

[更新日志](changelog.md)

## 协议

Spartan 的开源协议为 Apache-2.0，详情参见[LICENSE](LICENSE)

    