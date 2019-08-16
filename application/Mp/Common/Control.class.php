<?php
namespace Mp\Common;
use Spartan\Lib\Controller;

!defined('APP_NAME') && exit('404 Not Found');
/**
 * @description
 * @author singer
 * @date 15-4-2 下午2:27
 */
abstract class Control extends Controller {
	protected $userInfo = null;

    public function __construct(){
        $strOrigin = request()->param('origin','');
        !$strOrigin && $strOrigin = $_SERVER['HTTP_ORIGIN']??'*';
        header('Access-Control-Allow-Origin:'.$strOrigin);//指定允许其他域名访问
        header('Access-Control-Allow-Headers:AccessKey,Content-Type');//响应头设置
        header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE');//响应方法
        header('Access-Control-Allow-Credentials:true');//响应方法
        parent::__construct();
        $this->userInfo = session('?user_info')?session('user_info'):[];
    }

    /**
     * @description 得到一个给APP端保存的数据组
     * localSTro
     * @param array $arrUserInfo
     * @param string $strLoginType
     * @return array
     */
    public function getStorageInfo($arrUserInfo=[],$strLoginType='user_info'){
        $arrData = Array(
            'php_id'=>session_id(),
            'nick_name'=>$arrUserInfo['nick_name']??'',
            'face'=>$arrUserInfo['face']??'',
            'grade_id'=>$arrUserInfo['grade_id']??1,
            'recommend_code'=>$arrUserInfo['recommend_code']??'',
            'mobile_status'=>$arrUserInfo['mobile_status']??1,
            'real_status'=>$arrUserInfo['real_status']??1,
            'recommend_img'=>$arrUserInfo['recommend_img']??'',
        );
        unset($strLoginType);
        return Array('userInfo',0,$arrData);
    }


} 