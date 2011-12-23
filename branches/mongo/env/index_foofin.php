<?php
//foof.in index.php
$url =  $_SERVER['REQUEST_URI'];

$urlNum = explode("/", $url);

$langs = array('en', 'es', 'fr', 'it', 'pt', 'tr', 'zh', 'ca', 'gl');

if (!$urlNum[1] || !is_numeric($urlNum[1])) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: http://foofind.com');
    exit;
}

$langnum = intval($urlNum[1])-1;
if ($langnum<0 || $langnum>=count($langs)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: http://foofind.com');
    exit;
}

$langcod = $langs[$langnum];
   
$id = $urlNum[2];
if (strlen($id)!=16) $id = hexdec($id);

header('HTTP/1.1 301 Moved Permanently');
header('Location: http://foofind.com/'.$langcod.'/download/'.$id);
exit;
?>
