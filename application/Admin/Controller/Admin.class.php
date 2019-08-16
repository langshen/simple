<?php
namespace Admin\Controller;
use Admin\Common\Control;

defined('APP_NAME') or die('404 Not Found');

class Admin extends Control {

    /**
     * 修改个人信息
     */
    public function info(){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'admin_users';
        switch ($strAction){
            case 'index':
                $options = Array(
                    'field' => 'a.*,b.name as group_name',
                    'join' => '@.admin_user_group as b on b.id=a.group_id',
                    'where'=>Array(
                        'a.id'=>$this->adminInfo['id'],
                        'user_name'=>$this->adminInfo['user_name'],
                    )
                );
                $arrInfo = dal($strTable)->find($options);
                if ($arrInfo){
                    $arrStatus = Array(1 => '正常', 2 => '禁用');
                    $arrInfo['status_name'] = $arrStatus[$arrInfo['status']];
                    $arrInfo['face'] = $arrInfo['face'] != ''?attachUrl($arrInfo['face']):'';
                }
                $this->assign('info',$arrInfo);
                return $this->fetch('info');
                break;
            case 'pass':
                return $this->fetch('info');
                break;
            case 'savepass':
                $arrData = Array(
                    'old_pass_word'=>['require|length:6,32','请输入正确的原始密码[6-32位]。'],
                    'pass_word'=>['require|length:6,32','请输入正确的新密码密码[6-32位]。'],
                    're_pass_word'=>['require|confirm:pass_word','二次输入的新密码不一样。'],
                );
                if (!valid($arrData,$message)){
                    return $this->toApi($message);
                }
                if (is_numeric($arrData['pass_word']) && mb_strlen($arrData['pass_word'],'utf-8') <= 8){
                    return $this->toApi('纯数字密码不能少于9位！');
                }
                $options = array(
                    'where' => array(
                        'id' => $this->adminInfo['id'],
                        'user_name' => $this->adminInfo['user_name'],
                        'pass_word' => md5("{$this->adminInfo['user_name']}={$arrData['old_pass_word']}")
                    )
                );
                $intCount = dal($strTable)->find($options,'count');
                if ($intCount < 1){
                    return $this->toApi('原始密码不正确，无法修改。');
                }
                $options = array(
                    'where' => array('id'=>$this->adminInfo['id'],'user_name'=>$this->adminInfo['user_name'])
                );
                $arrData = array(
                    'pass_word'=>md5("{$this->adminInfo['user_name']}={$arrData['pass_word']}")
                );
                $result = dal($strTable)->updateField($arrData,$options);
                if ($result === false){
                    $this->toApi('更新失败，请重新修改！');
                }
                session('admin_info',null);
                $this->toApi('密码修改成功，请重新登录！',0);
                break;
            case 'saveinfo':
                $arrData = Array(
                    'real_name'=>['require|chs|length:2,16','请输入中文的真实姓名。'],
                    'email'=>['require|email|length:5,80','请输入正确的电子邮箱。'],
                );
                if (!valid($arrData,$message)){
                    return $this->toApi($message);
                }
                $options = array(
                    'where'=>array('id' =>$this->adminInfo['id'],'user_name'=>$this->adminInfo['user_name'])
                );
                $result = dal($strTable)->updateField($arrData,$options);
                if ($result === false){
                    return $this->toApi('更新失败，请重新修改！');
                }
                $this->adminInfo['real_name'] = $arrData['real_name'];
                $this->adminInfo['email'] = $arrData['email'];
                session('admin_info',$this->adminInfo);
                return $this->toApi('修改成功！',0);
                break;
            case 'saveface':
                $objFaceFile = request()->file('face_file');
                if (!$objFaceFile){
                    return $this->toApi('请选择图片。');
                }
                if (!$objFaceFile->checkImg()){
                    return $this->toApi('请上传图片格式。');
                }
                $clsFile = $objFaceFile->move(attachPath().'admin'.DS.'face',$this->adminInfo['id']);
                if (!$clsFile){
                    return $this->toApi('上传文件失败：'.$objFaceFile->getError());
                }
                $strFileUrl = str_replace('\\','/',$clsFile->getPath().'/'.$clsFile->getFilename());
                $strFileUrl = str_replace(attachPath(),'',$strFileUrl);
                $options = array(
                    'where' => array(
                        'id' => $this->adminInfo['id'],
                        'user_name' => $this->adminInfo['user_name']
                    )
                );
                $arrData = array(
                    'face' => $strFileUrl
                );
                $arrResult = dal($strTable)->updateField($arrData,$options);
                if ($arrResult[1] != 0){
                    $this->toApi('更新失败，请重新上传！');
                }
                $this->adminInfo['face'] = $strFileUrl;
                session('admin_info',$this->adminInfo);
                return $this->toApi('上传成功！',0,['url'=>attachUrl($strFileUrl)]);
                break;
        }
        return null;
    }

    /**
     * 我的的登录日志
     */
    public function myLog(){
        return $this->loginLog('my');
    }

    /**
     * 所有管理员的登录日志
     */
    public function allLog(){
        return $this->loginLog('all');
    }

    /**
     * 所有管理员日志
     * @param $strType string
     * @return mixed
     */
    private function loginLog($strType = 'my'){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'admin_login_log';
        switch ($strAction){
            case 'list':
                $options = Array('order'=>'id desc',);
                $strType == 'my' && $options['where']['user_name'] = $this->adminInfo['user_name'];
                $arrInfo = dal($strTable)->setConfig(['count'=>true])->select($options);
                return $this->tableList($arrInfo);
                break;
            case 'del':
                $options = Array('where'=>Array('id'=>request()->param('id')));
                $strType == 'my' && $options['where']['user_name'] = $this->adminInfo['user_name'];
                $arrResult = dal($strTable)->setConfig(['array'=>true])->delete($options);
                return $this->toApi($arrResult);
                break;
        }
        $arrSearchType = dal($strTable)->getSearchCondition();
        $arrSymbol = dal($strTable)->getSearchSymbol();
        $this->assign('search_type',$arrSearchType);
        $this->assign('search_symbol',$arrSymbol);
        $this->assign('type',$strType);
        return $this->fetch('loginLog');
    }

    /**
     * 管理菜单
     */
    public function menu(){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'admin_menu';
        switch ($strAction){
            case 'list':
                $options = Array(
                    'order'=>'a.id desc',
                    'field'=>'a.*,b.name as pid_name',
                    'join'=>Array(
                        '@.admin_menu b on a.pid=b.id'
                    )
                );
                $arrInfo = dal($strTable)->setConfig(['count'=>true])->select($options);
                return $this->tableList($arrInfo);
                break;
            case 'select':
                $options = Array(
                    'field'=>Array('id,pid,name,url'),
                    'where'=>Array('pid'=>0),
                    'order'=>'id desc',
                    'limit'=>1000
                );
                $intPid = max(0,request()->param('pid',0));
                $intPid > 0 && $options['where']['pid'] = $intPid;
                $arrInfo = dal($strTable)->setConfig(['array'=>true])->select($options);
                return $this->toApi($arrInfo);
                break;
            case 'check'://权限选中

                break;
            case 'view':
            case 'edit':
            case 'add':
                $intId = max(0,intval(request()->param('id')));
                if ($intId > 0){
                    $options = Array('where'=>Array('id'=>$intId));
                    $arrInfo = dal($strTable)->find($options);
                }else{
                    $arrInfo = [];
                }
                $this->assign('info',$arrInfo);
                break;
            case 'del':
                $options = Array('where'=>Array('id'=>request()->param('id')));
                $arrInfo = dal($strTable)->setConfig(['array'=>true])->delete($options);
                return $this->toApi($arrInfo);
                break;
            case 'save':
                $arrData = dal($strTable)->arrRequire;
                if (!valid($arrData,$message)){
                    return $this->toApi($message);
                }
                $arrResult = dal($strTable)->setConfig(['array'=>true])->update($arrData);
                return $this->toApi($arrResult);
                break;
        }
        $arrSearchType = dal($strTable)->getSearchCondition();
        array_unshift($arrSearchType,Array('id'=>'b.name','text'=>'父级名称'));
        $arrSymbol = dal($strTable)->getSearchSymbol();
        $this->assign('search_type',$arrSearchType);
        $this->assign('search_symbol',$arrSymbol);
        return $this->fetch('menu');
    }

    /**
     * 管理员用户组
     */
    public function userGroup(){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'admin_user_group';
        switch ($strAction){
            case 'list':
                $options = Array(
                    'order'=>'a.id desc',
                    'field'=>'a.*,b.name as pid_name',
                    'join'=>Array(
                        '@.admin_user_group b on a.pid=b.id',
                    )
                );
                $arrInfo = dal($strTable)->setConfig(['count'=>true])->select($options);
                return $this->tableList($arrInfo);
                break;
            case 'select':
                $options = Array(
                    'field'=>Array('id,pid,name,tip'),
                    'order'=>'id desc',
                    'limit'=>1000
                );
                $arrInfo = dal($strTable)->setConfig(['array'=>true])->select($options);
                return $this->toApi($arrInfo);
                break;
            case 'view':
            case 'edit':
            case 'add':
                $intId = max(0,intval(request()->param('id')));
                if ($intId > 0){
                    $options = Array('where'=>Array('id'=>$intId));
                    $arrInfo = dal($strTable)->find($options);
                }else{
                    $arrInfo = [];
                }
                $this->assign('info',$arrInfo);
                return $this->fetch('userGroup');
                break;
            case 'del':
                $options = Array('where'=>Array('id'=>request()->param('id')));
                $arrResult = dal($strTable)->setConfig(['array'=>true])->delete($options);
                return $this->toApi($arrResult);
                break;
            case 'save':
                $arrData = dal($strTable)->arrRequire;
                if (!valid($arrData,$message)){
                    return $this->toApi($message);
                }
                $arrResult = dal($strTable)->setConfig(['array'=>true])->update($arrData);
                return $this->toApi($arrResult);
                break;
        }
        $arrSearchType = dal($strTable)->getSearchCondition();
        $arrSymbol = dal($strTable)->getSearchSymbol();
        $this->assign('search_type',$arrSearchType);
        $this->assign('search_symbol',$arrSymbol);
        return $this->fetch('userGroup');
    }

    /**
     * 管理员的增删改
     */
    public function users(){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'admin_users';
        switch ($strAction){
            case 'list':
                $options = Array(
                    'field'=>'a.*,b.name,b.tip',
                    'join'=>Array(
                        '@.admin_user_group b ON b.id = a.group_id'
                    ),
                    'order'=>'a.id desc',
                );
                $arrInfo = dal($strTable)->setConfig(['count'=>true])->select($options);
                return $this->tableList($arrInfo);
                break;
            case 'select':
                $options = Array(
                    'field'=>Array(
                        'id,user_name,real_name'
                    ),
                    'order'=>'id desc',
                    'limit'=>1000
                );
                $arrInfo = dal($strTable)->setConfig(['array'=>true])->select($options);
                return $this->toApi($arrInfo);
                break;
            case 'view':
            case 'edit':
            case 'add':
                $intId = max(0,intval(request()->param('id')));
                if ($intId > 0){
                    $options = Array('where'=>Array('id'=>$intId));
                    $arrInfo = dal($strTable)->find($options);
                }else{
                    $arrInfo = [];
                }
                $this->assign('info',$arrInfo);
                break;
            case 'del':
                $options = Array('where'=>Array('id'=>request()->param('id')));
                $arrResult = dal($strTable)->setConfig(['array'=>true])->delete($options);
                return $this->toApi($arrResult);
                break;
            case 'save':
                $arrData = dal($strTable)->arrRequire;
                if (!valid($arrData,$message)){
                    return $this->toApi($message);
                }
                if (!isset($arrData['id']) || !$arrData['id']){
                    if (!isset($arrData['pass_word']) || !$arrData['pass_word']){
                        return $this->toApi('请输入6位以上密码。');
                    }
                    $intCount = dal($strTable)->find(
                        ['where'=>Array('user_name'=>$arrData['user_name'])],'count'
                    );
                    if ($intCount > 0){
                        return $this->toApi($arrData['user_name'].' 已经存在，请更换。');
                    }
                    if (isset($arrData['pass_word']) && $arrData['pass_word']){
                        if ($arrData['pass_word'] != request()->post('re_pass_word')){
                            return $this->toApi('两次输入的密码不一致，请确认。');
                        }
                        $arrData['pass_word'] = md5("{$arrData['user_name']}={$arrData['pass_word']}");
                    }
                }
                $arrResult = dal($strTable)->setConfig(['array'=>true])->update($arrData);
                return $this->toApi($arrResult);
                break;
        }
        $arrSearchType = dal($strTable)->getSearchCondition();
        $arrSymbol = dal($strTable)->getSearchSymbol();
        $this->assign('search_type',$arrSearchType);
        $this->assign('search_symbol',$arrSymbol);
        return $this->fetch('users');
    }

}