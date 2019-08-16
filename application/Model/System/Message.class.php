<?php
namespace Model\System;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

class Message extends Model{

    private $bolTip = false;//是否提醒

    /**
     * 设置是否提提醒
     * @param bool $bolTip
     * @return $this
     */
    public function setTip($bolTip = true){
        $this->bolTip = $bolTip;
        return $this;
    }

    /**
     * 添加站内信入口
     * @return array
     */
    public function add(){
        $arrData = $this->getFieldData(['send_type'=>1]);
        if ($arrData['send_type'] == 1){
            return $this->addSystemMessage();
        }else{
            return $this->addUserMessage();
        }
    }

    /**
     * 短消息的提示
     * @return array
     */
    public function tipMessage(){
        $arrData = $this->getFieldData(['user_id'=>0]);
        if ($arrData['user_id'] < 1){
            return Array('用户ID异常。',1);
        }
        $options = Array(
            'where'=>Array('user_id'=>$arrData['user_id']),
        );
        $arrData = Array(
            'new_message'=>Array('exp','new_message+1'),
        );
        db()->update('member_account',$arrData,$options);
        //发送成功写入redis

        return Array('成功.',0);
    }
    /**
     * 添加一条系统短消息
     * @return array
     */
	public function addSystemMessage(){
        $arrData = $this->getFieldData('user_id,title,content,status,add_time');
        !$arrData['status'] && $arrData['status'] = 1;
        !$arrData['add_time'] && $arrData['add_time'] = date('Y-m-d H:i:s');
        $arrData['type'] = 1;
		if (!$arrData['user_id']){
		    return Array('接受用户不明确。',1);
		}
		$result = db()->insert('system_message',$arrData);
        if(!$result){
            return Array('添加系统站内信失败',1);
        }
        $arrData = ['message_id'=>max(0,$result),'type'=>$arrData['type']];
        $this->setData($arrData);
        $this->bolTip && $this->tipMessage();
		return Array('添加系统站内信成功',0,$arrData);
	}

    /**
     * 添加一条或多个用户短消息
     * @return array
     */
	public function addUserMessage(){
        $arrData = $this->getFieldData('user_id,f_user_id,f_user_name,title,content,status,add_time,ip');
        $arrData['type'] = 2;//用户信息
        !$arrData['status'] && $arrData['status'] = 1;
        !$arrData['add_time'] && $arrData['add_time'] = date('Y-m-d H:i:s');
        !$arrData['ip'] && $arrData['ip'] = request()->ip();
        if (!$arrData['user_id']){
            return Array('接受用户不明确。',1);
        }
        if (!$arrData['f_user_id'] || !$arrData['f_user_name']){
            return Array('发送用户不明确。',1);
        }
		if (mb_strlen($arrData['title'],'utf-8') < 1 || mb_strlen($arrData['title'],'utf-8') > 100){
			return Array("信息标题应该在1－100个字符之间。");
		}
		if (mb_strlen($arrData['content'],'utf-8') < 1 || mb_strlen($arrData['content'],'utf-8') > 400){
			return Array("信息内容应该在1－400个字符之间。");
		}
		$result = db()->insert('system_message',$arrData);
		if(!$result){
			return Array('发送站内信失败',1);
		}
        $arrData = ['message_id'=>max(0,$result),'type'=>$arrData['type']];
		$this->setData($arrData);
        $this->bolTip && $this->tipMessage();
        return Array('添加站内信成功',0,$arrData);
	}
} 