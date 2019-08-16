<?php
namespace Model\Member;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

/**
 * 用户的资金操作
 * Class UserAccount
 */
class UserAccount extends Model
{
    private $_userId = 0;//用户ID
    private $_busId = 0;//操作的外接ID。
    private $_arrAmount = Array();//待操作数量
    private $_arrAccountType = Array();//帐号的消费类型
    private $_arrPayment = Array();
    private $_arrData = Array();//待更新的字段
    private $_arrContent = Array();//各个操作的备注
    private $__arrPayment = Array(
        'spending'=>Array(1,'total_','-','use_','-'),//支出
        'income'=>Array(2,'total_','+','use_','+'),//收入
        'lock'=>Array(3,'use_','-','lock_','+'),//锁定
        'unlock'=>Array(4,'use_','+','lock_','-'),//解锁
        'lock_pay'=>Array(1,'total_','-','lock_','-'),//锁付
    );

    private $__arrAccountType = [];//帐目类型

    /**
     * 设置操作用户和业务ID
     * @param int $intUserId 操作对像
     * @param int $busId 类型ID
     * @return $this
     */
    public function setUser($intUserId,$busId = 0){
        $arrAccountType = array_change_key_case(config('ACCOUNT_TYPE',[]),CASE_LOWER);
        if (!$arrAccountType){
            \Spt::halt('帐目类型：ACCOUNT_TYPE未配置。');
        }
        $this->_arrAccountType = Array();
        $this->_arrPayment = Array();
        $this->_arrAmount = Array();
        $this->_arrData = Array();
        $this->_arrContent = Array();
        $this->_userId = $intUserId;
        $this->_busId = $busId;
        return $this;
    }

    /**
     * 设置金钱
     * @param int $amount 数量
     * @param string $payment 流水方式
     * @param string $accountType 帐目类型
     * @param string $content 备注
     * @return $this
     */
    public function setGold($amount,$payment,$accountType,$content = ''){
        $this->_arrAmount['gold'] = max(0,abs($amount));
        $this->_arrContent['gold'] = $content;
        $this->getUpdateField($payment,$accountType,'gold',$amount);
        return $this;
    }

    /**
     * 设置积分
     * @param int $amount 数量
     * @param string $payment 流水方式
     * @param string $accountType 帐目类型
     * @param string $content 备注
     * @return $this
     */
    public function setScore($amount,$payment,$accountType,$content = ''){
        $this->_arrAmount['score'] = max(0,abs($amount));
        $this->_arrContent['score'] = $content;
        $this->getUpdateField($payment,$accountType,'score',$amount);
        return $this;
    }

    /**
     * 设置不可提现金额
     * @param int $amount 数量
     * @param string $payment 流水方式
     * @param string $accountType 帐目类型
     * @param string $content 备注
     * @return $this
     */
    public function setDiam($amount,$payment,$accountType,$content = ''){
        $this->_arrAmount['diam'] = max(0,abs($amount));
        $this->_arrContent['diam'] = $content;
        $this->getUpdateField($payment,$accountType,'diam',$amount);
        return $this;
    }

    /**
     * 设置其它字段信息
     * @param string $field 字段名称
     * @param int $amount 数量
     * @param string $type 操作类型 + 或 -
     * @return $this
     */
    public function setField($field,$amount,$type = '+'){
        $type != '+' && $type = '-';
        $this->_arrData[$field] = Array('exp',$field.$type.abs($amount));
        return $this;
    }

    /**
     * 各种结算方式 需要更新的字段。找出payment,account_type
     * @param $payment string 流水方式
     * @param $accountType string 帐目类型
     * @param $strField string 字段类型
     * @param $amount int 数量
     */
    private function getUpdateField($payment,$accountType,$strField,$amount){
        if ($amount <=0 ){return;}
        $payment = strtolower($payment);$accountType = strtolower($accountType);//下标为小写
        if (!array_key_exists($payment,$this->__arrPayment)){
            \Spt::halt("流水方式({$payment})不存在:".implode(',',array_keys($this->__arrPayment)));
        }
        $this->_arrPayment[$strField] = $this->__arrPayment[$payment][0];
        if (!array_key_exists($accountType,$this->__arrAccountType)){
            \Spt::halt("帐目类型({$accountType})不存在:".implode(',',array_keys($this->__arrAccountType)));
        }
        $this->_arrAccountType[$strField] = $this->__arrAccountType[$accountType][0];
        //更新的字段构成
        $temp = $this->__arrPayment[$payment];
        $temp = Array(
            $temp[1].$strField=>Array('exp',$temp[1].$strField.$temp[2].abs($amount)),
            $temp[3].$strField=>Array('exp',$temp[3].$strField.$temp[4].abs($amount)),
        );
        $this->_arrData = $this->_arrData?array_merge($this->_arrData,$temp):$temp;
    }

    /**
     * 更新结果
     * @return array
     */
    public function update(){
        if (!$this->_userId){
            return Array('用户ID不能为空。',1);
        }
        if (!$this->_busId){
            return Array('外接ID不能为空。',1);
        }
        if (array_sum($this->_arrAmount) <= 0){
            return Array('更新数据全为0。',1);
        }
        if (!$this->_arrData){
            return Array('没有需要操作的字段。',1);
        }
        $options = Array('where'=>Array('user_id'=>$this->_userId),);
        //***********************开始更新****************************
        $this->_arrData['update_time'] = date('Y-m-d H:i:s',time());
        //更新之前判断
        $arrPreAccountInfo = db()->find('member_account',$options);
        if (!$arrPreAccountInfo){
            \Spt::halt('用户不存('.$this->_userId.')');
        }
        foreach ($this->_arrData as $k=>$v){
            if (!isset($v[0]) || !isset($v[1]) || $v[0] != 'exp' ||
                stripos($v[1],'-') === false || !isset($arrPreAccountInfo[$k])){//只算减的且字段存在的
                continue;
            }
            //$v=Array('lock_score'=>Array('exp'=>lock_score-1));
            //$v[1] = lock_score-1;
            //$arrPreAccountInfo[$k]值
            $tempAmount = intval(str_ireplace($k . '-','',$v[1]));
            if (!is_numeric($tempAmount) || $tempAmount <= 0){
                \Spt::halt('表达式('.$v[1].')解析异常。');
            }
            if ($arrPreAccountInfo[$k] - $tempAmount < 0){
                return Array("帐户[{$this->_userId}]资金不足:({$arrPreAccountInfo[$k]},".($arrPreAccountInfo[$k]-$tempAmount).").",1);
            }
        }
        db()->startTrans('UserAccount.update');
        $result = db()->update('member_account',$this->_arrData,$options);
        if ($result === false) {
            db()->rollback();
            return Array($this->_userId."帐户资金更新失败:".db()->getError(),1);
        }
        //检测帐号是否正常
        $options = Array('where'=>Array('user_id'=>$this->_userId),'lock'=>true,);
        $arrAccountInfo = db()->find('member_account',$options);
        foreach($arrAccountInfo as $k => $v){
            if ($v < 0){
                db()->rollback();
                return Array("帐户({$this->_userId})资金不足[{$k}->{$v}]，无法继续操作。",1);
            }
        }
        //以下是更新各种操作的字段
        $arrData = Array(
            'user_id'=>$this->_userId,
            'ip'=> request()->ip(),
            'add_time'=> date('Y-m-d H:i:s'),
            'business_id'=>$this->_busId,
        );
        $arrResult = Array();
        //更新帐户余额
        if (isset($this->_arrAmount['gold']) && $this->_arrAmount['gold'] > 0){
            $arrTemp = Array(
                'account_type' => $this->_arrAccountType['gold'],
                'payment' => $this->_arrPayment['gold'],
                'amount' => $this->_arrAmount['gold'],
                'content' => $this->_arrContent['gold'],
                'current_total' => $arrAccountInfo['total_gold'],
                'current_use' => $arrAccountInfo['use_gold'],
                'current_lock' => $arrAccountInfo['lock_gold'],
                'pre_total' => $arrPreAccountInfo['total_gold'],
                'pre_use' => $arrPreAccountInfo['use_gold'],
                'pre_lock' => $arrPreAccountInfo['lock_gold'],
                'log_type' => 1,//金币，gold
            );
            $result = db()->insert('member_account_log',array_merge($arrData,$arrTemp));
            if ($result === false){
                db()->rollback();
                $this->sysError();
                return Array($this->_userId."添加帐户1日志失败:".db()->getError(),1);
            }
            $arrResult[] = max(0,$result);
        }
        //更新帐户积分
        if (isset($this->_arrAmount['score']) && $this->_arrAmount['score'] > 0){
            $arrTemp = Array(
                'account_type' => $this->_arrAccountType['score'],
                'payment' => $this->_arrPayment['score'],
                'amount' => $this->_arrAmount['score'],
                'content' => $this->_arrContent['score'],
                'current_total' => $arrAccountInfo['total_score'],
                'current_use' => $arrAccountInfo['use_score'],
                'current_lock' => $arrAccountInfo['lock_score'],
                'pre_total' => $arrPreAccountInfo['total_score'],
                'pre_use' => $arrPreAccountInfo['use_score'],
                'pre_lock' => $arrPreAccountInfo['lock_score'],
                'log_type' => 2,//积分，score
            );
            $result = db()->insert('member_account_log',array_merge($arrData,$arrTemp));
            if ($result === false){
                db()->rollback();
                $this->sysError();
                return Array($this->_userId."添加帐户2日志失败:".db()->getError(),1);
            }
            $arrResult[] = max(0,$result);
        }
        //更新不可提现金额
        if (isset($this->_arrAmount['diam']) && $this->_arrAmount['diam'] > 0){
            $arrTemp = Array(
                'account_type' => $this->_arrAccountType['diam'],
                'payment' => $this->_arrPayment['diam'],
                'amount' => $this->_arrAmount['diam'],
                'content' => $this->_arrContent['diam'],
                'current_total' => $arrAccountInfo['total_diam'],
                'current_use' => $arrAccountInfo['use_diam'],
                'current_lock' => $arrAccountInfo['lock_diam'],
                'pre_total' => $arrPreAccountInfo['total_diam'],
                'pre_use' => $arrPreAccountInfo['use_diam'],
                'pre_lock' => $arrPreAccountInfo['lock_diam'],
                'log_type' => 3,//钻石，diam
            );
            $result = db()->insert('member_account_log',array_merge($arrData,$arrTemp));
            if ($result === false){
                db()->rollback();
                $this->sysError();
                return Array($this->_userId."添加帐户3日志失败:".db()->getError(),1);
            }
            $arrResult[] = max(0,$result);
        }
        //添加帐户系统日志，后期可以取消
        $arrSysData = Array(
            'account_log_id'=>implode(',',$arrResult),
            'content'=>implode('{@}',$this->_arrContent),
            'add_time'=> date('Y-m-d H:i:s'),
            'user_id'=>$arrAccountInfo['user_id'],
            'field_content' => str_replace('"exp",','',json_encode($this->_arrData,JSON_UNESCAPED_UNICODE)),
            'value_content' => json_encode(
                Array(
                    'total_gold'=>$arrAccountInfo['total_gold'],
                    'use_gold'=>$arrAccountInfo['use_gold'],
                    'lock_gold'=>$arrAccountInfo['lock_gold'],
                    'total_score'=>$arrAccountInfo['total_score'],
                    'use_score'=>$arrAccountInfo['use_score'],
                    'lock_score'=>$arrAccountInfo['lock_score'],
                    'total_diam'=>$arrAccountInfo['total_diam'],
                    'use_diam'=>$arrAccountInfo['use_diam'],
                ),JSON_UNESCAPED_UNICODE),
        );
        $result = db()->insert('member_account_sys_log',$arrSysData);
        if ($result === false){
            db()->rollback();
            $this->sysError();
            return Array($this->_userId.'添加帐户系统日志失败:'.db()->getError(),1);
        }
        $arrAccountInfo['sys_id'] = $result;
        db()->commit('UserAccount.update');
        return Array('更新成功。', 0, $arrAccountInfo);
    }

    /**
     * 取得一个用户的帐目信息
     * @return array
     */
    public function find(){
        $intUserId = max(0,$this->getData('user_id'));
        if ($intUserId < 1){
            return Array('ID为空。',1);
        }
        $options = Array('where'=>Array('user_id'=>$intUserId,));
        $arrInfo = db()->find('member_account',$options);
        if (!$arrInfo){
            return Array('没有找到相应的用户。',1);
        }
        return Array('success', 0, $arrInfo);
    }

}