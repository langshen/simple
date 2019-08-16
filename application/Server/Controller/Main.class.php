<?php
namespace Server\Controller;
use Spartan\Lib\Console;

/**
 * 入口主类，多个CLI服务，可以使用多个入口
 * Class Main
 * @package Server\Controller
 */
class Main extends Console{

    /**
     * 入口函数，方便和WEb一样的URL模式，可用如下调用：
     * php index.php member/login
     * getUrl可以得到传入的参数，上面例子中"member/login"就是URL，和WEB一样。     *
     */
    public function runMain(){
        $arrUrl = explode('/',config('URL'));
        $this->console('Hello, this is runMain.',$arrUrl,true);

    }
}