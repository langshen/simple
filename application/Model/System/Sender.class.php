<?php
namespace Model\System;
use Spartan\Lib\Model;

defined('APP_NAME') or die('404 Not Found');

class Sender extends Model{

    public $interval = 60;//发送间隔，秒
    public $arrTpl = ['title'=>'','content'=>''];//发送的内容或标题

    /**
     * 发送一下站内信息
     * 可以接受多个user_id 或 user_name
     * send_type=1系统，send_type=2为用户
     * tip是否立即更新提醒
     */
    public function message(){
        $arrTpl = $this->getTplContent(3);
        if (!$arrTpl['title']){
            return Array('站内信标题为空。',1);
        }
        if (!$arrTpl['content']){
            return Array('站内信内容为空。',1);
        }
        $arrData = $this->getFieldData('user_id,user_name,f_user_id,f_user_name,send_type,tip,ip');
        !$arrData['user_id'] && $arrData['user_id'] = 0;
        !$arrData['f_user_id'] && $arrData['f_user_id'] = 0;
        !$arrData['user_name'] && $arrData['user_name'] = '';
        !$arrData['f_user_name'] && $arrData['f_user_name'] = '';
        !$arrData['send_type'] && $arrData['send_type'] = 1;
        !$arrData['tip'] && $arrData['tip'] = false;
        $arrData['title'] = $arrTpl['title'];
        $arrData['content'] = $arrTpl['content'];
        ($arrData['send_type'] == 2 && !$arrData['ip']) && $arrData['ip'] = request()->ip();
        //提取有效的接收用户
        $options = Array('field'=>'id','where'=>[]);
        if (is_numeric($arrData['user_id']) && intval($arrData['user_id']) > 0){
            $options['where']['id'] = $arrData['user_id'];
        }elseif(stripos($arrData['user_id'],',') > 0 && is_numeric(str_replace(',','',$arrData['user_id']))){
            $options['where']['id'] = Array('IN',$arrData['user_id']);
        }
        stripos($arrData['user_name'],',') > 0 && $arrData['user_name'] = explode(',',$arrData['user_name']);
        !is_array($arrData['user_name']) && $arrData['user_name'] = [$arrData['user_name']];
        $arrData['user_name'] = array_filter(
            $arrData['user_name'],//过滤可能的不合法的数据
            function($v){return $v && 1 === preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-\@\.]+$/u', $v);}
        );
        $arrData['user_name'] && $options['where']['user_name'] = Array('IN',$arrData['user_name']);
        if (!$options['where']){
            return Array('接受用户为空。',1);
        }
        $options['where']['_logic'] = 'OR';
        $arrUserInfo = db()->select('member_auth',$options);
        if (!$arrUserInfo){
            return Array('没有找到正确的用户。',1);
        }
        $clsMessage = Message::init();
        $arrData['tip'] && $clsMessage->setTip(true);
        db()->startTrans('sender.message');
        foreach ($arrUserInfo as $v){
            $arrResult = $clsMessage->setData([
                'user_id'=>$v['id'],
                'f_user_id'=>$arrData['f_user_id'],
                'f_user_name'=>$arrData['f_user_name'],
                'send_type'=>$arrData['send_type'],
                'title'=>$arrData['title'],
                'content'=>$arrData['content'],
                'ip'=>$arrData['ip'],
            ])->add();
            if ($arrResult[1] !== 0){
                db()->rollback();
                $arrResult[0] = 'ID:'.$v['id'].$arrResult[0];
                return $arrResult;
            }
        }
        db()->commit('sender.message');
        return Array('发送成功。',0,['count'=>count($arrUserInfo)]);
    }

    /**
     * 添加一个或多个队列
     */
    public function queue(){
        $arrData = $this->getFieldData('send_to,send_type,auto_send');
        is_null($arrData['auto_send']) && $arrData['auto_send'] = true;
        if (!$arrData['send_type']){
            return Array('队列类型不明确。'.$arrData['send_type'],1);
        }
        $arrTpl = $this->getTplContent($arrData['send_type']);
        if (!$arrTpl['title']){
            return Array('站内信标题为空。',1);
        }
        if (!$arrTpl['content']){
            return Array('站内信内容为空。',1);
        }
        stripos($arrData['send_to'],',') > 0 && $arrData['send_to'] = explode(',',$arrData['send_to']);
        !is_array($arrData['send_to']) && $arrData['send_to'] = [$arrData['send_to']];
        $arrData['send_to'] = array_filter(
            $arrData['send_to'],//过滤可能的不合法的数据
            function ($v) use ($arrData){
                return $v && ($arrData['send_type'] == 2 && isMobile($v) || $arrData['send_type']==1 && isEmail($v));
            }
        );
        if (!$arrData['send_to']){
            return Array('发送对象为空。',1);
        }
        $clsQueue = Queue::init();
        db()->startTrans('sender.queue');
        $intSendCount = 0;
        foreach ($arrData['send_to'] as $v){
            $arrResult = $clsQueue->setData([
                'send_type'=>$arrData['send_type'],
                'title'=>$arrTpl['title'],
                'content'=>$arrTpl['content'],
                'send_to'=>$v,
                'auto_send'=>$arrData['auto_send']
            ])->add();
            if ($arrResult[1] !== 0){
                db()->rollback();
                $arrResult[0] = $v.$arrResult[0];
                return $arrResult;
            }
            $intSendCount++;
        }
        db()->commit('sender.queue');
        return Array('发送成功。',0,['count'=>$intSendCount]);
    }

    /**
     * 添加一个验证码队列
     */
    public function verify(){
        $arrData = $this->getFieldData('user_id,send_to,tpl_key,send_type,code_type,code_len,rad_code,auto_send');
        !$arrData['user_id'] && $arrData['user_id'] = 0;
        !$arrData['auto_send'] && $arrData['auto_send'] = true;
        if (!$arrData['send_to']){
            return Array('发送目标为空。',1);
        }
        if (!$arrData['tpl_key']){
            return Array('模版标识为空。',1);
        }
        if ($arrData['send_type'] != 1 && $arrData['send_type'] != 2){
            return Array('发送类型不正确。',1);
        }
        /** @var \Model\System\Verify $clsVerify */
        $clsVerify = Verify::init();
        $arrData['code_type'] && $clsVerify->setCodeType($arrData['code_type']);
        $arrData['code_len'] && $clsVerify->setCodeLen($arrData['code_len']);
        !$arrData['rad_code'] && $arrData['rad_code'] = $clsVerify->getCode();
        $this->setData(['rad_code'=>$arrData['rad_code']]);
        $arrTpl = $this->getTplContent($arrData['send_type']);
        if (!$arrTpl['title'] && $arrData['send_type'] == 1){
            return Array('站内信标题为空。',1);
        }
        if (!$arrTpl['content']){
            return Array('站内信内容为空。',1);
        }
        $arrResult = $this->checkSendTime();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        db()->startTrans('sender.verify');
        $arrResult = $clsVerify->setData($arrData)->add();
        if ($arrResult[1] !== 0){//添加失败
            return $arrResult;
        }
        $arrData['title'] = $arrTpl['title'];
        $arrData['content'] = $arrTpl['content'];
        $arrResult = Queue::init($arrData)->add();
        if ($arrResult[1] !== 0){//添加失败
            return $arrResult;
        }
        db()->commit('sender.verify');
        $this->saveSendTime();
        $strMsg = '验证码发送成功，请注意查收。'.(config('DEBUG')?$arrData['rad_code']:'');
        return Array($strMsg,0,['rad_code'=>$arrData['rad_code']]);
    }

    /**
     * 检测某一发送对像的间隔时间
     * @return array;
     */
    public function checkSendTime(){
        $arrData = $this->getFieldData('tpl_key,send_to,user_id');
        !$arrData['user_id'] && $arrData['user_id'] = 0;
        $arrSendData = session(md5($arrData['tpl_key'].'_'.$arrData['send_to']));
        if ($arrSendData && isset($arrSendData['send_time'])){
            $intLestTime = $arrSendData['send_time'] + $this->interval - time();
            if ($intLestTime > 0){
                return Array(
                    "每次发送间隔为{$this->interval}秒，请{$intLestTime}秒后再试。",
                    1,
                    ['interval'=>$this->interval,'lest'=>$intLestTime]
                );
            }
        }
        return Array('可发送.',0,['interval'=>$this->interval,'lest'=>0]);
    }

    /**
     * 设置一个成功发送的时间点
     * @return array
     */
    public function saveSendTime(){
        $arrData = $this->getFieldData('tpl_key,send_to,user_id');
        !$arrData['user_id'] && $arrData['user_id'] = 0;
        session(md5($arrData['tpl_key'].'_'.$arrData['send_to']),['send_time'=>time()]);
        return Array('设置完成.',0);
    }

    /**
     * 清除指定的发送时间
     */
    public function clearTime(){
        $strTplKey = $this->getData('tpl_key');
        $strSendTo = $this->getData('send_to');
        session($strTplKey.'_'.$strSendTo,null);
    }

    /**
     * 取得一个模版内容
     * @param int $intType
     * @return array
     */
    private function getTplContent($intType = 3){
        $arrType = ['','email_info','mobile_info','message_info'];
        if (!isset($arrType[$intType])){
            return $this->arrTpl;
        }
        $arrResult = Tpl::init($this->getData())->getTpl();
        !isset($arrResult[2]) && $arrResult[2] = [];
        if ($arrResult[1] === 0){
            $arrTpl = array_merge($this->arrTpl,$arrResult[2]);
            if (isset($arrTpl[$arrType[$intType]]) && $arrTpl[$arrType[$intType]]){
                $arrTpl['content'] = $arrTpl[$arrType[$intType]];
            };
        }else{
            $arrTpl = $this->arrTpl;
        }
        !isset($arrTpl['title']) && $arrTpl['title'] = '';
        !isset($arrTpl['content']) && $arrTpl['content'] = '';
        $arrValueData = $this->getData('value','');
        $arrValueData = json_decode($arrValueData,true);//变量表
        !$arrValueData && $arrValueData = [];
        $arrValueData['rad_code'] = $this->getData('rad_code','');
        $arrValueData['site_name'] = config('SITE.NAME');
        $arrValueData['site_url'] = wwwUrl();
        $arrValueData['admin_url'] = adminUrl();
        $this->parseContent($arrTpl['content'],$arrValueData);
        $this->parseContent($arrTpl['title'],$arrValueData);
        return $arrTpl;
    }

    /**
     * 设置发送的标题和内容
     * @param string $title
     * @param string $content
     * @return $this
     */
    public function setContent($title = '',$content = ''){
        $this->arrTpl['title'] = $title;
        $this->arrTpl['content'] = $content;
        return $this;
    }

    /**
     * @description 解析模版内容
     * @param string $content 模版内容，可带{xxx}这样的变量
     * @param array $arrValue 模版变量的值，
     * @return string $content 返回处理完的模版内容
     */
    public function parseContent(&$content,$arrValue){
        $content = htmlspecialchars_decode($content);
        preg_match_all('/\{\$(.*?)\}/',$content,$value);
        for($i = 0; $i < count($value[1]); $i++) {
            $v = isset($arrValue[$value[1][$i]])?$arrValue[$value[1][$i]]:'';
            $content = str_replace($value[0][$i], $v, $content);
        }
        return $content;
    }

} 