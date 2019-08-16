<?php
namespace Model\Admin;
use Model\System\Verify;
use Spartan\Lib\Model;

defined('APP_NAME') or die('404 Not Found');

class Users extends Model
{
    /**
     * 系统用户登录
     * @return array
     */
    public function login(){
        $arrData = $this->getFieldData('user_name,pass_word,ip,add_time');
        $options = array(
            'alias'=>'a',
            'field' =>'a.real_name,a.user_name,a.id,email,face,b.menu_list,'.
                'b.name AS group_name,b.power_list,a.user_menu,a.user_power,a.status,b.id AS group_id,'.
                'login_time as per_login_time,login_ip as per_login_ip',
            'join' =>Array(
                'INNER JOIN @.admin_user_group AS b ON a.group_id = b.id'
            ),
            'where' =>Array(
                "user_name" => $arrData['user_name'],
                'pass_word' => md5("{$arrData['user_name']}={$arrData['pass_word']}")
            ),
        );
        $arrInfo = db()->find('admin_users',$options);
        if (!$arrInfo){
            $this->getModel('admin_login_log')->setData($arrData)->saveLog();
            return Array('用户名和密码错误。',1);
        }
        if ($arrInfo['status'] != 1 ){
            $arrData['pass_word'] = '锁定后尝试登录。';
            $this->getModel('admin_login_log')->setData($arrData)->saveLog();
            return Array('用户已经被锁定，无法登录。',1);
        }
        if (is_numeric($arrData['pass_word']) && mb_strlen($arrData['pass_word'],'utf-8') <= 8){
            return Array('密码简单，要求更改。',2,Array('id'=>$arrInfo['id'],'user_name'=>$arrInfo['user_name']));
        }
        $arrInfo['user_menu'] && $arrInfo['menu_list'] .= ',' . $arrInfo['user_menu'];
        $arrInfo['user_power'] && $arrInfo['power_list'] .= '@' . $arrInfo['user_power'];
        $temp = explode(',',$arrInfo['menu_list']);
        array_unique($temp);$arrInfo['menu_list'] = implode(',',$temp);
        $temp = explode(',',$arrInfo['power_list']);
        array_unique($temp);$arrInfo['power_list'] = implode(',',$temp);
        unset($arrInfo['user_menu'],$arrInfo['user_power']);
        $arrInfo['login_time'] = $arrData['add_time'];
        $arrInfo['login_ip'] = $arrData['ip'];
        substr($arrInfo['power_list'],0,3)=='all' && $arrInfo['power_list'] = 'all';
        substr($arrInfo['power_list'],0,4)=='view' && $arrInfo['power_list'] = 'view';
        //**修改登录信息//
        $arrDataLog = Array(
            'login_ip'=>$arrData['ip'],
            'login_time'=>$arrData['add_time'],
            'login_count'=>Array('exp','login_count+1'),
        );
        $options = Array(
            'where'=>Array('id'=>$arrInfo['id']),
        );
        $result = db()->update('admin_users',$arrDataLog,$options);
        if ($result == false){
            $arrDataLog['level'] = 1;
            $arrDataLog['info'] = '更新管理员登录信息错误';
            $this->sysError($arrDataLog);
        }
        //** 加入成功的记录 */
        $arrData['status'] = 1;
        $this->getModel('admin_login_log')->setData($arrData)->saveLog();
        return Array('登陆成功', 0, $arrInfo);
    }

    /**
     * 系统用户注册
     * @return array
     */
    public function register(){
        $arrData = $this->getFieldData('user_name,real_name,email,pass_word,login_ip,add_time');
        $arrData['group_id'] = 0;
        $arrData['pass_word'] = md5("{$arrData['user_name']}={$arrData['pass_word']}");
        $arrData['status'] = 0;
        $options = Array(
            'where'=>Array(
                'user_name'=>$arrData['user_name'],
            )
        );
        $arrInfo = db()->find('admin_users',$options);
        if ($arrInfo){
            return Array('管理用户名已经存在，请更换。', 1);
        }
        //这里可以设置默认开通的组
        //$arrData['group_id'] = 1;
        //$arrData['status'] = 1;
        $result = db()->insert('admin_users',$arrData);
        if ($result === false){
            return Array('管理用户插入失败，请联系管理员。', 1);
        }
        return Array('提交注册成功，请稍等超级管理员审核并开通。',0);
    }

    /**
     * 验证用户找回密码
     */
    public function verifyLostPass(){
        $arrData = $this->getFieldData('user_name,email,email_code');
        $options = Array(
            'where'=>Array(
                'user_name'=>$arrData['user_name'],
                'email'=>$arrData['email'],
            )
        );
        $arrAdminInfo = db()->find('admin_users',$options);
        if (!$arrAdminInfo){
            return Array('登录用户名对应的电子邮件不正常，请确认。',0);
        }
        $arrVerifyData = Array(
            'send_to'=>$arrData['email'],
            'send_type'=>2,
            'rad_code'=>$arrData['email_code'],
            'user_id'=>$arrAdminInfo['id'],
            'tpl_key'=>'admin_lost_pwd',
            'status'=>1,
            'verify_tip'=>'邮箱',
        );
        /** @var Verify $clsVerify */
        $clsVerify = Verify::init($arrVerifyData);
        $arrResult = $clsVerify->verify();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        $arrResult = $clsVerify->invalid();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        return Array('验证成功，请修改密码。',0,$arrAdminInfo);
    }

    /**
     * 修改用户的初始密码
     */
    public function editPass(){
        $arrData = $this->getFieldData('id,user_name,pass_word');
        if (!$arrData['id']){
            return Array('ID丢失。',1);
        }
        if (!$arrData['user_name']){
            return Array('user_name丢失。',1);
        }
        if (!$arrData['pass_word']){
            return Array('pass_word丢失。',1);
        }
        $arrData['pass_word'] = md5("{$arrData['user_name']}={$arrData['pass_word']}");
        $options = Array(
            'where'=>Array(
                'id'=>$arrData['id'],
                'user_name'=>$arrData['user_name'],
            ),
        );
        unset($arrData['id'],$arrData['user_name']);
        $result = db()->update('admin_users',$arrData,$options);
        if ($result === false){
            return Array('修改失败，请重新再试！',1);
        }
        return Array('密码修改成功，您需要重新登录！',0);
    }

}