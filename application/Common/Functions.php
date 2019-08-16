<?php
/*
*项目的常用、公共的函数、常量，可以在模版直接使用，如：{$Spt.wwwUrl}。
*/
defined('APP_NAME') or die('404 Not Found');

function attachPath($path = ''){
    return "../attachroot/".trim($path,'/');
}
function uploadPath($path = ''){
    return "../attachroot/".trim($path,'/');
}
function wwwUrl(){
    return 'http://www.'.request()->rootDomain();
}
function staticUrl(){
    return '/public/';
}
function attachUrl($path = ''){
    return 'http://www.'.request()->rootDomain().'/attach/'.trim($path,'/');
}
function apiUrl(){
    return 'http://api.'.request()->rootDomain();
}
function adminUrl(){
    return 'http://www.'.request()->rootDomain().'/admin';
}