<?php
namespace Model\System;
use Spartan\Lib\Model;

defined('APP_NAME') or die('404 Not Found');

class Tpl extends Model{
	/**
	 * 读取当前的需要的发送模版
	 * @return array
	 */
	public function getTpl(){
		static $arrTpl = Array();
		$strTplKey = $this->getData('tpl_key','');
		if (!$strTplKey) {
			return Array('发送模版KEY不能为空。',1,[]);
		}
		if (!isset($arrTpl[$strTplKey])){
            $options = Array(//找到队列需要的模版
                'where'=>Array(
                    'key'=>$strTplKey,
                    'status'=>1,
                ),
                'field'=>'key,title,mobile_info,message_info,email_info',
            );
            $arrInfo = db()->find('system_message_tpl',$options);
            if (!$arrInfo){
                return Array("发送模版{$strTplKey}不存在。",1,[]);
            }
            $arrInfo['mobile_info'] = htmlspecialchars_decode($arrInfo['mobile_info']);
            $arrInfo['message_info'] = htmlspecialchars_decode($arrInfo['message_info']);
            $arrInfo['email_info'] = htmlspecialchars_decode($arrInfo['email_info']);
            $arrTpl[$strTplKey] = $arrInfo;
        }
		return Array('success',0,$arrTpl[$strTplKey]);
	}

} 