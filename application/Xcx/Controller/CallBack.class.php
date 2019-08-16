<?php
namespace Xcx\Controller;
use Spartan\Lib\Controller;
/**
 * @description 外接口请求跳转类，
 */
class CallBack extends Controller{

    /**
     * 微信支付扫码支付回调
     */
    public function WxPayOrderCallBack(){
        $strSuccess = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        $strXml = file_get_contents('php://input');
        if (!$strXml){
            print_r('hi,is empty.');die();
        }
        $arrData = Array(
            'content'=>$strXml,
            'payment'=>'WXZF_XCX',
        );
        $arrResult = model('Xcx_Payment',$arrData)->PayOrderCallBack();
        if ($arrResult[1] === 0){
            exit($strSuccess);
        }else{
            print_r($arrResult);
        }
    }


}
