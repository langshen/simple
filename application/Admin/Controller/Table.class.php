<?php
namespace Admin\Controller;
use Admin\Common\Control;
use Spartan\Extend\DbTable;

class Table extends Control {

    /**
     * Table Model 生成器
     * @return mixed
     */
    public function create(){
        $strAction = request()->param('action','list');
        $this->assign('action',$strAction);
        switch ($strAction){
            case 'list':
                $arrResult = DbTable::instance()->tableList();
                if ($arrResult[1] !== 0){
                    $this->error($arrResult[0],'');
                }
                $this->assign('list',$arrResult[2]);
                break;
            case 'info':
                $arrResult = DbTable::instance()->tableInfo();
                if ($arrResult[1] !== 0){
                    $this->error($arrResult[0],'');
                }
                $this->assign('info',$arrResult[2]);
                break;
            case 'save':
                $arrResult = DbTable::instance()->tableCreate();
                return $this->toApi($arrResult);
                break;
        }
        return $this->fetch(FRAME_PATH.'Tpl/table_creater.tpl');
    }

    /**
     * 查看Table下的类是否已经建表
     * @return mixed
     */
    public function build(){
        $strAction = request()->param('action','list');
        $this->assign('action',$strAction);
        switch ($strAction){
            case 'list':
                $arrResult = DbTable::instance()->modelList();
                if ($arrResult[1] !== 0){
                    $this->error($arrResult[0],'');
                }
                $this->assign('action','model_list');
                $this->assign('list',$arrResult[2]);
                break;
            case 'save':
                $arrResult = DbTable::instance()->modelCreate();
                return $this->toApi($arrResult);
                break;
        }
        return $this->fetch(FRAME_PATH.'Tpl/table_creater.tpl');
    }

}