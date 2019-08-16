<?php
namespace Xcx\Controller;
use Xcx\Common\Control;

!defined('APP_NAME') && exit('404 Not Found');

class Auth extends Control {

    /**
     * 得到登录状态
     */
    public function getSessionInfo(){
        $arrUserInfo = request()->session('user_info',[]);
        if ($arrUserInfo){
            return $this->toApi($this->getStorageInfo($arrUserInfo,'xcx'));
        }
        $arrData = $this->getFieldData('code,errMsg,rawData,signature,encryptedData,iv');
        $arrResult = model('Xcx/BizDataCrypt',$arrData)->decryptData();
        if ($arrResult[1] !== 0 || !isset($arrResult[2])){
            return $this->toApi($arrResult);
        }
        $arrDecryptData = $arrResult[2];//解密后信息
        //提交得到用户的信息，登录或者注册
        $arrResult = model('Member/Auth',$arrDecryptData)->autoLogin();
        if ($arrResult[1] !== 0){
            return $this->toApi($arrResult);
        }
        $arrUserInfo = $arrResult[2];
        if (isset($arrUserInfo['face']) && $arrUserInfo['face'] && stripos($arrUserInfo['face'],'://')===false){
            $arrUserInfo['face'] = attachUrl($arrUserInfo['face']);
        }
        if (isset($arrUserInfo['recommend_img']) && $arrUserInfo['recommend_img'] && stripos($arrUserInfo['recommend_img'],'://')===false){
            $arrUserInfo['recommend_img'] = attachUrl($arrUserInfo['recommend_img']);
        }
        $arrUserInfo['session_key'] = $arrDecryptData['session_key'];
        session('user_info',$arrUserInfo);
        return $this->toApi('success',0,$this->getStorageInfo($arrUserInfo,'xcx'));
    }

    /**
     * 得到一个小程序的access_token
     */
    public function getAccessToken(){
        $arrResult = model('Xcx/BizDataCrypt')->getAccessToken();
        $this->toApi($arrResult);
    }

    /**
     * 得到一个小程序分享码
     */
    public function getShareCode(){
        $arrData = Array(
            'user_id'=>$this->userInfo['id'],
            'wx_open_id'=>$this->userInfo['wx_open_id'],
        );
        $options = Array(
            'field'=>'xcx_code',
            'where'=>Array('id'=>$this->userInfo['id'])
        );
        $arrAuthInfo = $this->dal('member_auth')->find($options);
        if (!$arrAuthInfo){
            $this->ajaxMessage('登录超时.');
        }
        if (is_file(uploadPath($arrAuthInfo['xcx_code']))){
            $arrAuthInfo['xcx_code'] = attachUrl($arrAuthInfo['xcx_code']);
            $this->ajaxMessage('成功',1,$arrAuthInfo);
        }
        $arrResult = $this->logic('Xcx/BizDataCrypt',$arrData)->getShareCode();
        $strContent = isset($arrResult[2])?$arrResult[2]:'';
        if ($arrResult[1] != 1 || !$strContent){
            $this->ajaxMessage($arrResult);
        }
        $clsAttachment = new Attachment();
        $strUrl = '/code/'.($arrData['user_id'] % 50);
        $arrResult = $clsAttachment->createDir(uploadPath($strUrl));
        if ($arrResult[1] != 1){
            $this->ajaxMessage($arrResult);
        }
        $strUrl .= '/'.$arrData['user_id'].'.jpg';
        $arrResult = $clsAttachment->savePicture($strContent,uploadPath($strUrl));
        if ($arrResult[1] != 1){
            $this->ajaxMessage($arrResult);
        }
        $options = Array('where'=>Array('id'=>$this->userInfo['id']));
        $arrData = Array('xcx_code'=>$strUrl);
        $bolResult = $this->dal('member_auth')->updateField($arrData,$options);
        if ($bolResult){
            $this->userInfo['xcx_code'] = $strUrl;
            session('user_info',$this->userInfo);
        }
        $arrData['xcx_code'] = attachUrl($arrData['xcx_code']);
        $this->ajaxMessage('成功',1,$arrData);
    }

    /**
     * 会员个人信息
     */
    public function getAuthInfo(){
        $options = Array(
            'field'=>'user_name,email,mobile,face,nick_name,grade_id,sex,xcx_code,real_name,id_card,introduce,mobile_status,real_status',
            'where'=>Array(
                'id'=>$this->userInfo['id'],
            ),
        );
        $arrAuthInfo = $this->dal('member_auth')->find($options);
        if (!$arrAuthInfo){
            $this->ajaxMessage('登录超时.');
        }
        if (isset($arrAuthInfo['face']) && $arrAuthInfo['face'] && stripos($arrAuthInfo['face'],'://')===false){
            $arrAuthInfo['face'] = attachUrl($arrAuthInfo['face']);
        }
        if (isset($arrAuthInfo['xcx_code']) && $arrAuthInfo['xcx_code'] && stripos($arrAuthInfo['xcx_code'],'://')===false){
            $arrAuthInfo['xcx_code'] = attachUrl($arrAuthInfo['xcx_code']);
        }
        $this->userInfo['grade_id'] = $arrAuthInfo['grade_id'];
        $this->userInfo['mobile_status'] = $arrAuthInfo['mobile_status'];
        $this->userInfo['real_status'] = $arrAuthInfo['real_status'];
        $this->userInfo['nick_name'] = $arrAuthInfo['nick_name'];
        $this->userInfo['mobile'] = $arrAuthInfo['mobile'];
        session('user_info',$this->userInfo);
        $this->ajaxMessage('成功',1,$arrAuthInfo);
    }

    /**
     * 某个会员信息
     */
    public function getUserInfo(){
        $strWxOpenId = trim($this->request()->input('wx_open_id'));
        $options = Array(
            'field'=>'user_name,email,mobile,face,nick_name,xcx_code,grade_id,sex,real_name,id_card,introduce,mobile_status,real_status',
            'where'=>Array(
                'wx_open_id'=>$strWxOpenId,
            ),
        );
        $arrAuthInfo = $this->dal('member_auth')->find($options);
        if (!$arrAuthInfo){
            $this->ajaxMessage('用户不存在.');
        }
        if (isset($arrAuthInfo['face']) && $arrAuthInfo['face'] && stripos($arrAuthInfo['face'],'://')===false){
            $arrAuthInfo['face'] = attachUrl($arrAuthInfo['face']);
        }
        if (isset($arrAuthInfo['xcx_code']) && $arrAuthInfo['xcx_code'] && stripos($arrAuthInfo['xcx_code'],'://')===false){
            $arrAuthInfo['xcx_code'] = attachUrl($arrAuthInfo['xcx_code']);
        }
        $this->ajaxMessage('成功',1,$arrAuthInfo);
    }



}