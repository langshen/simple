<?php
namespace Model\Xcx;
use Spartan\Lib\Http;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

class BizDataCrypt extends Model {
    public $arrConfig = [];

    public function __construct(array $arrData = []){
        parent::__construct($arrData);
        $this->arrConfig = config('WX_PAYMENT');
        if (!isset($this->arrConfig['APP_ID']) || !isset($this->arrConfig['APP_SECRET'])){
            \Spt::halt('微信支付配置内容丢失。');
        }
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * 解密后的原文
     * @return array 成功0，失败返回对应的错误码
     */
    public function decryptData(){
        $strSessionKey = $this->getData('session_key','');
        if (!$strSessionKey){
            $arrResult = $this->getOpenId();
            if ($arrResult[1] !== 0){
                return $arrResult;
            }
            $this->setData($arrResult[2]);
            $strSessionKey = $arrResult[2]['session_key'];
        }
        $strEncryptedData = $this->getData('encryptedData','');
        $strIv = $this->getData('iv','');
        if (strlen($strSessionKey) != 24) {
            return Array('session key 非法',1);
        }
        if (strlen($strIv) != 24) {
            return Array('encodingAesKey 非法',1);
        }
        //解密相关内容
        $strAesKey = base64_decode($strSessionKey);
        $strAesIV = base64_decode($strIv);
        $strAesCipher = base64_decode($strEncryptedData);
        $arrResult = json_decode(
            openssl_decrypt( $strAesCipher, "AES-128-CBC", $strAesKey, 1, $strAesIV),
            true
        );
        if(!$arrResult || !isset($arrResult['watermark']) || !isset($arrResult['watermark']['appid'])) {
            return Array('解密后得到的buffer非法',1);
        }
        if ($arrResult['watermark']['appid'] != $this->arrConfig['APP_ID']){
            return Array('解密后得到的appid非法',1);
        }
        unset($arrResult['watermark'],$arrResult['language']);
        $arrResult['session_key'] = $strSessionKey;
        return Array('success',0,$arrResult);
    }

    /**
     * 得到一个open_id
     * @return array
     */
    public function getOpenId(){
        $strJsCode = $this->getData('code','');
        if (!$strJsCode){
            return Array('code丢失。',1);
        }
        $clsHttp = Http::instance();
        $strUrl = "https://api.weixin.qq.com/sns/jscode2session?appid=".
            $this->arrConfig['APP_ID']."&secret=".$this->arrConfig['APP_SECRET']."&js_code={$strJsCode}".
            "&grant_type=authorization_code";
        $arrContent = $clsHttp->send($strUrl);
        if (is_array($arrContent) && isset($arrContent['session_key']) && $arrContent['session_key']){
            return Array('success',0,$arrContent);
        }else{
            return Array('获取OpenId失败。',1);
        }
    }

    /**
     * 得到一个access_token
     * @return array
     */
    public function getAccessToken(){
        $clsHttp = Http::instance();
        $strUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".
            $this->arrConfig['APP_ID']."&secret=".$this->arrConfig['APP_SECRET'];
        $arrContent = $clsHttp->send($strUrl);
        return Array('成功',0,$arrContent);
    }

    /**
     * 得到一个大量B的小程序码
     * @return array
     */
    public function getShareCode(){
        $arrResult = $this->getAccessToken();
        $strAccessToken = isset($arrResult[2]['access_token'])?$arrResult[2]['access_token']:'';
        if ($arrResult[1] !== 0 || !$strAccessToken){
            return Array('取得access_token失败。',1);
        }
        $clsHttp = Http::instance();
        $strUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$strAccessToken;
        $arrPost = Array(
            'scene'=>$this->getData('user_id','0'),
            'page'=>'pages/index/index',
            'width'=>450,
        );
        $strContent = $clsHttp->send($strUrl,json_encode($arrPost,JSON_UNESCAPED_UNICODE),'POST.JSON','html');
        if (strlen($strContent) > 500){
            return Array('成功,',0,base64_encode($strContent));
        }else{
            return Array('失败',1,json_decode($strContent,true));
        }
    }

    /**
     * 转化为xml
     * @param $arrBody
     * @return string
     */
    public function toXml($arrBody){
        $strBody = '<?xml version="1.0" encoding="UTF-8"?>';
        $strBody .= "<xml>";
        foreach ($arrBody as $k => $v){
            if (!$v){continue;}
            if (is_string($v) && !is_numeric($v)){
                $strBody .= "<{$k}><![CDATA[{$v}]]></{$k}>";
            }else{
                $strBody .= "<{$k}>{$v}</{$k}>";
            }
        }
        return $strBody."</xml>";
    }

    /**
     * 发送模版消息
     * @param array $arrInfo
     * @param string $strType
     * @return array
     */
    public function sendTemplateMessage($arrInfo = [],$strType = ''){
        $arrTemplate = Array(
            'inpour'=>'mcMcCnEpJ0S8ZNIUFpsDsEYECpDxH45LeMc9h19mFV0',
            'order'=>'XhueT03GQL1X-MMUcNCyXhHkMurWAazqYqsy72NJYto',
            'vip'=>'WC3hTSHgQU8X9AWAEbNUkon57tuvps0OqGM3A9LCvNM',
        );
        if (!isset($arrTemplate[$strType])){
            return Array('没有合适的模块：'.$strType,1);
        }
        if (!isset($arrInfo['prepay_id']) || !$arrInfo['prepay_id']){
            return Array('没有prepay_id，不能发送',1);
        }
        if ($strType == 'inpour'){
            $arrData = Array(
                'keyword1' => $arrInfo['put_time'],//充值时间
                'keyword2' => $arrInfo['amount'],//充值时间
                'keyword3' => $arrInfo['pay_num'],//充值时间
                'keyword4' => '如有问题，请联系客服，我们会及时为您解答。'//备注
            );
        }elseif ($strType == 'order'){
            $arrData = Array(
                'keyword1' => $arrInfo['put_time'],//充值时间
                'keyword2' => $arrInfo['amount'],//服务信息
                'keyword3' => $arrInfo['pay_num'],//预约时间
                'keyword4' => $arrInfo['pay_num'],//服务地址
                'keyword5' => $arrInfo['pay_num'],//支付金额
                'keyword6' => $arrInfo['pay_num'],//支付时间
                'keyword7' => '如有问题，请联系客服，我们会及时为您解答。'//备注
            );
        }elseif ($strType == 'vip'){
            $arrData = Array(
                'keyword1'=>'',//当前会员等级
                'keyword2'=>'',//有效期
                'keyword3'=>'如有问题，请联系客服，我们会及时为您解答。',//备注
            );
        }else{
            return Array('没有合适的类型：'.$strType,1);
        }
        $arrResult = $this->getAccessToken();
        $strAccessToken = isset($arrResult[2]['access_token'])?$arrResult[2]['access_token']:'';
        if ($arrResult[1] != 1 || !$strAccessToken){
            return Array('取得access_token失败。',1);
        }
        $strUrl = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$strAccessToken;
        $arrPost = array(
            'touser' => $arrInfo['open_id'],
            'template_id' => $arrTemplate[$strType],
            'page' => 'pages/index/index',
            'form_id' => $arrInfo['prepay_id'],
            'data' => $arrData,
        );
        $arrContent = Http::instance()->send($strUrl,json_encode($arrPost,JSON_UNESCAPED_UNICODE),'POST.JSON','html');
        return Array('成功,',0,$arrContent);
    }

}