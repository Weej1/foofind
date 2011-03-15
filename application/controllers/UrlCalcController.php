<?php

class UrlCalcController extends Zend_Controller_Action {

    public function init() {
        // validate domain foofind
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
        $this->view->lang =  $this->_helper->checklang->check();
    }

    public function indexAction() {

        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) $this->_redirect("/");
        if ( $auth->getIdentity()->type != 1 ) $this->_redirect("/");

        require_once APPLICATION_PATH.'/controllers/helpers/Fileutils.php';
        $helper = new Zend_Controller_Action_Helper_Fileutils();

        if ($this->_getParam('src')!=null) {
            $this->view->src=$this->_getParam('src');
            $uri = substr(hash("sha256", $this->view->src), 0, 24);
            $this->view->hex=$uri;
            $this->view->url=$helper->uri2url($helper->hex2uri($uri));
        } elseif($this->_getParam('url')!=null){
            $this->view->hex=$helper->uri2hex($helper->url2uri($this->_getParam('url')));
            $this->view->url=$this->_getParam('url');
        } elseif($this->_getParam('hex')!=null){
            $this->view->url=$helper->uri2url($helper->hex2uri($this->_getParam('hex')));
            $this->view->hex=$this->_getParam('hex');
        }
    }
}

