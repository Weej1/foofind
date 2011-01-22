<?php
//foof.in index.php
$url =  $_SERVER['REQUEST_URI'];

$urlNum = explode("/", $url);

        if (!$urlNum[1]) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: http://foofind.com');
                exit;
        }

	if ($urlNum[1] == 1) $urlNum[1] = 'en';
        if ($urlNum[1] == 2) $urlNum[1] = 'es';

        $id = $urlNum[2];
        if (strlen($id)!=16) $id = hexdec($id);
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: http://foofind.com/'.$urlNum[1].'/download/'.$id);

        exit;
?>
