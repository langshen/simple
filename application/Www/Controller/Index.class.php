<?php
namespace Www\Controller;
use Spartan\Extend\DbTable;
use Spartan\Lib\Controller;
use Model\Admin\Users;

defined('APP_NAME') or die('404 Not Found');

class Index extends Controller {

    public function index(){


        return $this->fetch();
    }

    public function save(){
        var_dump($this->request->post());
        $result = $this->request->UpFile('file1')
            ->setConfig(['save_path'=>attachPath(),'url_root'=>'/public/'])
            ->saveToFile();
            var_dump($result);

    }

    public function verify(){
        \Spartan\Lib\Image::instance();
    }




    public function tableCreate(){
        $arrInfo = DbTable::instance()->tableCreate();
        var_dump($arrInfo);
    }

}