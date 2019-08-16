<?php
namespace Admin\Controller;
use Admin\Common\Control;

class Web extends Control {

    /**
     * 幻灯分类
     */
    public function sliderType(){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'web_slider_type';
        switch ($strAction){
            case 'list':
                $options = Array(
                    'field'=>'a.*,b.name as pid_name',
                    'order'=>'a.id desc',
                    'join'=>Array(
                        '@.web_slider_type b on a.pid=b.id'
                    )
                );
                $arrInfo = dal($strTable)
                    ->setConfig(['auto'=>true,'count'=>true])
                    ->select($options);
                return $this->tableList($arrInfo);
            case 'select':
                $options = Array(
                    'field'=>Array(
                        'id,pid,name'
                    ),
                    'order'=>'id desc',
                    'limit'=>1000
                );
                $arrInfo = $this->dal($strTable)
                    ->setConfig(['auto'=>true,'array'=>true])
                    ->select($options);
                $this->ajaxMessage($arrInfo);//数组返回，需要加Config['array'] = true
                break;
            case 'view':
            case 'add':
                $intId = max(0,intval($this->request()->input('get.id')));
                if ($intId > 0){
                    $options = Array(
                        'where'=>Array(
                            'id'=>$intId
                        )
                    );
                    $arrInfo = $this->dal($strTable)->find($options);
                }else{
                    $arrInfo = [];
                }
                $this->assign('info',$arrInfo);
                break;
            case 'del':
                $options = Array(
                    'where'=>Array(
                        'id'=>$this->request()->input('id')
                    )
                );
                $arrInfo = $this->dal($strTable)
                    ->setConfig(['array'=>true])
                    ->delete($options);
                $this->ajaxMessage($arrInfo);
                break;
            case 'save':
                $objTable = $this->dal($strTable)->getTableCls();
                list($arrData,$arrResult) = $this->valid($objTable->arrRequired)->result();//提交验证并返回结果
                if ($arrResult[1] != 1){
                    $this->ajaxMessage($arrResult);
                }
                $arrResult = $this->dal($strTable)->setConfig(['array'=>true])->update($arrData);
                $this->ajaxMessage($arrResult);
                break;
        }
        $arrSearchType = dal($strTable)->getSearchCondition();
        $arrSymbol = dal($strTable)->getSearchSymbol();
        $this->assign('search_type',$arrSearchType);
        $this->assign('search_symbol',$arrSymbol);
        return $this->fetch('sliderType');
    }

    /**
     * 幻灯
     */
    public function slider(){
        $strAction = $this->getUrl(2,'index');
        $this->assign('action',$strAction);
        $strTable = 'web_slider';
        switch ($strAction){
            case 'list':
                $options = Array(
                    'field'=>'a.*,b.name as type_name',
                    'join'=>Array(
                        '@.web_slider_type b ON b.id = a.type_id'
                    ),
                    'order'=>'a.id desc',
                );
                $arrInfo = $this->dal($strTable)
                    ->setConfig(['auto'=>true,'count'=>true])
                    ->select($options);
                $this->tableList($arrInfo);
                break;
            case 'select':
                $options = Array(
                    'field'=>'a.id,title,name',
                    'join'=>Array(
                        '@.web_slider_type b ON b.id = a.type_id'
                    ),
                    'order'=>'a.id desc',
                    'limit'=>1000
                );
                $arrInfo = $this->dal($strTable)
                    ->setConfig(['auto'=>true,'array'=>true])
                    ->select($options);
                $this->ajaxMessage($arrInfo);//数组返回，需要加Config['array'] = true
                break;
            case 'view':
            case 'add':
                $intId = max(0,intval($this->request()->input('get.id')));
                if ($intId > 0){
                    $options = Array(
                        'where'=>Array(
                            'id'=>$intId
                        )
                    );
                    $arrInfo = $this->dal($strTable)->find($options);
                }else{
                    $arrInfo = [];
                }
                $this->assign('info',$arrInfo);
                break;
            case 'del':
                $options = Array(
                    'where'=>Array(
                        'id'=>$this->request()->input('id')
                    )
                );
                $arrInfo = $this->dal($strTable)
                    ->setConfig(['array'=>true])
                    ->delete($options);
                $this->ajaxMessage($arrInfo);
                break;
            case 'save':
                $objTable = $this->dal($strTable)->getTableCls();
                list($arrData,$arrResult) = $this->valid($objTable->arrRequired)->result();//提交验证并返回结果
                if ($arrResult[1] != 1){
                    $this->ajaxMessage($arrResult);
                }
                $arrResult = $this->dal($strTable)->setConfig(['array'=>true])->update($arrData);
                $this->ajaxMessage($arrResult);
                break;
            case 'upload':
                if (!$_FILES['face_file']){
                    $this->ajaxMessage('请上传图片！');
                }
                $clsAttachment = new Attachment();
                $strFileName = date('YmdHis',time()).rand(1000,9999).$clsAttachment->getExt($_FILES['face_file']['name']);
                $strFileNameUrl = '/slider/'.date("Ymd", time()).'/';
                $strTmpName = $_FILES['face_file']['tmp_name'];
                $arrResult = $clsAttachment->createDir(uploadPath().$strFileNameUrl);
                if ($arrResult[1] != 1){
                    $this->ajaxMessage('新建目录失败，请重新再试！');
                }
                if(!move_uploaded_file($strTmpName,uploadPath().$strFileNameUrl.$strFileName)){
                    $this->ajaxMessage('上传失败，请重新上传！');
                };
                $this->ajaxMessage('上传成功！',1,['url'=>attachUrl($strFileNameUrl.$strFileName),'path'=>$strFileNameUrl.$strFileName]);
                break;
        }
        $arrSearchType = $this->dal($strTable)->getSearchCondition();
        $arrSymbol = $this->dal($strTable)->getSearchSymbol();
        $this->assign('search_type',$arrSearchType);
        $this->assign('search_symbol',$arrSymbol);
        $this->display('slider');
    }

}