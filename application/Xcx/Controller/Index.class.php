<?php
namespace Xcx\Controller;
use Spartan\Lib\Controller;

defined('APP_NAME') or die('404 Not Found');

class Index extends Controller {

    public function index(){

        return $this->fetch();
    }

}