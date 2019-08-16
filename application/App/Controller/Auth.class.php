<?php
namespace App\Controller;
use App\Common\Control;

!defined('APP_NAME') && exit('404 Not Found');

class Auth extends Control {

    /**
     * 得到登录状态
     */
    public function getSessionInfo(){
        $strLoginType = $this->getFieldData(['login_type'=>'user_info']);
        return $this->toApi($this->getStorageInfo($this->userInfo,$strLoginType));
    }


}