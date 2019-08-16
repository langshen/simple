<?php
namespace Model\User;
use Spartan\Lib\Model;
use Model\Member\UserAccount;

defined('APP_NAME') OR exit('404 Not Found');

class Sign extends Model
{
    public $strTable = 'user_sign';
    //******************************要连续打卡***************************************//

    /**
     * 当前签到的日期
     * @param $intUserId
     * @return mixed
     */
    public function signDay($intUserId){
        $intTotalDay = date('t',time());//当月的总天数
        $beginDate = date('Y-m-1',time());
        $endDate = date("Y-m-{$intTotalDay} 23:59:59",time());
        $options = Array(
            'where'=>Array(
                'activity_id'=>99,//这是固定的签到活动
                'user_id'=>$intUserId,
                'add_time'=>Array('Between',[$beginDate,$endDate]),
            ),
            'order'=>'add_time desc',
        );
        //已经打卡的次数
        $arrSign = db()->select($this->strTable,$options);
        if (!$arrSign){
            return Array(0,[]);
        }
        $arrSignData = array_column($arrSign,'add_time');
        foreach($arrSignData as &$value){
            $value = explode(' ',$value)[0];
        }unset($value);
        $intSignHeightData = 0;
        $arrSignData = array_unique($arrSignData);
        if ($arrSignData){
            $intNowDay = intval(date('d'));
            foreach($arrSignData as $value){
                if (date('Y-m-d') == $value){
                    continue;
                }
                if ($intNowDay - 1 == intval(date('d',strtotime($value)))){
                    $intSignHeightData++;
                    $intNowDay = intval(date('d',strtotime($value)));
                }else{
                    break;
                }
            }
        }
        return Array($intSignHeightData,$arrSignData);
    }

    /**
     * 今天的签到次数
     * @param $intUserId
     * @return mixed
     */
    public function todaySign($intUserId){
        $beginDate = date('Y-m-d 00:00:00',time());
        $endDate = date("Y-m-d 23:59:59",time());
        $options = Array(
            'where'=>Array(
                'activity_id'=>99,//这是固定的签到活动
                'user_id'=>$intUserId,
                'add_time'=>Array('Between',[$beginDate,$endDate]),
            )
        );
        $intTodayCount = db()->find($this->strTable,$options,'count(id)');
        return max(0,$intTodayCount);
    }

    /**
     * 签到的动作
     * @param $intUserId
     * @return array
     */
    public function saveSign($intUserId){
        $intTodaySign = $this->todaySign($intUserId);
        if ($intTodaySign > 0){//当月的总天数
            return Array('您今天已经签到啦。',0);
        };
        $arrSignDay = $this->signDay($intUserId);
        $intSignHeightData = $arrSignDay[0];
        $intSignHeightData++;
        $floScore = $intSignHeightData * 5;//连接一次签到+5分。
        db()->startTrans('Sign.saveSign');
        //签到记录
        $strActContent = '连续签到'.$intSignHeightData.'次，奖励'.$floScore.'积分。';
        $arrData = Array(
            'activity_id'=>99,
            'user_id'=>$intUserId,
            'reward_status'=>1,
            'content'=>$strActContent,
            'add_time'=>date('Y-m-d H:i:s'),
            'ip'=>request()->ip(),
        );
        $result = db()->insert($this->strTable,$arrData);
        if ($result === false){
            db()->rollback();
            return Array('签到记录保存失败。',0);
        }
        $intSignId = max(0,$result);
        //更新金币
        /** @var UserAccount $clsAccount */
        $clsAccount = UserAccount::instance();
        $arrResult = $clsAccount->setUser($intUserId,$intSignId)
            ->setScore($floScore,'income','reward',$strActContent)
            ->update();
        if ($arrResult[1] != 1){
            db()->rollback();
            return Array('账号异常，签到失败。',0);
        }
        db()->commit('Sign.saveSign');
        $arrData = ['score'=>$floScore,'sign'=>$intSignHeightData];
        return Array('签到成功，已连续签到'.$intSignHeightData.'次，奖励'.$floScore.'积分。',1,$arrData);
    }

}