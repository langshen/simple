<?php
namespace Model\User;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

class raffle extends Model
{

    //登录抽奖信息
    public function raffleInfo(){
        $intUserId = max(0,$this->userInfo['id']);
        if ($intUserId < 1){
            return Array('登录超时，请重新登陆。',0);
        }
        $arrInfo = $this->getRaffle($intUserId);
        return Array('成功',1,$arrInfo);
    }
    //得到一个抽奖信息
    private function getRaffle($intUserId){
        $beginDate = strtotime(date('Y-m-d 00:00:00',time()));
        $endDate = strtotime(date("Y-m-d 23:59:59",time()));
        $options = Array(
            'where'=>Array(
                'activity_id'=>99,//这是固定的签到活动
                'user_id'=>$intUserId,
                'add_time'=>Array('Between',[$beginDate,$endDate]),
            ),
            'field'=>'id,value,content',
        );
        $arrInfo = $this->Db()->find('user_activity_raffle',$options);
        !$arrInfo && $arrInfo = ['id'=>0,'value'=>0,'content'=>''];
        $arrInfo['content'] = str_replace('、',"\n",$arrInfo['content']);
        return $arrInfo;
    }
    //登录抽奖动作
    public function saveRaffle(){
        $intUserId = max(0,$this->userInfo['id']);
        if ($intUserId < 1){
            return Array('登录超时，请重新登陆。',0);
        }
        $arrInfo = $this->getRaffle($intUserId);
        if ($arrInfo['id'] > 0){
            return Array('您今天已经抽过奖了。',0);
        }
        $intColor = max(0,$this->getData('color',0));
        if ($intColor < 1 || $intColor > 6){
            return Array('错误的抽奖牌子。',0);
        }
        $intExp = $intGold = $intDiam = $intBuKa = $intGiftBox = 0;
        $intGiftRnd = mt_rand(1,100);//礼品的机率范围
        $arrGiftMsg = Array('获得');
        switch (true){
            case $intGiftRnd <= 10://金币
                $intGold = mt_rand(2,5) * 500;
                $arrGiftMsg[] = $intGold.'欢乐豆';
                if (mt_rand(1,10) < 5){
                    $intBuKa = 1;
                    $arrGiftMsg[] = '1张补签卡';
                }else{
                    $intGiftBox = 1;
                    $arrGiftMsg[] = '1个铜宝箱';
                }
                break;
            case $intGiftRnd > 10 && $intGiftRnd <= 30://经验
                $intGold = mt_rand(1,4) * 500;
                $arrGiftMsg[] = $intGold.'欢乐豆';
                if (mt_rand(1,10) < 5){
                    $intExp = mt_rand(2,5) * 10;
                    $arrGiftMsg[] = $intExp.'经验值';
                }else{
                    $intGiftBox = 1;
                    $arrGiftMsg[] = '1个铜宝箱';
                }
                break;
            case $intGiftRnd > 30 && $intGiftRnd <= 60:
                $intGold = mt_rand(2,4) * 500;
                $arrGiftMsg[] = $intGold.'欢乐豆';
                $intExp = mt_rand(1,3) * 10;
                $arrGiftMsg[] = $intExp.'经验值';
                break;
            case $intGiftRnd >60 && $intGiftRnd <= 90:
                $intGold = mt_rand(1,4) * 500;
                $arrGiftMsg[] = $intGold.'欢乐豆';
                if (mt_rand(1,10) < 4){
                    $intExp = mt_rand(1,2) * 10;
                    $arrGiftMsg[] = $intExp.'经验值';
                }else{
                    $intGiftBox = 1;
                    $arrGiftMsg[] = '1个铜宝箱';
                }
                break;
            case $intGiftRnd > 90:
                $intRnd = mt_rand(1,10);
                if ($intRnd < 4){
                    $intDiam = 1;
                    $arrGiftMsg[] = $intDiam.'个钻石';
                    $intExp = mt_rand(1,2) * 10;
                    $arrGiftMsg[] = $intExp.'经验值';
                }elseif ($intRnd > 8){
                    $intGold = mt_rand(4,6) * 500;
                    $arrGiftMsg[] = $intGold.'欢乐豆';
                    $intGiftBox = 1;
                    $arrGiftMsg[] = '1个铜宝箱';
                }else{
                    $intGold = mt_rand(2,4) * 500;
                    $arrGiftMsg[] = $intGold.'欢乐豆';
                    if (mt_rand(1,10) < 5){
                        $intBuKa = 1;
                        $arrGiftMsg[] = '1张补签卡';
                    }else{
                        $intGiftBox = 1;
                        $arrGiftMsg[] = '1个铜宝箱';
                    }
                }
                break;
            default:
                $intGold = 500;
                $arrGiftMsg[] = $intGold.'欢乐豆';
        }
        $this->Db()->startTrans('Activity.saveRaffle');
        //一个铜宝箱
        if ($intGiftBox > 0){
            $arrResult = $this->addGoldBox($intUserId,1);
            if ($arrResult[1] != 1){
                $this->Db()->rollback();
                return $arrResult;
            }
        }
        //一个补签卡
        if ($intBuKa > 0){
            $arrResult = $this->addBuKa($intUserId);
            if ($arrResult[1] != 1){
                $this->Db()->rollback();
                return $arrResult;
            }
        }
        //抽奖记录
        $strContent = implode('、',$arrGiftMsg);
        $arrData = Array(
            'activity_id'=>99,
            'user_id'=>$intUserId,
            'reward_status'=>$intGiftRnd,
            'value'=>$intColor,
            'content'=>$strContent,
            'add_time'=>time(),
            'ip'=>$this->getData('_ip_',''),
        );
        $result = $this->Db()->insert('user_activity_raffle',$arrData);
        if ($result === false){
            $this->Db()->rollback();
            return Array('抽奖记录保存失败。',0);
        }
        $intRaffleId = max(0,$result);
        //帐户变化日志
        /** @var UserAccount $clsAccount */
        $clsAccount = UserAccount::instance();
        $clsAccount->setUser($intUserId,$intRaffleId);
        if ($intDiam > 0){
            $clsAccount->setDiam($intDiam,'income','reward','每日登录抽奖获得钻石');
        }
        if ($intGold > 0){
            $clsAccount->setGold($intGold,'income','reward','每日登录抽奖获得金币');
        }
        if ($intExp > 0){
            $clsAccount->setExp($intExp,'add','每日登录抽奖获得经验');
        }
        $arrResult = $clsAccount->update();
        if ($arrResult[1] != 1){
            $this->Db()->rollback();
            return Array('账号异常，签到失败。',0);
        }
        $this->Db()->commit('Activity.saveRaffle');
        return Array('成功',1,['gold'=>$intGold,'exp'=>$intExp,'value'=>$intColor,'diam'=>$intDiam,'info'=>str_replace('、',"\n",$strContent)]);
    }
}