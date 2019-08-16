<?php
namespace Admin\Controller;
use Admin\Common\Control;
use Spartan\Extend\DbTable;

class Set extends Control {

    /**
     * Table Model 生成器
     * @return mixed
     */
    public function creater(){
        $strAction = request()->param('action','list');
        $this->assign('action',$strAction);
        switch ($strAction){
            case 'list':
                $arrResult = DbTable::instance()->tableList();
                if ($arrResult[1] != 1){
                    $this->error($arrResult[0],'');
                }
                $this->assign('list',$arrResult[2]);
                break;
            case 'info':
                $arrResult = DbTable::instance()->tableInfo();
                if ($arrResult[1] != 1){
                    $this->error($arrResult[0],'');
                }
                $this->assign('info',$arrResult[2]);
                break;
            case 'save':
                $arrResult = DbTable::instance()->tableCreate();
                return json($arrResult);
                break;
        }
        return $this->fetch(FRAME_PATH.'Tpl/table_creater.tpl');
    }


}