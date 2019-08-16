<?php
namespace Admin\Controller;
use Admin\Common\Control;

class Product extends Control {

    public function type(){
        $strAction = $this->getUrl(2,'index');
        switch ($strAction){
            case 'list':

                break;
        }
        return $this->fetch();
    }

}