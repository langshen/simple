<?php
namespace Model\Member;
use Spartan\Lib\Model;

defined('APP_NAME') OR exit('404 Not Found');

/**
 * 用户的推荐关系
 * Class Auth
 * @package Logic\Member
 */
class Relations extends Model
{
    /**
     * 填写推荐人用户名得到的推荐关系
     * @return array
     */
    public function addRecommend(){
        $arrData = $this->getFieldData(
            ['user_id'=>0,'user_name'=>'','recommend_user_name'=>'','recommend_email'=>'','recommend_mobile'=>'','recommend_name'=>'']
        );
        if($arrData['user_id'] < 1){//被推荐人用户ID
            return Array('用户ID错误!',1);
        }
        $options = Array('where'=>Array());
        if ($arrData['recommend_mobile']){
            $options['where']['mobile'] = $arrData['recommend_mobile'];
        }elseif ($arrData['recommend_email']){
            $options['where']['email'] = $arrData['recommend_email'];
        }elseif ($arrData['recommend_user_name']){
            $options['where']['user_name'] = $arrData['recommend_user_name'];
        }elseif ($arrData['recommend_name']){
            if(mb_strlen($arrData['recommend_name'],'utf-8') == 16){
                $options['where']['recommend_code'] = $arrData['recommend_name'];
            }elseif (isMobile($arrData['recommend_name'])){
                $options['where']['mobile'] = $arrData['recommend_name'];
            }elseif (isEmail($arrData['recommend_name'])){
                $options['where']['email'] = $arrData['recommend_name'];
            }else{
                $options['where']['user_name'] = $arrData['recommend_name'];
            }
        }else{
            return Array('没有推荐码。',1);
        }
        $arrReferrer = db()->find('member_auth',$options);
        if (!$arrReferrer){
            return Array('推荐人不存在',1);
        }
        $arrUserName = Array($arrData['user_name'],$arrReferrer['user_name']);
        $arrResult = $this->add($arrData['user_id'],$arrReferrer['id'],$arrUserName,"填写推荐人进行推荐。");
        if($arrResult[1] !== 0 ){
            $arrData = array(
                "level"=>0,
                "class"=>"Relations.addRecommendByUserNameAndEmail",
                "info"=>"添加推荐用户关系失败",
                "err"=>"被推荐人：{$arrData['user_id']},推荐人：{$arrReferrer['id']}",
                "add_time"=>date('Y-m-d H:i:s'),
            );
            $this->sysError($arrData);
            return $arrResult;
        }
        return Array('添加成功。',0,$arrResult);
    }

    /**
     * 添加一个推荐关系
     * @date 2014-03-31
     * @param int $intUserId 受推荐用户ID
     * @param int $intReferrerId 推荐人用户ID
     * @param array $arrUserName 被推荐人和推荐人
     * @param string $strContent 备注
     * @param int $intStatus 状态
     * @return array
     */
    public function add($intUserId, $intReferrerId,$arrUserName, $strContent='',$intStatus = 1){
        if ($intUserId == $intReferrerId) {
            return Array('无法将用户自己设定为推荐人',1);
        }
        $options = Array('where'=>Array('user_id'=>$intUserId,));
        $intCount = db()->find('member_relations',$options,"count('user_id')");
        if ($intCount > 0) { // 确定是否已存在关系
            return Array('已存在推荐关系',1);
        }
        is_null($strContent) && $strContent = '';
        $arrData = array(
            'user_id'     => $intUserId,
            'user_name'   => '["'.$arrUserName[0].'","'.$arrUserName[1].'"]',//json_encode($arrUserName),
            'referrer_id_1' => $intReferrerId,
            'status'      => $intStatus,//默认
            'remark'      => $strContent,
            'type'        => 1,//默认
            'add_time'    => date('Y-m-d H:i:s'),
        );
        //推荐人，带来他的关系
        $options = Array('where'=>Array('user_id'=>$intReferrerId,));
        $arrParentInfo = db()->find('member_relations',$options);
        if ($arrParentInfo){
            for($i = 2;$i <= 10;$i++){
                $arrData["referrer_id_{$i}"] = $arrParentInfo['referrer_id_'.($i-1)];
            }
        }
        $result = db()->insert('member_relations',$arrData);
        if ($result === false) {
            return Array('写入数据时发生错误',1);
        }else{
            return Array('添加成功',0,$arrData);
        }
    }
    /**
     * 更新一个推荐关系
     * @date 2014-03-31
     * @param int $intUserId 受推荐用户ID
     * @param int $intReferrerId 推荐人用户ID
     * @param array $arrUserName 被推荐人和推荐人
     * @param string $strContent 备注
     * @return array
     */
    public function update($intUserId, $intReferrerId,$arrUserName, $strContent=''){
        if ($intUserId == $intReferrerId) {
            return Array('无法将用户自己设定为推荐人',1);
        }
        $options = Array('where'=>Array('user_id'=>$intUserId, 'referrer_id_1' => $intReferrerId,));
        $count = db()->find('member_relations',$options,"count('user_id')");
        if ($count > 0) { // 确定是否已存在关系
            return Array('已存在推荐关系，无需修改',1);
        }
        $arrData = array(
            'user_name'   => json_encode($arrUserName,JSON_UNESCAPED_UNICODE),
            'status'      => 1,//默认
            'remark'      => $strContent,
            'type'        => 1,//默认
            'add_time'    => date('Y-m-d H:i:s',time()),
            'user_id'     => $intUserId,
            'referrer_id_1' => $intReferrerId,
        );
        //推荐人，带来他的关系
        $options = Array('where'=>Array('user_id'=>$intReferrerId,));
        $arrParentInfo = db()->find('member_relations',$options);
        if ($arrParentInfo){
            for($i = 2;$i <= 10;$i++){
                $arrData["referrer_id_{$i}"] = isset($arrParentInfo['referrer_id_'.($i-1)])?$arrParentInfo['referrer_id_'.($i-1)]:0;
            }
        }
        //$arrData 等于 当前的整个完整关系
        db()->startTrans('Recommend.update');
        $options = Array(
            'where'=>Array(
                'referrer_id_1|referrer_id_2|referrer_id_3|referrer_id_4|referrer_id_5|referrer_id_6|referrer_id_7|referrer_id_8|referrer_id_9|referrer_id_10'=>$intUserId,
            )
        );
        $arrParentInfo = db()->select('member_relations',$options);
        //找出被推荐人在其它层里的关系。
        $intMasterId = max(0,intval(config('OEM_CONFIG.USER_ID')));
        if ($intMasterId < 1){
            return Array('最大顶层老大(OEM_CONFIG.USER_ID)不存在。'.$intMasterId,1);
        }
        foreach ($arrParentInfo as $v){
            for($i=1;$i<=10;$i++){
                if ($v['referrer_id_'.$i] == $intUserId){
                    for($ii=$i;$ii<=10;$ii++){
                        $v['referrer_id_'.$ii] = isset($arrData['referrer_id_'.($ii-$i)])?$arrData['referrer_id_'.($ii-$i)]:0;
                    }
                    $v['referrer_id_'.$i] = $intUserId;
                    break;
                }
            }
            $intNum = 999;//默认999位置后面的为0
            for($i=1;$i<=10;$i++){
                if ($i > $intNum){
                    $v['referrer_id_'.$i] = 0;
                    continue;
                }
                if ($v['referrer_id_'.$i] == $intMasterId){
                    $intNum = $i;
                }
            }
            //重写了所有的层级，但user_name不变
            $options = Array('where'=>Array('user_id'=>$v['user_id'],));
            $result = db()->update('member_relations',$v,$options);
            if ($result === false){
                db()->rollback();
                return Array('推荐关系修改失败:'.$v['user_id']);
            }
        }
        $intNum = 999;//默认999位置后面的为0
        for($i=1;$i<=10;$i++){
            if ($i > $intNum){
                $arrData['referrer_id_'.$i] = 0;
                continue;
            }
            if ($arrData['referrer_id_'.$i] == $intMasterId){
                $intNum = $i;
            }
        }
        $options = Array('where'=>Array('user_id'=>$intUserId,),);
        $result = db()->update('member_relations',$arrData,$options);
        if ($result === false){
            db()->rollback();
            return Array('推荐关系修改失败:'.$intUserId);
        }
        db()->commit('Recommend.update');
        return Array('更新成功',0);
    }

    /**
     * 删除一个推荐关系
     * @return array
     */
    public function delete(){
        return Array('推荐关系不能删除~！',0);
    }

}