<?php
namespace Admin\Controller;
use Admin\Common\Control;
use Spartan\Lib\Image;

defined('APP_NAME') or die('404 Not Found');

class Index extends Control {

    /**
     * 控制台
     * @return mixed
     */
    public function index(){
        if (!$this->adminInfo){
            return $this->redirect(adminUrl().'/index/login.html');
        }

        $this->assign('admin_info',$this->adminInfo);
        return $this->fetch();
    }

    /**
     * 首页
     */
    public function home(){
        if (!$this->adminInfo){
            return $this->redirect(adminUrl().'/index/login.html');
        }

        return $this->fetch();
    }

    /**
     * 登录界面和登录操作
     */
    public function login(){
        if($this->getUrl(2,'default') != "save"){
            return $this->fetch('login');
        }
        //如果是提交
        $arrData = Array(
            'user_name'=>Array('require|length:2,32','请输入正确的用户名。'),
            'pass_word'=>Array('require|length:6,32','请输入正确的密码。'),
            'ver_code'=>Array('require|same:'.session('ver_code'),'请输入正确的验证码。'),
            'ip'=>request()->ip(),
            'add_time'=>date('Y-m-d H:i:s'),
        );
        session('ver_code',null);
        if (!valid($arrData,$message)){
            return $this->toApi($message);
        }
        $arrInfo = model('admin_users',$arrData)->login();
        if ($arrInfo[1] === 0){
            session('admin_info', $arrInfo[2]);
            unset($arrInfo[2]);
            return $this->toApi($arrInfo);
        }elseif ($arrInfo[1] === 2){
            session("admin_edit", $arrInfo[2]);
            unset($arrInfo[2]);
            return $this->toApi($arrInfo);
        }else{
            return $this->toApi($arrInfo);
        }
    }

    /**
     * 注册界面和注册操作
     */
    public function reg(){
        if($this->getUrl(2,'default') != "save"){
            return $this->fetch('reg');
        }
        //如果是提交
        $arrData = Array(
            'user_name'=>Array('require|length:2,32','请输入正确的用户名。'),
            'real_name'=>Array('require|chs|length:2,10','请输入正确的姓名（中文）。'),
            'pass_word'=>Array('require|length:8,32','请输入正确的密码[8-32位]。'),
            're_pass_word'=>Array('require|confirm:pass_word','两次输入密码不一致。'),
            'email'=>Array('require|email|length:6,80','请输入正确的邮箱。'),
            'ver_code'=>Array('require|same:'.session('ver_code'),'请输入正确的验证码。'),
            'login_ip'=>request()->ip(),
            'add_time'=>date('Y-m-d H:i:s'),
        );
        session('ver_code',null);
        if (!valid($arrData,$message)){
            return $this->toApi($message);
        }
        $arrInfo = model('admin_users',$arrData)->register();
        return $this->toApi($arrInfo);
    }

    /**
     * 找回密码界面和找回密码操作
     */
    public function lost(){
        if($this->getUrl(2,'default') != "save"){
            return $this->fetch('lost');
        }
        //如果是提交
        $arrData = Array(
            'user_name'=>Array('require|length:2,32','请输入正确的用户名。'),
            'email'=>Array('require|email|length:6,80','请输入正确的邮箱。'),
            'email_code'=>Array('require|length:4,8','请输入正确的邮箱验证码。'),
            'ver_code'=>Array('require|same:'.session('ver_code'),'请输入正确的验证码。'),
        );
        session('ver_code',null);
        if (!valid($arrData,$message)){
            return $this->toApi($message);
        }
        $arrResult = model('admin_users',$arrData)->verifyLostPass();
        if ($arrResult[1] === 0){
            session('admin_info',null);
            session('admin_', $arrResult[2]);
            session('admin_edit', $arrResult[2]);
            unset($arrResult[2]);
        }
        return $this->toApi($arrResult);
    }

    /**
     * 显示验证码。
     */
    public function code(){
        return Image::instance();
    }

    /**
     * 退出登录
     */
    public function logout(){
        session('admin_info', null);
        session('admin_edit', null);
        if (request()->isAjax()){
            return $this->toApi('success',0);
        }else{
            return $this->display("<script language='javascript'>window.parent.location.href='".adminUrl()."/index/login.html';</script>退出登录。。");
        }
    }

    /**
     * 修改用户初始密码。
     */
    public function edit(){
        $arrInfo = session("admin_edit");
        if(!$arrInfo){
            $this->error("登录超时，请重新登录!",adminUrl().'/index/login.html');
        }
        $action = $this->getUrl(2,'default');
        if ($action != "save"){
            $this->assign('info',$arrInfo);
            return $this->fetch('edit');
        }
        $arrData = Array(
            'pass_word'=>Array('require|length:8,32','请输入正确的密码[8-32位]！'),
            're_pass_word'=>Array('require|confirm:pass_word','两次输入密码不一致。'),
            'id'=>$arrInfo['id'],
            'user_name'=>$arrInfo['user_name'],
        );
        if (!valid($arrData,$message)){
            return $this->toApi($message);
        }
        $arrResult = model('admin_users',$arrData)->editPass();
        if ($arrResult[1] === 0){
            session('admin_edit',null);
            session('admin_info',null);
        }
        return $this->toApi($arrResult);
    }


}