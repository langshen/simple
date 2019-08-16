<?php
namespace Model\System;
use Spartan\Lib\Model;
use Spartan\Extend\Sender as SenderSmsEmail;

defined('APP_NAME') or die('404 Not Found');

class Queue extends Model{
	/**
	 * * 添加一个队列。。
	 * @return array
	 */
    public function add(){
        $arrData = $this->getFieldData('title,content,send_type,send_to,status,add_time');
        !$arrData['status'] && $arrData['status'] = 1;//未发送
        !$arrData['add_time'] && $arrData['add_time'] = date('Y-m-d H:i:s');
        $arrData['send_type'] = max(0,intval($arrData['send_type']));
        if ($arrData['send_type'] != 1 && $arrData['send_type'] != 2) {//如果没有传入，就用默认的。
            return Array('发送类似只能为“mobile2”，“email1”。',1);
        }
	    if ($arrData['send_type'] == 2){
		    if (!isMobile($arrData['send_to'])){
			    return Array('验证类型为手机，却不是手机。',1);
		    }
	    }elseif ($arrData['send_type'] == 1){
		    if (!isEmail($arrData['send_to'])){
			    return Array('验证类型为邮箱，却不是邮箱。',1);
		    }
	    }
	    !$arrData['title'] && $arrData['title'] = '';
	    !$arrData['content'] && $arrData['content'] = '';
	    if (!$arrData['content']){//如果没有内容，就要用模版的KEY查找。
			return Array('发送内容为空。',1);
	    }
	    $result = db()->insert('system_queue',$arrData);
	    if ($result === false){
            return Array('添加队列失败。',1);
        }
        $arrData = Array('queue_id'=>max(0,$result));
	    $this->setData($arrData);
	    if ($this->getData('auto_send',false) == true){
	        return $this->send();
        }
	    return Array('success',0,$arrData);
    }

    /*发送指定的队列*/
    public function send(){
        $arrData = $this->getFieldData('num,queue_id,title,send_type,status,send_to,begin_time,end_time');
        $arrData['send_type'] = max(0,$arrData['send_type']);
        $arrData['status'] = max(0,$arrData['status']);
        $options = Array(
            'limit'=>max(1,$arrData['num']),//发送条数
            'where'=>Array(),
        );
        //指定队列ID
        if (is_numeric($arrData['queue_id']) && intval($arrData['queue_id']) > 0){
            $options['where']['id'] = $arrData['queue_id'];
        }elseif(stripos($arrData['queue_id'],',') > 0 && is_numeric(str_replace(',','',$arrData['queue_id']))){
            $options['where']['id'] = Array('IN',$arrData['queue_id']);
        }
        //如果指定发送标题
        $arrData['title'] && $options['where']['title'] = $arrData['title'];
        //如果指定发送接收者
        $arrData['send_to'] && $options['where']['send_to'] = $arrData['send_to'];
        //如果指定发送状态
        $arrData['status'] && $options['where']['status'] = $arrData['status'];
        //发送某种类型
        $arrData['send_type'] && $options['where']['send_type'] = $arrData['send_type'];
        //指定发送某一时间段的开始时间
        //指定发送某一时间段的开始时间  指定发送某一时间段的结束时间
        if (strtotime($arrData['begin_time']) && strtotime($arrData['end_time'])){
            $options['where']['add_time'] = Array(
                'between',
                $arrData['begin_time'],
                $arrData['end_time']
            );
        }
        if (!$options['where']){
            return Array('没有指定任何发送条件。',1);
        }
        $arrInfo = db()->select('system_queue',$options);
        if (!$arrInfo){
            return Array('没有需要发送的队列。',1);
        }
        $arrSuccess = [0,0];//成功，失败
        $clsSender = SenderSmsEmail::instance();
        foreach($arrInfo as $v){
            $arrData = Array('send_time' => date('Y-m-d H:i:s'));
            if ($v['send_type'] == 2){//2='MOBILE'
                $arrResult = $clsSender->sms()->setMobile($v['send_to'])->setBody($v['content'])->send();
            }elseif ($v['send_type'] == 1){//1='EMAIL'
                $arrResult = $clsSender->email()->setEmail($v['send_to'])->setMailInfo($v['title'],$v['content'])->send();
            }else{
                continue;
            }
            !isset($arrResult[0]) && $arrResult[0] = '未知错误';
            !isset($arrResult[1]) && $arrResult[1] = 1;
            if ($arrResult[1] !== 0){
                $arrData['send_info'] = $arrResult[0];
                $arrData['status'] = 3;
                $arrSuccess[1]++;
            }else{
                $arrData['send_info'] = isset($arrResult[0])?$arrResult[0]:'';
                $arrData['status'] = 2;//成功
                $arrSuccess[0]++;
            }
            $options = Array(
                'where'=>Array('id'=>$v['id']),
            );
            db()->update('system_queue',$arrData,$options);
        }
        unset($clsSender);
        return Array('共发送'.count($arrInfo).'条，成功'.$arrSuccess[0].'条，失败'.$arrSuccess[1].'条。',0);
    }

} 