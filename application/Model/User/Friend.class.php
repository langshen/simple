<?php
namespace Model\User;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

class Friend extends Model
{
    //我的好友的读取
    public function selectFriend(){
        if ($this->userInfo['id'] < 1){
            return array('登录超时。',0);
        }
        $options = Array(
            'alias'=>'a',
            'field'=>'a.id,f_name,vip,sex,face,total_gold as gold,vip_exp,exp',
            'where'=>Array(
                'a.user_id'=>$this->userInfo['id'],
            ),
            'join'=>Array(
                '@.member_info b ON a.f_user_id=b.user_id',
                '@.member_account c ON a.f_user_id=c.user_id',
            ),
            'limit'=>max(1,$this->getData('page_size',1)),
            'page'=>max(1,$this->getData('page',1)),
            'order'=>'vip desc,a.id desc',
        );
        $intCount = $this->Db()->find('user_friend',$options,'count(a.id)');
        $arrFriend = $this->Db()->select('user_friend',$options);
        !$arrFriend && $arrFriend = [];
        return Array('成功',1,['data'=>$arrFriend,'total'=>$intCount]);
    }

    //添加好友
    public function addFriend(){
        if ($this->userInfo['id'] < 1){
            return array('登录超时。',0);
        }
        $strName = $this->getData('friend_name','');
        //收款人信息
        $options = Array(
            'where'=>Array(),
            'field'=>'id,user_name',
        );
        if (stripos($strName,'@') > 0){
            $options['where']['email'] = $strName;
        }elseif (is_numeric($strName) && mb_strlen($strName,'utf-8') == 11){
            $options['where']['mobile'] = $strName;
        }else{
            $options['where']['user_name'] = $strName;
        }
        $arrUserInfo = $this->Db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('帐号：'.$strName.'不存在，无法添加。',0);
        }
        if ($this->userInfo['id'] == $arrUserInfo['id']){
            return Array('您无法添加自己为好友。',0);
        }
        $this->Db()->startTrans('User.addFriend');
        $options = Array(
            'where'=>Array(
                'user_id'=>$this->userInfo['id'],
            )
        );
        $intCount = $this->Db()->find('user_friend',$options,'count(id)');
        if ($intCount > 200){
            $this->Db()->rollback();
            return Array('您的好友数已经到达上限，无法继续添加。',0);
        }
        $options = Array(
            'where'=>Array(
                'user_id'=>$arrUserInfo['id'],
            )
        );
        $intCount = $this->Db()->find('user_friend',$options,'count(id)');
        if ($intCount > 200){
            $this->Db()->rollback();
            return Array('对方的好友数已经到达上限，添加失败。',0);
        }
        $arrData = Array(
            'user_id'=>$this->userInfo['id'],
            'f_user_id'=>$arrUserInfo['id'],
            'f_name'=>$arrUserInfo['user_name'],
            'status'=>2,
            'add_time'=>time(),
        );
        $result = $this->Db()->insert('user_friend',$arrData);
        if ($result === false){
            $this->Db()->rollback();
            return Array('添加好友失败。',0);
        }
        $arrData = Array(
            'user_id'=>$arrUserInfo['id'],
            'f_user_id'=>$this->userInfo['id'],
            'f_name'=>$this->userInfo['user_name'],
            'status'=>2,
            'add_time'=>time(),
        );
        $result = $this->Db()->insert('user_friend',$arrData);
        if ($result === false){
            $this->Db()->rollback();
            return Array('操作添加好友失败。',0);
        }
        $this->Db()->commit('User.addFriend');
        return Array('添加好友成功。',1);
    }

    //删除好友
    public function delFriend(){
        if ($this->userInfo['id'] < 1){
            return array('登录超时。',0);
        }
        $strFriendId = $this->getData('ids','');
        if (!is_numeric(str_replace(',','',$strFriendId))){
            return array('操作数据异常，操作失败。',0);
        }
        $options = Array(
            'where'=>Array(
                'id'=>Array('in',$strFriendId),
                'user_id'=>$this->userInfo['id'],
            ),
        );
        $result = $this->Db()->delete('user_friend',$options);
        if ($result === false){
            return Array('删除好友失败。',0);
        }
        return Array('操作成功。',1);
    }



}