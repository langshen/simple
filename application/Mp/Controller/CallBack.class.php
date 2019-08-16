<?php
namespace Mp\Controller;
use Spartan\Lib\Controller;
use Spartan\Extend\VenderWeChat;
/**
 * @description 外接口请求跳转类，
 */
class CallBack extends Controller{

    /**
     * 微信公众号的回调
     */
    public function WxMpCallBack(){
        $weObj = (new VenderWeChat())->getWeChat(config('MP_CONFIG'));
        $weObj->valid();
        $strType = $weObj->getRev()->getRevType();
        $arrRevData = $weObj->getRevData();
        switch($strType) {
            case 'text':
                $strContent = 'hello, I\'m WxMpCallBack';
                if ($arrRevData['Content'] == '代理人'){
                    $strContent = '欢迎代理人';
                }
                $weObj->text($strContent)->reply();
                break;
            case 'event':
                $arrEventForm = $weObj->getRev()->getRevEvent();
                !is_array($arrEventForm) && $arrEventForm = ['event'=>'','key'=>''];
                if ($arrEventForm['event']=='subscribe'){
                    $arrInfo = $weObj->getUserInfo($arrRevData['FromUserName']);
                    $arrInfo['member_type'] = 'xcx';
                    model('Member/Auth',$arrInfo)->addUser();
                }elseif ($arrEventForm['event']=='unsubscribe'){

                }elseif ($arrEventForm['event']=='CLICK'){
                    $strContent = '';
                    switch ($arrEventForm['key']){
                        case 'bind_tip'://要求绑定
                            $strContent = "尊敬的商户，请登录您的帐户并绑定，即可开通到款提示功能了。".
                                "<a href='/wx/bind/{$arrRevData['FromUserName']}.html'>前去绑定>>></a>";

                            break;

                        case '':

                            break;
                        default:
                            $strContent = "亲呀，我无法看透您的心呀，请联系客服。";
                    }
                    $weObj->text($strContent)->reply();
                }elseif ($arrEventForm['event']=='VIEW'){

                }else{
                    $weObj->text("hello, 请联系客服")->reply();
                }
                break;
            case 'image':

                break;
            default:
                $weObj->text("help info")->reply();
        }
    }

    //微信公从号菜单操作
    public function WxMenu(){
        $weObj = (new VenderWeChat())->getWeChat(config('MP_CONFIG'));
        $newmenu = array(
            "button"=>array(
                array(
                    'name'=>'互动有礼',
                    'key'=>'MENU_KEY_1',
                    'sub_button'=>Array(
                        array('type'=>'view','name'=>'现金红包','url'=>'/gift/cash.html'),
                        array('type'=>'view','name'=>'签到积分','url'=>'/gift/sign.html'),
                    )
                ),
                array(
                    'name'=>'平安是福',
                    'key'=>'MENU_KEY_2',
                    'sub_button'=>Array(
                        array('type'=>'view','name'=>'平安金管家','url'=>'/pa/butler.html'),
                    )
                ),
                array(
                    'name'=>'会员功能',//
                    'key'=>'MENU_KEY_3',
                    'sub_button'=>Array(
                        array('type'=>'view','name'=>'个人资料','url'=>'/member/info.html'),
                    )
                ),
            )
        );
        $result = $weObj->createMenu($newmenu);
        var_dump($result);
        var_dump($weObj->errMsg);
    }

}
