<?php
namespace Xcx\Controller;
use Xcx\Common\Control;

!defined('APP_NAME') && exit('404 Not Found');

class Home extends Control {

    /**
     * 小程序首页信息
     */
    public function info(){
        $arrResult = Array('slider'=>'','server'=>'','notice'=>'');
        $this->toApi('success',0,$arrResult);
    }

}