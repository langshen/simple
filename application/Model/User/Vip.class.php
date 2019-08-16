<?php
namespace Model\User;
use Spartan\Lib\Model;
use Model\Member\UserAccount;

defined('APP_NAME') OR exit('404 Not Found');

class Vip extends Model
{
    public $strTable = 'user_vip';
    public $intMonthMoney = 0.1;//每个月10块基础信息

    /**
     * 余额支付的运算
     * @return array|mixed
     */
    public function buyVip(){
        $intPayMonth = max(0,$this->getData('pay_month'));
        $intPayType = max(0,$this->getData('pay_type'));
        $intUserId = max(0,$this->getData('user_id',0));
        if ($intPayMonth < 1){
            return Array('至少购买一个月。',0);
        }
        if ($intPayType < 1 || $intPayType > 2){
            return Array('未知的支付方式。',0);
        }
        if ($intUserId < 1){
            return array('登录超时。',0);
        }
        $floMoney = $this->buyMoney($intPayMonth);
        $arrResult = $this->maxEndTime();
        $dateBeginTime = isset($arrResult[2]['end_time'])?$arrResult[2]['end_time']:'';
        if ($arrResult[1] != 1 || !$dateBeginTime){
            return $arrResult;
        }
        $options = Array('user_id'=>$intUserId);
        $arrAccountInfo = db()->find('member_account',$options);
        if (!$arrAccountInfo){
            return Array('没有找到相关的帐号信息',0);
        }
        if ($arrAccountInfo['use_gold'] < $floMoney){
            return Array("可用余额:{$arrAccountInfo['use_gold']}元，不足于支付:{$floMoney}元VIP费用。");
        }
        db()->startTrans('Vip.saveVip');
        $arrData = Array(
            'user_id'=>$intUserId,
            'begin_time'=>$dateBeginTime,
            'end_time'=>date('Y-m-d 23:59:59',strtotime('+'.$intPayMonth.' Months',strtotime($dateBeginTime))),
            'pay_month'=>$intPayMonth,
            'pay_money'=>$floMoney,
            'pay_type'=>$intPayType,
            'pay_status'=>2,
            'status'=>1,
            'add_time'=>date('Y-m-d H:i:s'),
        );
        $intVipId = max(0,db()->insert($this->strTable,$arrData));
        if ($intVipId < 1){
            db()->rollback();
            return Array('订单异常，无法支付。',0);
        }
        /** @var UserAccount $clsAccount */
        $clsAccount = UserAccount::instance();
        $arrResult = $clsAccount->setUser($arrData['user_id'],$intVipId)
            ->setGold(
                $floMoney,
                'spending',
                'vip',
                "购买{$intPayMonth}VIP服务,共{$floMoney}元."
            )->update();
        if ($arrResult[1] != 1){
            db()->rollback();
            return $arrResult;
        }
        $options = Array('where'=>Array('id'=>$intUserId));
        $arrData = Array(
            'grade_id'=>2,
        );
        $bolResult = db()->update('member_auth',$arrData,$options);
        if ($bolResult === false){
            db()->rollback();
            return Array('更新用户信息失败。');
        }
        db()->commit('Vip.saveVip');
        return Array('更新成功'.$intPayMonth,1,['grade_id'=>2]);
    }

    /**
     * 微信支付返回来的保存结果
     * @return array
     */
    public function wxSaveVip(){
        $intVip = max(0,$this->getData('vip_id'));
        $intUserId = max(0,$this->getData('user_id'));
        $floPayMoney = max(0,$this->getData('pay_money'));
        if ($intUserId < 1 || $intVip < 1){
            return Array('vip_id或user_id小于0',0);
        }
        $options = Array('where'=>Array('id'=>$intVip,'user_id'=>$intUserId));
        $arrVipInfo = db()->find($this->strTable,$options);
        if (!$arrVipInfo){
            return Array('没有找到对应的VIP信息：'.$intVip,0);
        }
        if ($arrVipInfo['status'] != 2){
            return Array('vip信息状态不能更新。'.$arrVipInfo['status'],0);
        }
        if ($arrVipInfo['pay_money'] != $floPayMoney){
            return Array('充值金额不正确',0);
        }
        $intPayMonth = $arrVipInfo['pay_month'];
        $arrResult = $this->maxEndTime();
        $dateBeginTime = isset($arrResult[2]['end_time'])?$arrResult[2]['end_time']:'';
        if ($arrResult[1] != 1 || !$dateBeginTime){
            return $arrResult;
        }
        db()->startTrans('Vip.wxSaveVip');
        $arrData = Array(
            'begin_time'=>$dateBeginTime,
            'end_time'=>date('Y-m-d 23:59:59',strtotime('+'.$intPayMonth.' Months',strtotime($dateBeginTime))),
            'pay_month'=>$intPayMonth,
            'pay_status'=>2,
            'status'=>1,
        );
        $options = Array('where'=>Array('id'=>$intVip,'user_id'=>$intUserId));
        $bolResult = db()->update($this->strTable,$arrData,$options);
        if ($bolResult === false){
            return Array('更新vip信息失败。',0);
        }
        $options = Array('where'=>Array('id'=>$intUserId));
        $arrData = Array(
            'grade_id'=>2,
        );
        $bolResult = db()->update('member_auth',$arrData,$options);
        if ($bolResult === false){
            db()->rollback();
            return Array('更新用户信息失败。');
        }
        db()->commit('Vip.wxSaveVip');
        return Array('更新成功'.$intPayMonth,1);
    }

    /**
     * 得到一个优惠价格。
     * @param $intPayMonth
     * @return float|int
     */
    public function buyMoney($intPayMonth){
        $floMoney = $intPayMonth * $this->intMonthMoney;
        if ($intPayMonth >=3){
            return $floMoney * (1 - 0.01 * ($intPayMonth - 2));
        }else{
            return $floMoney;
        }
    }

    /**
     * 返回一个最大的结束时间
     * @return array|mixed
     */
    public function maxEndTime(){
        $intUserId = $this->getData('user_id',0);
        if ($intUserId < 1){
            return array('登录超时。',0);
        }
        $options = Array(
            'field'=>'max(end_time) as end_time',
            'where'=>Array(
                'user_id'=>$intUserId,
                'pay_status'=>2,
                'status'=>1,
            )
        );
        $arrVipInfo = db()->find($this->strTable,$options);
        !$arrVipInfo && $arrVipInfo = [];
        !isset($arrVipInfo['end_time']) && $arrVipInfo['end_time'] = date('Y-m-d H:i:s');
        strtotime($arrVipInfo['end_time']) < time() && $arrVipInfo['end_time'] = date('Y-m-d H:i:s');
        return Array('成功',1,$arrVipInfo);
    }

}