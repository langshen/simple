<?php
namespace Admin\Controller;
use Admin\Common\Control;

class Queue extends Control {
    /**
     * 发送邮件验证码。
     */
    public function email(){
        $arrData = Array(
            'user_name'=>['require|length:2,32','请输入正确的用户名。'],
            'send_to'=>['require|email|length:8,80','请输入正确的电子邮箱。'],
            'tpl_key'=>['require|length:2,20','发送模块不正确。'],
            'send_type'=>1,//邮箱
            'tip'=>false,
        );
        if (!valid($arrData,$message)){
            return $this->toApi($message);
        }
        $options = Array(
            'where'=>Array(
                'user_name'=>$arrData['user_name'],
                'email'=>$arrData['send_to'],
                'status'=>1,
            )
        );
        $arrInfo = db()->find('admin_users',$options);
        if (!$arrInfo){
            return $this->toApi('用户名和邮箱不符合。');
        }
        $arrData['value'] = json_encode($arrInfo,JSON_UNESCAPED_UNICODE);
        $arrData['user_id'] = $arrInfo['id'];
        /** @var \Model\System\Sender $clsSender */
        $clsSender = model('System_Sender')->setData($arrData);
        $clsSender->setContent('找回管理密码','{$user_name}，您的验证码是：{$rad_code}');
        $arrResult = $clsSender->verify();
        unset($arrResult[2]);
        return $this->toApi( $arrResult );
    }

    /**
     * 发送短信，都是验证码。
     */
    public function sendSms(){
        $clsSender = model('System_Sender');
        $this->toApi( $clsSender->setData([
            'send_type'=>2,
            'send_to'=>'18907779520,sl@qq.com,,<sc'
        ])->setContent('您好','内容【事业通】')->queue());
    }



}