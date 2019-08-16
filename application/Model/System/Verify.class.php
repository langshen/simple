<?php
namespace Model\System;
use Spartan\Lib\Model;

defined('APP_NAME') or die('404 Not Found');

class Verify extends Model{

    public $codeLen = 6;//验证码长度
    public $codeType = 1;//数字，2，字每，3，都有。
    public $codeText = '';//验证码内容

    /**
     * 添加一个验证码
     * @return array
     */
	public function add(){
	    $arrData = $this->getFieldData('user_id,send_type,rad_code,send_to,valid_time,tpl_key,ip,add_time');
        !$arrData['user_id'] && $arrData['user_id'] = 0;
        if (!$arrData['rad_code']){
            $arrData['rad_code'] = $this->getCode();
        }else{
            $this->codeText = $arrData['rad_code'];
        }
        !$arrData['ip'] && $arrData['ip'] = request()->ip();
        !$arrData['add_time'] && $arrData['add_time'] = date('Y-m-d H:i:s');
        $arrData['send_type'] = max(0,intval($arrData['send_type']));
		if ($arrData['send_type'] == 2){
			if (!isMobile($arrData['send_to'])){
				return Array('验证类型为手机，却不是手机。',1);
			}
		}elseif ($arrData['send_type'] == 1){
			if (!isEmail($arrData['send_to'])){
				return Array('验证类型为邮箱，却不是邮箱。',1);
			}
		}else{
            return Array('发送类似只能为“mobile”，“email”。',1);
        }
		if (!$arrData['valid_time']){//如果没有传入，就用默认的。
			unset($arrData['valid_time']);
		}
		if (!$arrData['tpl_key']){
			return Array('发送模版KEY不能为空。',1);
		}
		$result = db()->insert('system_verify',$arrData);
		if ($result === false){
		    return Array('发送失败。',1);
        }
		$arrData['id'] = max(0,$result);
		return Array('发送成功', 0, $arrData);
	}

    /**
     * 验证一个验证码
     * @return array
     */
	public function verify(){
		$options = Array();
        $arrData = $this->getFieldData('verify_tip,id,user_id,rad_code,send_to,tpl_key,valid_time');
		if (!$arrData['rad_code']){
			return Array($arrData['verify_tip'].'验证码丢失。',1);
		}
		if (!$arrData['send_to'] && !$arrData['id'] && !$arrData['user_id']){//发送对像和验证的ID，至少需要一个
			return Array('接受者或用户ID或该验证的ID丢失。',1);
		}
		if (!$arrData['tpl_key']){
			return Array($arrData['verify_tip'].'验证模块KEY丢失。',1);
		}
        $arrData['id'] && $options['where']['id'] = $arrData['id'];
        $arrData['user_id'] && $options['where']['user_id'] = $arrData['user_id'];
        $arrData['rad_code'] && $options['where']['rad_code'] = $arrData['rad_code'];
        $arrData['send_to'] && $options['where']['send_to'] = $arrData['send_to'];
        $arrData['tpl_key'] && $options['where']['tpl_key'] = $arrData['tpl_key'];
		$arrInfo = db()->find('system_verify',$options);
		if (!$arrInfo){
           return array($arrData['verify_tip'].'验证码不正确，请确认。',1);
		}
		if ($arrInfo['status'] != 1){
			return Array($arrData['verify_tip'].'验证码错误，请重新输入',1);
		}
		if ($arrData['valid_time'] == true){//如果传了需要验证时间，就验证
			if (strtotime($arrInfo['add_time']) + $arrInfo['valid_time'] * 60 < time()){
				return Array($arrData['verify_tip']."验证码已经超过有效期，请在{$arrInfo['valid_time']}分钟之内使用。",1);
			}
		}
		//把验证码的ID压入getData，用于处理过期。
		$this->setData(Array('verify_id'=>$arrInfo['id']));
        $arrInfo['verify_id'] = $arrInfo['id'];
		return Array('验证成功',0,$arrInfo);
	}

    /**
     * 把验证码失效
     * @return array
     */
	public function invalid(){
		$intId = max(0,$this->getData('verify_id',0));
		if ($intId < 1){
			return Array('verify id无效。',1);
		}
		$options = Array(
			'where'=>Array('id'=>$intId),
		);
		$arrData = Array(
			'active_time'=> date('Y-m-d H:i:s',time()),
		    'status'=>2,//2为已使用
		);
		$result = db()->update('system_verify',$arrData,$options);
		if ($result === false){
		    return Array('更改失败异常。',1);
        }
		$arrInfo = db()->find('system_verify',$options);
		if ($arrInfo){//作费同一类验证码
			$options = Array(
				'where'=>Array(
					'send_type'=>$arrInfo['send_type'],
					'send_to'=>$arrInfo['send_to'],
					'tpl_key'=>$arrInfo['tpl_key'],
					'status'=>1,//只把未更新的弄掉
				),
			);
			$arrData['active_time'] = 0;
			$result = db()->update('system_verify',$arrData,$options);
		}
		return Array('更改验证码状态成功',$result===false?1:0);
	}

    /**
     * 设置验证码串的长度
     * @param $intLen
     * @return $this|Verify
     */
	public function setCodeLen($intLen){
        $this->codeLen = $intLen;
        return $this;
    }

    /**
     * 设置验证码串的类型
     * @param $intType
     * @return $this|Verify
     */
    public function setCodeType($intType){
        $this->codeType = $intType;
        return $this;
    }

	/**
	 * @return string 返回验证码串串。
	 */
	public function getCode(){
	    $strRad = '0123456789qwertyuipasdfghjklzxcvbnmo';
	    if ($this->codeType == 1){
            $strRad = substr($strRad,0,10);
        }elseif ($this->codeType == 2){
            $strRad = substr($strRad,10);
        }else{
            $strRad = substr($strRad,1,-1);
        }
        $arrRad = str_split($strRad);
        shuffle($arrRad);
        $arrTempRad = array_rand($arrRad,$this->codeLen);
        $this->codeText = '';
        foreach($arrTempRad as $v){
            $this->codeText .= $arrRad[$v];
        }
		return $this->codeText;
	}

} 