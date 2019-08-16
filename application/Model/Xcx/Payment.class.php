<?php
namespace Logic\Xcx;
use Spartan\Extend\Payment\WxPayment;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

class Payment extends Model {

    public function PayOrderCallBack(){
        $strPayment = $this->getData('payment','WXZF');
        $strXml = $this->getData('content','');
        if (!$strXml){
            return $this->callBackLog('信息内容为空。',$strXml);
        }
        $arrSignResult = (new WxPayment())->checkSign($strXml);
        if ($arrSignResult[1] != 1){
            return $this->callBackLog('签名不对。',$strXml);
        }
        $arrInfo = isset($arrSignResult[2])?$arrSignResult[2]:[];
        if (!$arrInfo){
            return $this->callBackLog('签名返回数据为空。',$strXml);
        }
        $arrInfo['out_trade_no'] = isset($arrInfo['out_trade_no'])?$arrInfo['out_trade_no']:'';
        $arrInfo['transaction_id'] = isset($arrInfo['transaction_id'])?$arrInfo['transaction_id']:'';
        $arrInfo['total_fee'] = isset($arrInfo['total_fee'])?$arrInfo['total_fee']:0;
        $arrInfo['result_code'] = isset($arrInfo['result_code'])?$arrInfo['result_code']:'';
        $arrInfo['return_code'] = isset($arrInfo['return_code'])?$arrInfo['return_code']:'';
        $arrInfo['attach'] = isset($arrInfo['attach'])?json_decode($arrInfo['attach'],true):[];
        !is_array($arrInfo['attach']) && $arrInfo['attach'] = [];
        $arrInfo['resp_status'] = ($arrInfo['result_code'] == 'SUCCESS' && $arrInfo['return_code'] == 'SUCCESS')?1:2;//1成功，2失败
        $this->updateCallBackInfo($strPayment, $arrInfo);
        if (!$arrInfo['out_trade_no'] || !$arrInfo['transaction_id'] || $arrInfo['resp_status'] != 1){
            return $this->callBackLog('回调信息不全。',$strXml,$arrInfo);
        }
        if (isset($arrInfo['attach']['inpour_id']) && $arrInfo['attach']['inpour_id'] > 0){
            return $this->updateInpourPayStatus($arrInfo);
        }elseif (isset($arrInfo['attach']['vip_id']) && $arrInfo['attach']['vip_id'] > 0){
            return $this->updateVipStatus($arrInfo);
        }else{
            return $this->updateOrderPayStatus($arrInfo);
        }
    }

    /**
     * 系统异常提醒
     * @param $strMsg
     * @param string $strXml
     * @param array $arrInfo
     * @return array
     */
    public function callBackLog($strMsg,$strXml = '',$arrInfo = []){
        $strPayment = $this->getData('payment','');
        $this->sysError([
            'info'=>$strPayment.'回调失败：'.$strMsg,
            'level'=>3,
            'class'=>'Logic\Xcx\Payment',
            'err'=>(is_array($strXml)?json_encode($strXml,JSON_UNESCAPED_UNICODE):$strXml).
                ($arrInfo?json_encode($arrInfo,JSON_UNESCAPED_UNICODE):'')
        ]);
        return Array($strMsg,0);
    }

    /**
     * 记录所有的回调信息
     * @param $strPayWay string 返回信息
     * @param $arrInfo array 返回信息
     */
    public function updateCallBackInfo($strPayWay,$arrInfo){
        $arrData = Array(
            'pay_way'=>$strPayWay,
            'resp_num'=>$arrInfo['transaction_id'],
            'resp_code'=>$arrInfo['result_code'],
            'resp_status'=>$arrInfo['resp_status'],
            'order_num' => $arrInfo['out_trade_no'],
            'result_info'=>json_encode($arrInfo,JSON_UNESCAPED_UNICODE),
        );
        $result = db()->insert('system_pay_call_back',$arrData);
        if ($result === false){
            $this->sysError(['info'=>$strPayWay.'异步入库失败。']);
        }
    }
    /**
     * 更新订单状态
     * @param $arrTradeInfo array 支付信息
     * @return array
     */
    public function updateOrderPayStatus($arrTradeInfo){
        $intUserId = isset($arrTradeInfo['attach']['user_id'])?$arrTradeInfo['attach']['user_id']:0;
        $intOrderId = isset($arrTradeInfo['attach']['order_id'])?$arrTradeInfo['attach']['order_id']:0;
        $intStatus = isset($arrTradeInfo['attach']['status'])?$arrTradeInfo['attach']['status']:0;
        $intCouponId = isset($arrTradeInfo['attach']['coupon_id'])?$arrTradeInfo['attach']['coupon_id']:0;
        $arrTradeInfo['total_fee'] = bcdiv($arrTradeInfo['total_fee'] , 100,2);
        if ($intUserId < 1 || $intOrderId < 1){
            return $this->callBackLog('原样返回的信息不对。','',$arrTradeInfo);
        }
        $options = array(
            'alias' => 'a',
            'field' => 'a.*,b.open_id',
            'join' => '@.member_auth as b on b.id=a.user_id',
            'where' => array(
                'a.user_id' => $intUserId,
                'a.id' => $intOrderId,
            )
        );
        $arrOrderInfo = db()->find('reservation_order',$options);
        if (!$arrOrderInfo){
            return $this->callBackLog('订单信息不对。','',$arrTradeInfo);
        }
        if ($arrOrderInfo['status'] != $intStatus){
            return $this->callBackLog('订单状态不对1。',$arrOrderInfo,$arrTradeInfo);
        }
        $arrOrderInfo['pay_info'] = json_decode($arrOrderInfo['pay_info'],true);
        !$arrOrderInfo['pay_info'] && $arrOrderInfo['pay_info'] = [];
        $arrOrderInfo['trade_no'] = json_decode($arrOrderInfo['trade_no'],true);
        !$arrOrderInfo['trade_no'] && $arrOrderInfo['trade_no'] = [];
        $arrOrderInfo['pay_time'] = json_decode($arrOrderInfo['pay_time'],true);
        !$arrOrderInfo['pay_time'] && $arrOrderInfo['pay_time'] = [];
        if ($intStatus == 1){
            $intStatus = 2;//状态 1.待支付预约定金，2.处理中，3.待支付尾款，4.支付尾款待确认5.订单完成
            if ($arrOrderInfo['d_money'] != $arrTradeInfo['total_fee']){
                return $this->callBackLog('预付金额不正确。',$arrOrderInfo,$arrTradeInfo);
            }
            $arrOrderInfo['pay_info']['d_money'] = $arrTradeInfo;
            $arrOrderInfo['trade_no']['d_money'] = $arrTradeInfo['transaction_id'];
            $arrOrderInfo['pay_time']['d_money'] = date('Y-m-d H:i:s');
        }elseif ($intStatus == 3){
            $intStatus = 4;//状态 1.待支付预约定金，2.处理中，3.待支付尾款，4.支付尾款待确认5.订单完成
            if ($arrOrderInfo['pay_money'] != $arrTradeInfo['total_fee']){
                return $this->callBackLog('尾款金额不正确。',$arrOrderInfo,$arrTradeInfo);
            }
            $arrOrderInfo['pay_info']['d_money'] = $arrTradeInfo;
            $arrOrderInfo['trade_no']['d_money'] = $arrTradeInfo['transaction_id'];
            $arrOrderInfo['pay_time']['d_money'] = date('Y-m-d H:i:s');
        }else{
            return $this->callBackLog('订单状态不对2。',$arrOrderInfo,$arrTradeInfo);
        }
        //更新订单
        $options = array(
            'where' => array('user_id' => $intUserId,'id' => $intOrderId)
        );
        $arrData = Array(
            'status' => $intStatus,
            'pay_info' => json_encode($arrOrderInfo['pay_info'],JSON_UNESCAPED_UNICODE),
            'trade_no' => json_encode($arrOrderInfo['trade_no'],JSON_UNESCAPED_UNICODE),
            'pay_time' => json_encode($arrOrderInfo['pay_time'],JSON_UNESCAPED_UNICODE),
        );
        $result = db()->update('reservation_order',$arrData,$options);
        if ($result === false){
            return $this->callBackLog("订单{$arrOrderInfo['id']}更新失败",$arrOrderInfo,$arrTradeInfo);
        }
        //如果有优惠券，更新状态
        if ($intCouponId > 0){
            $options = Array(
                'where'=> array('user_id' => $intUserId,'id' => $intCouponId)
            );
            $arrData = Array(
                'status' => 2,
                'use_time' => date('Y-m-d H:i:s'),
            );
            $bolResult = db()->update('operate_coupon_receive',$arrData,$options);
            if ($bolResult === false){
                return $this->callBackLog("优惠券{$intCouponId}异常。",$arrOrderInfo,$arrTradeInfo);
            }
        }
        //如果设置了微信模板消息ID，那就发送微信消息
        $arrOrderInfo['open_id'] = $arrTradeInfo['openid'];
        $arrOrderInfo['amount'] = $arrTradeInfo['total_fee'];
        $this->sendToMpTip($arrOrderInfo,'order');
        return Array('成功',1,$arrOrderInfo);
    }

    /**
     * 更新充值订单状态
     * @param $arrTradeInfo array 支付信息
     * @return array
     */
    public function updateInpourPayStatus($arrTradeInfo){
        $intUserId = isset($arrTradeInfo['attach']['user_id'])?$arrTradeInfo['attach']['user_id']:0;
        $intInpourId = isset($arrTradeInfo['attach']['inpour_id'])?$arrTradeInfo['attach']['inpour_id']:0;
        $intStatus = isset($arrTradeInfo['attach']['status'])?$arrTradeInfo['attach']['status']:0;
        $arrTradeInfo['total_fee'] = bcdiv($arrTradeInfo['total_fee'] , 100,2);
        if ($intUserId < 1 || $intInpourId < 1){
            return $this->callBackLog('原样返回的信息不对。','',$arrTradeInfo);
        }
        $options = array(
            'where' => array('user_id' => $intUserId,'id' => $intInpourId,)
        );
        $arrInpourInfo = db()->find('member_inpour',$options);
        if (!$arrInpourInfo){
            return $this->callBackLog('订单信息不对。','',$arrTradeInfo);
        }
        if ($arrInpourInfo['status'] != $intStatus){
            return $this->callBackLog('订单状态不对1。',$arrInpourInfo,$arrTradeInfo);
        }
        if ($arrInpourInfo['amount'] != $arrTradeInfo['total_fee']){
            return $this->callBackLog('充值金额不正确。',$arrInpourInfo,$arrTradeInfo);
        }
        $arrInpourData = Array(
            'service_num'=>$arrTradeInfo['transaction_id'],
            'status'=>2,
            'result'=>json_encode($arrTradeInfo,JSON_UNESCAPED_UNICODE),
            'put_time'=>date('Y-m-d H:i:s'),
        );
        $result = db()->update('member_inpour',$arrInpourData,$options);
        if ($result === false){
            return $this->callBackLog("充值订单{$arrInpourData['id']}更新失败",$arrInpourInfo,$arrTradeInfo);
        }
        $floTax = 0;//手续费
        /** @var \Model\Member\UserAccount $clsUserAccount */
        $clsUserAccount = $this->getModel('Member_UserAccount');
        $arrResult = $clsUserAccount->setUser($intUserId,$intInpourId)
                ->setGold(
                    bcsub($arrInpourInfo['amount'],$floTax,2),
                    'income',
                    'recharge',
                    '微信支付成功充值:'.$arrInpourInfo['amount'].'元.'
                )->update();

        if ($arrResult[1] != 1){
            return $this->callBackLog("充值订单{$arrInpourData['id']}入帐{$arrInpourInfo['amount']}元失败.",$arrInpourInfo,$arrTradeInfo);
        }
        $arrInpourInfo['open_id'] = $arrTradeInfo['openid'];
        $arrInpourInfo['action_info'] = json_decode($arrInpourInfo['action_info'],true);
        if (isset($arrInpourInfo['action_info']['prepay_id'])&&$arrInpourInfo['action_info']['prepay_id']){
            $arrInpourInfo['prepay_id'] = $arrInpourInfo['action_info']['prepay_id'];
            $this->sendToMpTip($arrInpourInfo,'inpour');
        }else{
            return $this->callBackLog("充值订单{$arrInpourInfo['id']}没有prepay_id",$arrInpourInfo,$arrTradeInfo);
        }
        return Array('成功',1,$arrInpourInfo);
    }

    /**
     * 更新购买VIP状态
     * @param $arrTradeInfo array 支付信息
     * @return array
     */
    public function updateVipStatus($arrTradeInfo){
        $intUserId = isset($arrTradeInfo['attach']['user_id'])?$arrTradeInfo['attach']['user_id']:0;
        $intVipId = isset($arrTradeInfo['attach']['vip_id'])?$arrTradeInfo['attach']['vip_id']:0;
        $arrTradeInfo['total_fee'] = bcdiv($arrTradeInfo['total_fee'] , 100,2);
        $arrData = Array(
            'user_id'=>$intUserId,
            'vip_id'=>$intVipId,
            'pay_money'=>$arrTradeInfo['total_fee']
        );
        $arrResult = $this->getModel('User_Vip',$arrData)->wxSaveVip();
        if ($arrResult[1] != 1){
            return $this->callBackLog($arrResult[0],$arrData,$arrTradeInfo);
        }
        return Array('成功',1,$arrResult);
    }
    /**
     * 发送小程序消息模版
     * @param $arrOrderInfo
     * @param $strType string
     */
    public function sendToMpTip($arrOrderInfo,$strType = ''){
        $clsBizDataCrypt = $this->getModel('Xcx_BizDataCrypt');
        $arrResult = $clsBizDataCrypt->sendTemplateMessage($arrOrderInfo,$strType);
        $this->callBackLog("消息",$arrResult,$arrOrderInfo);
    }
}