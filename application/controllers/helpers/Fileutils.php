<?php
class Zend_Controller_Action_Helper_Fileutils extends Zend_Controller_Action_Helper_Abstract {
    


    function url2uri($url){

         //reverse id
       $uri = str_replace('-', '/', $url);
       $uri = str_replace('!', '+', $uri);
       $uri = base64_decode($uri ) ;
        
        return $uri;

    }

    function uri2hex($uri) {
        return bin2hex($uri);
    }




}