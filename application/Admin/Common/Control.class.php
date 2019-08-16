<?php
namespace Admin\Common;
use Spartan\Lib\Controller;

defined('APP_NAME') or die('404 Not Found');

abstract class Control extends Controller {
	protected $adminInfo = null;

	public function __construct(){
		parent::__construct();
        $this->adminInfo = session('?admin_info')?session('admin_info'):[];
		$this->checkAccess();
	}

    /**
     * 针对于layui的格式输出
     * @param $arrInfo
     * @return mixed
     */
    public function tableList($arrInfo){
        $intCount = $arrInfo['count']??0;
        unset($arrInfo['count']);
        $arrData = [
            'code' => 0,
            'msg'  => 'success',
            'time' => time(),
            'data' => $arrInfo['data'],
            'count'=> $intCount,
        ];
        return json($arrData,200)->send();
    }

    /**
     * 得到一个用户信息
     * @param string $strField
     * @param string $strValue
     * @return array|mixed
     */
    public function getUserInfo($strField='user_name',$strValue=''){
        if (!$strField || !$strValue){return [];}
        $options = Array(
            'where'=>Array($strField=>$strValue),
        );
        $arrUserInfo = db()->find('member_auth',$options);
        return $arrUserInfo;
    }

    /**
     * 为用户增加查询条件
     * @param $arrSearchType
     */
    public function addSearchTypeByAuth(&$arrSearchType){
        array_unshift($arrSearchType,Array('id'=>'b.mobile','text'=>'手机号码'));
        array_unshift($arrSearchType,Array('id'=>'b.user_name','text'=>'用户名'));
        array_unshift($arrSearchType,Array('id'=>'b.nick_name','text'=>'昵称'));
        array_unshift($arrSearchType,Array('id'=>'b.real_name','text'=>'真实姓名'));
    }

    /**
     * 权限判断
     */
    private function checkAccess(){
        if (!$this->adminInfo && !$this->whiteList()) {
            if ($this->request->isAjax()){
                $this->toApi('登录超时，请重新登录。',1001);die();
            }else{
                $this->error('登录超时，请重新登录。',adminUrl().'/index/login');
            }
        }
    }

    /**
     * 不判断登录的白名单
     * @return bool
     */
    private function whiteList(){
        $strUrl = strtolower(config('URL'));
	    $arrUrl = Array(
	        'index/login',
	        'index/login/save',
            'index/reg',
	        'index/reg/save',
	        'index/code',
            'index/pass',
            'index/lost',
            'index/lost/save',
            'index/edit',
            'index/edit/save',
            'queue/email',
            'queue/sms',
            'table/create',
            'table/build',
        );
        return in_array($strUrl,$arrUrl);
    }

} 