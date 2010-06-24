<?php
class Zend_Controller_Action_Helper_Checkdomain extends Zend_Controller_Action_Helper_Abstract {
    
    function check(){
         //some people point his domains to our ips, so if not *.foofind.* ... goto hell
        $domain = $_SERVER['HTTP_HOST'];
        $domain = explode(".", $domain);

        if ($domain[0] != 'foofind'){
            header('Location: http://example.com' ,301);
            exit;
        }
    }

    

}