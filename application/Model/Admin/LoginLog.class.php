<?php
namespace Model\Admin;
use Spartan\Lib\Model;

defined('APP_NAME') or die('404 Not Found');

class LoginLog extends Model
{

    public function saveLog(){
        $intStatus = $this->getData('status',2);
        $arrData =  Array(
            'user_name' => $this->getData('user_name'),
            'pass_word' => $intStatus==1?'(******成功)':$this->getData('pass_word'),
            'ip'        => $this->getData('ip'),
            'add_time'  => $this->getData('add_time'),
            'status'    => $intStatus,
        );
        $result = db()->insert('admin_login_log',$arrData);
        if ($result == false){
            die( db()->getError() );
        }
    }

}