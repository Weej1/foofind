<?php
require_once APPLICATION_PATH . '/models/Files.php';
require_once APPLICATION_PATH.'../../library/Foofind/TamingTextClient.php';

function utf8_urldecode($str) {
    $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
    return html_entity_decode($str,null,'UTF-8');;
}

class TamingController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();

        $this->config = Zend_Registry::get('config');
        $tamingServer = split(":", $this->config->taming->server);

        $this->taming = new TamingTextClient($tamingServer[0], (int)$tamingServer[1]);
        $this->lang = $this->_helper->checklang->check();
    }

    public function autocompleteAction()
    {
        $q = stripcslashes(strip_tags($this->_getParam('q')));
        $t = stripcslashes(strip_tags($this->_getParam('t')));
        
        if(strpos(strtolower($q), "%u")!==FALSE)
            $q = utf8_urldecode($q);
        else if(!mb_check_encoding($q, 'UTF-8'))
            $q = utf8_encode($q);
        
        // build a caching object
        if ($this->config->cache->taming) {
            // build a caching object
            $oCache = Zend_Registry::get('cache');
            $key = "tam_".md5("{$this->lang}_t{$t}_{$q}.");
            $existsCache = $oCache->test($key);
        } else {
            $existsCache = false;
        }

        if  ( $existsCache  ) {
            //cache hit, load from memcache.
            echo $oCache->load( $key );
        } else {
            $w = array(array("l"=>-250, "c"=>1, $this->lang=>100));
            if ($t) {
                foreach (Model_Files::ct2ints($t) as $cti)
                    $w[0][Model_Files::cti2sct($cti)] = 500;
            }
            $result = $this->taming->tameText($q, $w, 4);
            echo $result;
            if ($this->config->cache->taming) $oCache->save( $result, $key );
        }
    }
}