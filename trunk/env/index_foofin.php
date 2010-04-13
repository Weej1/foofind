<?php
//foof.in index.php
$url =  $_SERVER['REQUEST_URI'];

$urlNum = explode("/", $url);


$urlNum[2];


//var_dump($urlNum);

//die();
header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . 'http://foofind.com/'.$urlNum[1].'/download/'.hexdec($urlNum[2]));
exit;


?>
