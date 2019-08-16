<?php
namespace Model\Member;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

/**
 * 用户的登录或注册
 * member_type,用户类型，可设置为:小程序和公众号(xcx),APP(app),自主网站(web)
 * Class Auth
 */
class Auth extends Model
{

    public $arrConfig = [
        'pass_key'=>'{@}[+]',//密码串前缀
        'reg_login'=>true,//注册后自动登录
        'open_recommend'=>true,//启用用户推荐关系
    ];

    /**
     * 取得登录时，要求统一的获取信息
     * @return array
     */
    private function getLoginOption(){
        return Array(
            'alias' => 'a',
            'field' => 'a.*',
        );
    }

    /**
     * 自动登录，且自动注册，专用于小程序、公众号
     * @return array
     */
    public function autoLogin(){
        $arrTemp = $this->getFieldData('openId,open_id,openid');
        $strOpenId = $this->choose([$arrTemp['openId'],$arrTemp['open_id'],$arrTemp['openid']]);
        if (!$strOpenId){
            return Array('openId丢失，请重新登录。',1);
        }
        $options = $this->getLoginOption();
        $options['where'] = Array('wx_open_id'=>$strOpenId);
        $arrMemberInfo = db()->find('member_auth',$options);
        if (!$arrMemberInfo){//还没有用户，注册一个并返回
            $this->setData(['member_type'=>'xcx']);
            return $this->addUser();
        }else{
            $this->loginInfo($arrMemberInfo,1);
            return Array('自动登录成功',0,$arrMemberInfo);
        }
    }

    /**
     * 主动登录，可用手机、邮箱、用户名登录
     * @return array
     */
    public function login(){
        $arrData = $this->getFieldData('user_name,pass_word');
        if (!$arrData['user_name'] || !$arrData['pass_word']){
            return Array('登录名或密码不能为空。',1);
        }
        $options = $this->getLoginOption();
        $options['where']['pass_word'] = md5($this->getConfig('pass_key').$arrData['pass_word']);
        if (isMobile($arrData['user_name'])){
            $options['where']['mobile'] = $arrData['user_name'];
        }elseif (isEmail($arrData['user_name'])){
            $options['where']['email'] = $arrData['user_name'];
        }else{
            $options['where']['user_name'] = $arrData['user_name'];
        }
        $arrInfo = db()->find('member_auth',$options);
        if (!$arrInfo){
            return Array('用户名和密码错误！',1);
        }
        if ($arrInfo['status'] !== 1 ){
            $this->loginInfo($arrInfo);
            return Array('用户已经被锁定，无法登录!',1);
        }
        $this->loginInfo($arrInfo,1);
        return Array('登录成功！',0,$arrInfo);
    }

    /**
     * 添加一个用户，小程序、公众号、正常注册
     * @return array
     */
    public function addUser(){
        $clsAuth = $this->getTable('member_auth');
        if (!is_object($clsAuth)){
            return Array('请先生成member_auth表Model。',1);
        }
        $arrFields = $this->getTableFieldsToFieldData($clsAuth->arrFields);
        if (!$arrFields){
            return Array('member_auth表结构异常。',1);
        }
        $arrData = $this->getFieldData($arrFields);
        !$arrData['reg_time'] && $arrData['reg_time'] = date('Y-m-d H:i:s');
        if ($arrData['id']){
            return Array('不要提交ID值，修改资料请用saveAuthInfo',1);
        }
        unset($arrData['id']);
        $strMemberType = $this->getData('member_type','web');
        //以上得到完整的表结构值及默认值
        if ( $strMemberType == 'xcx'){//小程序、公众号的注册
            $arrFields = Array(
                'openId'=>'',//小程序
                'unionId'=>'',//开放平台
                'nickName'=>'',//小程序
                'openid'=>'',//公众号
                'open_id'=>'',//公众号
                'nickname'=>'',//小程序
                'avatarUrl'=>'',//小程序
                'headimgurl'=>'',//公众号
                'unionid'=>'',//开放平台
                'union_id'=>'',//开放平台
                'gender'=>0,//小程序
                'country'=>'',//小程序
                'province'=>'',//小程序
                'city'=>'',//小程序
                'address'=>'',//小程序
            );
            $arrTemp = $this->getFieldData($arrFields);
            $arrTempData = Array(
                'wx_open_id'=>$this->choose([$arrTemp['openId'],$arrTemp['openid'],$arrTemp['open_id']]),
                'wx_union_id'=>$this->choose([$arrTemp['unionId'],$arrTemp['unionid'],$arrTemp['union_id']]),
                'nick_name'=>$this->choose([$arrTemp['nickname'],$arrTemp['nickName']]),
                'face'=>$this->choose([$arrTemp['avatarUrl'],$arrTemp['headimgurl']]),
                'sex'=>$arrTemp['gender'],
                'address'=>$arrTemp['country'].';'.$arrTemp['province'].';'.$arrTemp['city'].';'.$arrTemp['address']
            );
            $arrData = array_merge($arrData,$arrTempData);
            if (!$arrData['wx_open_id']){
                return Array('openId丢失，请重新授权登录。',1);
            }
        }
        if (!$arrData['user_name']){
            $arrData['user_name'] = $this->choose([$arrData['mobile'],$arrData['email'],trim($arrData['nick_name'])]);
        }
        if (!$arrData['user_name']){
            return Array('用户名为空。',1);
        }
        if ($arrData['pass_word'] && mb_strlen($arrData['pass_word'],'utf-8') != 32){
            $arrData['pass_word'] = md5($this->getConfig('pass_key').$arrData['pass_word']);
        }else{
            unset($arrData['pass_word']);
        }
        if ($arrData['pay_word'] && mb_strlen($arrData['pay_word'],'utf-8') != 32){
            $arrData['pay_word'] = md5($this->getConfig('pass_key').$arrData['pay_word']);
        }else{
            unset($arrData['pay_word']);
        }
        !$arrData['nick_name'] && $arrData['nick_name'] = $arrData['user_name'];
        !$arrData['source'] && $arrData['source'] = $this->getConfig('member_type');
        if (!$arrData['recommend_code']){//用户的推荐码
            $tempCode = $this->choose([$arrData['wx_open_id'],$arrData['user_name'],microtime(true)]);
            $arrData['recommend_code'] = substr(md5($tempCode), 8, 16);//用户推荐码
        }
        $options = Array(
            'field'=>'user_name,email,mobile,wx_open_id',
            'where'=>Array('_logic'=>'OR',)
        );
        $arrData['user_name'] && $options['where']['user_name'] = $arrData['user_name'];
        $arrData['email'] && $options['where']['email'] = $arrData['email'];
        $arrData['mobile'] && $options['where']['mobile'] = $arrData['mobile'];
        $arrData['wx_open_id'] && $options['where']['wx_open_id'] = $arrData['wx_open_id'];
        $arrAuthInfo = db()->find('member_auth',$options);
        if ($arrAuthInfo){
            $arrTip = ['user_name'=>'用户名','email'=>'邮箱','mobile'=>'手机','wx_open_id'=>'微信'];
            foreach ($arrAuthInfo as $k=>$v){
                if ($v){
                    return Array($arrTip[$k].'已经存在，请更换。'.$v,1);
                }
            }
            return Array('什么东西存在要更换？',1);
        }
        //开始入库操作
        db()->startTrans('Auth.addUser');
        $intUserId = max(0,db()->insert('member_auth',$arrData));
        if ($intUserId < 1){
            db()->rollback();
            return Array('用户登录失败，请联系管理员。',1);
        }
        $arrData['user_id'] = $intUserId;
        $this->setData($arrData);
        //添加用户详细信息
        $arrResult = $this->initUserInfo();
        if ($arrResult[1] !== 0){
            db()->rollback();
            return Array('帐号初始化失败，请联系管理员。',1);
        }
        //与注册用户有关的结束操作，操作失败即注册失败的情况 todo
        $arrResult = $this->addUserEnd();
        if ($arrResult[1] !== 0){
            db()->rollback();
            return $arrResult;
        }
        db()->commit('Auth.addUser');
        //用户注册成功之后的其它操作，操作失败也不会影响注册，比如：发激活邮件 todo
        $this->addUserFinish();
        //会员推荐关系
        if ($this->getConfig('open_recommend') == true){
            $arrTempData = $this->getFieldData([
                'user_id'=>$intUserId,
                'user_name'=>$arrData['user_name'],
                'recommend_user_name'=>'',
                'recommend_email'=>'',
                'recommend_mobile'=>'',
                'recommend_name'=>'',
            ]);
            Relations::init($arrTempData)->addRecommend();
        }
        //自动登录
        $arrMemberInfo = [];
        if ($this->getConfig('reg_login') == true){
            $options = $this->getLoginOption();
            $options['where'] = Array('a.id'=>$intUserId);
            $arrMemberInfo = db()->find('member_auth',$options);
            !$arrMemberInfo && $arrMemberInfo = [];
        }
        return Array('注册并登录成功',0,$arrMemberInfo);
    }

    /**
     * 初始化一个用户
     * @return array
     */
    private function initUserInfo(){
        $strForm = $this->getData('user_form','reg');//初始化的来源，注册或后台添加的
        $intUserId = max(0,$this->getData('user_id',0));
        $strIp = request()->ip();//IP
        db()->startTrans('Auth.initUserInfo');
        $iniTable = Array(
            'member_account' => Array(//'用户账号表',
                'user_id'=>$intUserId,
            ),
            'member_login_info' => Array(//'用户登录信息表',
                'user_id'=>$intUserId,
                'reg_ip'=>$strIp,
                'reg_time'=>$this->getData('reg_time'),
            ),
            'member_login_log'=>Array(
                'user_id'=>$intUserId,
                'login_time'=>$this->getData('reg_time'),
                'login_ip'=>$strIp,
                'user_agent' => request()->server('HTTP_USER_AGENT'),
                'has_mobile' => request()->isMobile()?1:2,
                'status'=>1,
            ),
        );
        if ($strForm != 'reg'){//后台注册的用户，不添加登录日志
            unset($iniTable['member_login_log']);
        }
        foreach ($iniTable as $k => $v){
            $result = db()->insert($k, $v);
            if ($result === false){
                db()->rollback();
                return Array("{$k}添加出错：".db()->getLastSql(), 1);
            }
        }
        db()->commit('Auth.initUserInfo');
        return Array('初始化完成',0);
    }

    /**
     * 添加用户的最后操作 todo
     * @return array
     */
    public function addUserEnd(){

        return Array('end',0);
    }

    /**
     * 添加用户成功之后的操作 todo
     * @return array
     */
    public function addUserFinish(){

        return Array('Finish',0);
    }

    /**
     * 检查某个字段是否为空
     * 0存在且不为空，1为空或不存在
     */
    public function checkFieldEmpty(){
        $arrData = $this->getFieldData('user_id,field');
        if (!$arrData['user_id']){
            return Array('用户ID不能为空。',1);
        }
        !$arrData['field'] && $arrData['field'] = 'pay_word';
        $options = Array('where' => array('id' => $arrData['user_id']));
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return array('没有此用户信息',1);
        }
        return Array('检查结果',0,[ // 0存在且不为空，1为空或不存在
            $arrData['field']=>(isset($arrUserInfo[$arrData['field']]) && $arrUserInfo[$arrData['field']])?0:1
        ]);
    }

    /**
     * 检查用户是否可用
     * 0为不存在，
     * @return mixed
     */
    public function checkMemberExists(){
        $arrData = $this->getFieldData('user_name,field');
        if (!$arrData['user_name']){
            return Array('检查值为空。',1);
        }
        !$arrData['field'] && $arrData['field'] = 'user_name';
        $options = Array('where'=>Array($arrData['field']=>$arrData['user_name']));
        $intCount = db()->find('member_auth',$options,'count(id)');
        !$intCount && $intCount = 0;
        return array('查询成功',0,['exists'=>$intCount]);
    }

    /**
     * 取得指定字段的内容，如：性别，手机，邮件，密保问题等
     * @return array
     */
    public function getFieldValue(){
        $arrData = $this->getFieldData('user_id,fields');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        !$arrData['fields'] && $arrData['fields'] = '*';
        $options = array(
            'where' => array('id' => $arrData['user_id']),
            'field' => $arrData['fields'],
        );
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('用户为空。',1);
        }
        foreach ($arrUserInfo as &$v){
            is_null($v) && $v = '';
        }unset($v);
        return Array('success',0,$arrUserInfo);
    }

    /**
     * 取回用户表信息和登录表信息，又或者帐号信息
     * @return array
     */
    public function getAuthInfo(){
        $arrData = $this->getFieldData('user_id,table,field');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        !$arrData['table'] && $arrData['table'] = 'auth';
        !$arrData['field'] && $arrData['field'] = '*';
        $options = array(
            'alias'=>'a',
            'field' => $arrData['field'],
            'join' => array('@.member_login_info as c on c.user_id=a.id'),
            'where' => array('id' => $arrData['user_id']),
        );
        if ($arrData['table'] == 'account'){
            $options['join'][] = '@.member_account as b on b.user_id=a.id';
        }
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('用户记录为空。',1);
        }
        return Array('success',0,$arrUserInfo);
    }

    /**
     * 保存用户信息
     */
    public function saveAuthInfo(){
        $arrData = $this->getFieldData('user_id,fields');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['fields']){
            return Array('更新的字段列表为空。',1);
        }
        $arrFieldData = $this->getFieldData($arrData['fields']);
        $options = array('where' => array('id' => $arrData['user_id']));
        unset($arrData);
        $result = db()->update('member_auth',$arrFieldData,$options);
        if ($result === false){
            return Array('信息保存失败。',1);
        }
        return Array('用户资料保存成功。',0,['count'=>max(0,$result)]);
    }

    /**
     * 验证密保问题是否正确
     * @return array
     */
    public function validQuestion(){
        $arrData = $this->getFieldData('user_id,answer_a,answer_b');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['answer_a'] || !$arrData['answer_b']){
            return Array('问题答案不能为空。',1);
        }
        $options = Array('where'=>Array('id'=>$arrData['user_id']),'field'=>'answer_a,answer_b');
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('用户不存在。',1);
        }
        if ($arrData['answer_a'] === $arrUserInfo['answer_a'] && $arrData['answer_b'] === $arrUserInfo['answer_b']){
            return Array('密保答案正确。',0,['token'=>time()]);
        }
        return Array('密保答案不正确。',1,['token'=>time()]);
    }

    /**
     * 保存密保信息
     * @return array|false|int
     */
    public function saveQuestion(){
        $arrResult = $this->validPayWord();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        $this->setData(['fields'=>'question_a,question_b']);
        $arrResult = $this->getFieldValue();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        $arrUserInfo = isset($arrResult[2])?$arrResult[2]:[];
        if ($arrUserInfo['question_a'] && $arrUserInfo['question_b']){
            return Array('用户已经设置了密保,无法修改.',1);
        }
        return $this->saveResetQuestion();
    }

    /**
     * 重置并直接保存密保
     * @return array|false|int
     */
    public function saveResetQuestion(){
        $arrData = $this->getFieldData('user_id,question_a,answer_a,question_b,answer_b');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['question_a'] || !$arrData['question_b']){
            return Array('密保问题不能为空。',1);
        }
        if (!$arrData['answer_a'] || !$arrData['answer_b']){
            return Array('问题答案不能为空。',1);
        }
        $options = array('where' => array('id' => $arrData['user_id']));
        unset($arrData['user_id']);
        $arrResult = db()->update('member_auth',$arrData,$options);
        if($arrResult === false){
            return array('设置密保失败，请稍后再试！',1);
        }
        return array('设置密保成功！',0);
    }

    /**
     * 旧密码修改登录密码
     * @return array|false|int
     */
    public function editPwd(){
        $arrData = $this->getFieldData('user_id,old_pass_word,new_pass_word,re_pass_word');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['new_pass_word'] || $arrData['new_pass_word'] != $arrData['re_pass_word']){
            return Array('两次输入的密码不相同。',1);
        }
        $options = Array('where' => array('id' => $arrData['user_id']));
        $arrUserInfo = db()->find('member_auth',$options);
        if (md5($this->getConfig('pass_key').$arrData['pass_word']) !== $arrUserInfo['pass_word']){
            return Array('旧的登录密码不正确，请重新输入！',1);
        }
        $arrData = Array(
            'pass_word'=>md5($this->getConfig('pass_key').$arrData['new_pass_word']),
            'pass_time'=>date('Y-m-d H:i:s'),
        );
        $arrResult = db()->update('member_auth',$arrData,$options);
        if($arrResult === false){
            return Array('修改密码失败，请稍后再试！',1);
        }
        return Array('修改密码成功！',0);
    }

    /**
     * 忘记密码时，使用某些字段直接重置密码
     * @return array
     */
    public function saveResetPwdByName(){
        $arrData = $this->getFieldData('user_name,real_name,email,phone,pay_word');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['pay_word']){
            return Array('支付密码不能为空。',1);
        }
        $options = Array(
            'where'=>Array(
                'pay_word'=>md5($arrData['pay_word'].$this->getConfig('pass_key')),
            ),
        );
        $arrData['user_name'] && $options['where']['user_name'] = $arrData['user_name'];
        $arrData['real_name'] && $options['where']['real_name'] = $arrData['real_name'];
        $arrData['email'] && $options['where']['email'] = $arrData['email'];
        $arrData['phone'] && $options['where']['phone'] = $arrData['phone'];
        unset($arrData['pay_word']);
        $arrData = array_filter($arrData);
        if (count($arrData) < 2){
            return Array('除支付密码外，还需要其它二个字段值。',1);
        }
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('会员信息、取款密码校对失败。',1);
        }
        $strNewPassWord = $this->rndWord(6);
        $options = Array('where'=>Array('id'=>$arrUserInfo['id']));
        $arrData = Array(
            'pass_word'=>md5($this->getConfig('pass_key').$strNewPassWord),
            'pass_time'=>date('Y-m-d H:i:s'),
        );
        $arrResult = db()->update('member_auth',$arrData,$options);
        if ($arrResult === false){
            return Array('重置登录密码失败！',1);
        }
        return Array("密码已重置，新密码是{$strNewPassWord}。请尽快登入系统进行变更！",0);
    }

    /**
     * 重置登录密码
     * @return array|false|int
     */
    public function saveResetPwd(){
        $arrData = $this->getFieldData('user_id,new_pass_word,re_pass_word');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['new_pass_word'] || $arrData['new_pass_word'] != $arrData['re_pass_word']){
            return Array('两次输入的密码不相同。',1);
        }
        $options = array('where' => array('id' => $arrData['user_id']));
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('用户信息丢失。',1);
        }
        $arrData = Array(
            'pass_word'=>md5($this->getConfig('pass_key').$arrData['new_pass_word']),
            'pass_time'=>date('Y-m-d H:i:s'),
        );
        $arrResult = db()->update('member_auth',$arrData,$options);
        if ($arrResult === false){
            return Array('重置密码失败！',1);
        }
        return Array('重置密码成功！',0);
    }

    /**
     * 旧密码修改支付密码或登录密码设置支付密码
     * @return array|false|int
     */
    public function editPayPwd(){
        $arrData = $this->getFieldData('user_id,old_pay_word,new_pay_word,re_pay_word');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['new_pay_word'] || $arrData['new_pay_word'] != $arrData['re_pay_word']){
            return Array('两次输入的密码不相同。',1);
        }
        $options = array('where' => array('id' => $arrData['user_id']));
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('用户信息丢失。',1);
        }
        if (!$arrUserInfo['pay_word']){//如果支付密码为空，就比登录密码。
            if (md5($this->getConfig('pass_key').$arrData['old_pay_word']) != $arrUserInfo['pass_word']){
                return Array('登录密码不正确，请重新输。',1);
            }
        }else{
            if (md5($arrData['old_pay_word'].$this->getConfig('pass_key')) != $arrUserInfo['pay_word']){
                return Array('旧支付密码不正确，请重新输入。',1);
            }
        }
        $arrData = Array(
            'pay_word'=>md5($arrData['new_pay_word'].$this->getConfig('pass_key')),
            'pay_time'=>date('Y-m-d H:i:s'),
        );
        $arrResult = db()->update('member_auth',$arrData,$options);
        $strContent = !$arrUserInfo['pay_word']?'设置支付密码':'修改支付密码';
        if ($arrResult === false){
            return Array($strContent.'失败！',0);
        }
        return Array($strContent.'成功！',1);
    }

    /**
     * 验证支付密码
     * @return array
     */
    public function validPayWord(){
        $arrData = $this->getFieldData('user_id,pay_word');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['pay_word']){
            return Array('支付密码不能为空。',1);
        }
        $options = Array('where' => array('id' => $arrData['user_id']));
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return array('没有此用户信息',1);
        }
        if(!$arrUserInfo['pay_word']){
            return Array('您未设置支付密码，请先设置。',1);
        }
        if ($arrUserInfo['pay_word'] != md5($arrData['pay_word'].$this->getConfig('pass_key'))){
            return Array('您输入的支付密码不正确。',1);
        }
        return Array('支付密码正确。',0);
    }

    /**
     * 重置支付密码
     * @return array|false|int
     */
    public function saveResetPayPwd(){
        $arrData = $this->getFieldData('user_id,new_pay_word,re_pay_word');
        if (!$arrData['user_id']){
            return Array('用户ID为空。',1);
        }
        if (!$arrData['new_pay_word'] || $arrData['new_pay_word'] != $arrData['re_pay_word']){
            return Array('两次输入的密码不相同。',1);
        }
        $options = array('where' => array('id' => $arrData['user_id']));
        $arrUserInfo = db()->find('member_auth',$options);
        if (!$arrUserInfo){
            return Array('用户信息丢失。',1);
        }
        $arrData = Array(
            'pay_word'=>md5($arrData['pay_word'].$this->getConfig('pass_key')),
            'pay_time'=>date('Y-m-d H:i:s'),
        );
        $arrResult = db()->update('member_auth',$arrData,$options);
        if ($arrResult === false){
            return Array('重置支付密码失败！',1);
        }
        return Array('重置支付密码成功！',0);
    }

    /**
     * 记录用户登录信息
     * @param array $arrInfo
     * @param int $status 1为成功
     */
    private function loginInfo($arrInfo = [],$status = 0){
        //写入用户登录日志
        $arrData = array(
            'user_id' => $arrInfo['id'],
            'login_time' => date('Y-m-d H:i:s'),
            'login_ip' => request()->ip(),
            'user_agent' => request()->server('HTTP_USER_AGENT'),
            'has_mobile' => request()->isMobile()?1:2,
            'status' => $status,
        );
        $intLogId = db()->insert('member_login_log',$arrData);
        if ($intLogId < 1){
            $this->sysError(array('info' => '写入用户登录日志失败！'));
        }
        //写入用户登录信息表j_member_login_info
        $options = array('where' => array('user_id' => $arrInfo['id']));
        $arrLoginInfo = db()->find('member_login_info',$options);
        if ($arrLoginInfo){
            $arrData = array(
                'login_num' => array('exp','login_num+1'),
                'login_time' => date('Y-m-d H:i:s'),
                'per_login_time' => $arrLoginInfo['login_time'],
                'login_ip' => request()->ip(),
                'per_login_ip' => $arrLoginInfo['login_ip'],
            );
            $result = db()->update('member_login_info',$arrData,$options);
            if ($result === false){
                $this->sysError(array('info' => '更新用户登录信息失败！'));
            }
        }else{
            $arrData = array(
                'user_id' => $arrInfo['id'],
                'login_num' => 1,
                'login_time' => date('Y-m-d H:i:s',time()),
                'per_login_time' => date('Y-m-d H:i:s',time()),
                'login_ip' => request()->ip(),
                'per_login_ip' => request()->ip(),
            );
            $result = db()->insert('member_login_info',$arrData);
            if ($result < 1){
                $this->sysError(array('info' => '写入用户登录信息失败！'));
            }
        }
    }

    /**
     * 随机密码串
     * @param int $intNum
     * @return string
     */
    private function rndWord($intNum = 6){
        $arrV = ['Q','W','E','R','T','Y','U','I','O','P','A','S','D','F','G','H','J','K','L','Z','X','C','V','B','N','M',1,2,3,4,5,6,7,8,9];
        shuffle($arrV);
        $arrK = array_rand($arrV,$intNum);
        foreach ($arrK as &$v){
            $v = $arrV[$v];
        }unset($v);
        return implode('',$arrK);
    }

    /**
     * 保存实名
     * @return array
     */
    public function saveRealName(){
        $arrData = $this->getFieldData('user_id,real_name,id_card,area_id,area_name,img_info');
        $arrTempImgInfo = json_decode($arrData['img_info'],true);
        if ($arrData['user_id'] < 1){
            return Array('登录超时，请返回刷新。',1);
        }
        if (!is_array($arrTempImgInfo)){
            return Array('图片格式不正确。',1);
        }
        if (!isset($arrTempImgInfo['f']) || !$arrTempImgInfo['f']){
            return Array('请上传身份证前面照片。',1);
        }
        if (!isset($arrTempImgInfo['b']) || !$arrTempImgInfo['b']){
            return Array('请上传身份证背面照片。',1);
        }
        $arrData['status'] = 1;
        $options = Array(
            'where'=>Array(
                'user_id'=>$arrData['user_id'],
                'id_card'=>$arrData['id_card'],
                'status'=>Array('in','1,2,3'),
            )
        );
        $arrVerify = db()->find('member_id_card_verify',$options);
        if ($arrVerify){
            return Array('身份证号码已经被验证。',1);
        }
        db()->startTrans('Auth.saveRealName');
        $arrAuthData = Array(
            'real_name'=>$arrData['real_name'],
            'id_card'=>$arrData['id_card'],
            'real_status'=>2,
        );
        $options = Array('where'=>Array('id'=>$arrData['user_id']));
        $result = db()->update('member_auth',$arrAuthData,$options);
        if ($result === false){
            db()->rollback();
            return Array('更新用户信息失败。',1);
        }
        $result = db()->insert('member_id_card_verify',$arrData);
        if ($result === false){
            db()->rollback();
            return Array('插入身份证信息失败。',1);
        }
        db()->commit('Auth.saveRealName');
        return Array('提交成功，请等待审核。',0);
    }

    /**
     * 保存绑定手机
     * @return array
     */
    public function saveMobile(){
        $arrData = $this->getFieldData('user_id,mobile,rad_code,tpl_key');
        if ($arrData['user_id'] < 1){
            return Array('登录超时，请返回刷新。',1);
        }
        !$arrData['tpl_key'] && $arrData['tpl_key'] = 'bind_mobile';
        $arrVerifyData = Array(
            'user_id'=>$arrData['user_id'],
            'send_to'=>$arrData['mobile'],
            'tpl_key'=>$arrData['tpl_key'],
            'send_type'=>2,
        );
        $arrResult = model('System_Verify',$arrVerifyData)->verify();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        $options = Array('where'=>Array('mobile'=>$arrData['mobile']));
        $arrAuthInfo = db()->find('member_auth',$options);
        if ($arrAuthInfo){
            return Array("手机号码：{$arrData['mobile']}，已经被他人绑定。",1);
        }
        db()->startTrans('Auth.saveMobile');
        $arrResult = model('System_Verify')->invalid();
        if ($arrResult !== 0){
            db()->rollback();
            return $arrResult;
        }
        $options = Array('where'=>Array('id'=>$arrData['user_id']));
        $arrTempData = Array(
            'mobile_status'=>2,
            'mobile'=>$arrData['mobile'],
        );
        $result = db()->update('member_auth',$arrTempData,$options);
        if ($result === false){
            db()->rollback();
            return Array('绑定手机失败。',1);
        }
        db()->commit('Auth.saveMobile');
        session(md5($arrData['tpl_key'].'_'.$arrData['mobile']),null);
        return Array('绑定成功。',0);
    }

    /**
     * 保存重新绑定手机
     * @return array
     */
    public function reSaveMobile(){
        $arrData = $this->getFieldData('user_id,old_mobile,new_mobile,old_rad_code,new_rad_code');
        if ($arrData['user_id'] < 1){
            return Array('登录超时，请返回刷新。',1);
        }
        $arrUnBindInfo = Array(
            'send_to' => $arrData['old_mobile'],
            'tpl_key' => 'unbind_mobile',
            'send_type' => 2,
            'user_id' => $arrData['user_id'],
            'verify_tip' => '旧手机',
            'rad_code' => $arrData['old_rad_code'],
        );
        $arrResult = model('System_Verify')->setData($arrUnBindInfo)->verify();
        if ($arrResult !== 0 || !isset($arrResult[2]['verify_id']) || $arrResult[2]['verify_id'] < 0){
            return $arrResult;
        }
        $arrUnBindInfo['verify_id'] = $arrResult[2]['verify_id'];
        $arrBindInfo = Array(
            'send_to' => $arrData['new_mobile'],
            'tpl_key' => 'bind_mobile',
            'send_type' => 2,
            'user_id' => $arrData['user_id'],
            'verify_tip' => '新手机',
            'rad_code' => $arrData['new_rad_code'],
        );
        $arrResult = model('System_Verify')->setData($arrBindInfo)->verify();
        if ($arrResult !== 0 || !isset($arrResult[2]['verify_id']) || $arrResult[2]['verify_id'] < 0){
            return $arrResult;
        }
        $arrBindInfo['verify_id'] = $arrResult[2]['verify_id'];
        //判断新号码是否能用
        $options = Array('where'=>Array('mobile'=>$arrData['new_mobile']));
        $arrAuthInfo = db()->find('member_auth',$options);
        if ($arrAuthInfo){
            return Array("手机号码：{$arrData['new_mobile']}，已经被他人绑定。");
        }
        //开始更新
        db()->startTrans('Auth.reSaveMobile');
        $arrResult = model('System_Verify')->setData($arrUnBindInfo)->invalid();
        if ($arrResult[1] !== 0){
            db()->rollback();
            return $arrResult;
        }
        $arrResult = model('System_Verify')->setData($arrBindInfo)->invalid();
        if ($arrResult[1] !== 0){
            db()->rollback();
            return $arrResult;
        }
        $options = Array('where'=>Array('id'=>$arrData['user_id'],'mobile'=>$arrData['new_mobile']));
        $arrTempData = Array(
            'mobile_status'=>2,
            'mobile'=>$arrData['new_mobile'],
        );
        $result = db()->update('member_auth',$arrTempData,$options);
        if ($result === false){
            db()->rollback();
            return Array('绑定手机失败。',1);
        }
        db()->commit('Auth.reSaveMobile');
        session(md5('bind_mobile_'.$arrData['new_mobile']),null);
        session(md5('unbind_mobile_'.$arrData['old_mobile']),null);
        return Array('绑定成功。',0);
    }

    /**
     * 保存绑定邮箱
     * @return array
     */
    public function saveEmail(){
        $arrData = $this->getFieldData('user_id,email,rad_code,tpl_key');
        if ($arrData['user_id'] < 1){
            return Array('登录超时，请返回刷新。',1);
        }
        !$arrData['tpl_key'] && $arrData['tpl_key'] = 'bind_email';
        $arrVerifyData = Array(
            'user_id'=>$arrData['user_id'],
            'send_to'=>$arrData['email'],
            'tpl_key'=>$arrData['tpl_key'],
            'send_type'=>1,
        );
        $arrResult = model('System_Verify',$arrVerifyData)->verify();
        if ($arrResult[1] !== 0){
            return $arrResult;
        }
        $options = Array('where'=>Array('email'=>$arrData['email']));
        $arrAuthInfo = db()->find('member_auth',$options);
        if ($arrAuthInfo){
            return Array("邮箱：{$arrData['email']}，已经被他人绑定。",1);
        }
        db()->startTrans('Auth.saveEmail');
        $arrResult = model('System_Verify')->invalid();
        if ($arrResult !== 0){
            db()->rollback();
            return $arrResult;
        }
        $options = Array('where'=>Array('id'=>$arrData['user_id']));
        $arrTempData = Array(
            'email_status'=>2,
            'email'=>$arrData['email'],
        );
        $result = db()->update('member_auth',$arrTempData,$options);
        if ($result === false){
            db()->rollback();
            return Array('绑定邮箱失败。',1);
        }
        db()->commit('Auth.saveEmail');
        session(md5($arrData['tpl_key'].'_'.$arrData['email']),null);
        return Array('绑定成功。',0);
    }

    /**
     * 保存重新绑定邮箱
     * @return array
     */
    public function reSaveEmail(){
        $arrData = $this->getFieldData('user_id,old_email,new_email,old_rad_code,new_rad_code');
        if ($arrData['user_id'] < 1){
            return Array('登录超时，请返回刷新。',1);
        }
        $arrUnBindInfo = Array(
            'send_to' => $arrData['old_email'],
            'tpl_key' => 'unbind_email',
            'send_type' => 1,
            'user_id' => $arrData['user_id'],
            'verify_tip' => '旧邮箱',
            'rad_code' => $arrData['old_rad_code'],
        );
        $arrResult = model('System_Verify')->setData($arrUnBindInfo)->verify();
        if ($arrResult !== 0 || !isset($arrResult[2]['verify_id']) || $arrResult[2]['verify_id'] < 0){
            return $arrResult;
        }
        $arrUnBindInfo['verify_id'] = $arrResult[2]['verify_id'];
        $arrBindInfo = Array(
            'send_to' => $arrData['new_email'],
            'tpl_key' => 'bind_email',
            'send_type' => 1,
            'user_id' => $arrData['user_id'],
            'verify_tip' => '新邮箱',
            'rad_code' => $arrData['new_rad_code'],
        );
        $arrResult = model('System_Verify')->setData($arrBindInfo)->verify();
        if ($arrResult !== 0 || !isset($arrResult[2]['verify_id']) || $arrResult[2]['verify_id'] < 0){
            return $arrResult;
        }
        $arrBindInfo['verify_id'] = $arrResult[2]['verify_id'];
        //判断新号码是否能用
        $options = Array('where'=>Array('email'=>$arrData['new_email']));
        $arrAuthInfo = db()->find('member_auth',$options);
        if ($arrAuthInfo){
            return Array("邮箱：{$arrData['new_email']}，已经被他人绑定。");
        }
        //开始更新
        db()->startTrans('Auth.reSaveEmail');
        $arrResult = model('System_Verify')->setData($arrUnBindInfo)->invalid();
        if ($arrResult[1] !== 0){
            db()->rollback();
            return $arrResult;
        }
        $arrResult = model('System_Verify')->setData($arrBindInfo)->invalid();
        if ($arrResult[1] !== 0){
            db()->rollback();
            return $arrResult;
        }
        $options = Array('where'=>Array('id'=>$arrData['user_id'],'email'=>$arrData['new_email']));
        $arrTempData = Array(
            'email_status'=>2,
            'email'=>$arrData['new_email'],
        );
        $result = db()->update('member_auth',$arrTempData,$options);
        if ($result === false){
            db()->rollback();
            return Array('绑定手机失败。',1);
        }
        db()->commit('Auth.reSaveEmail');
        session(md5('bind_email_'.$arrData['new_email']),null);
        session(md5('unbind_email_'.$arrData['old_email']),null);
        return Array('绑定成功。',0);
    }

}