<?php
class ApiController extends Zend_Controller_Action
{
   
    protected $_server;


    public function init()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();

        require_once APPLICATION_PATH.'/controllers/SearchController.php';
        require_once APPLICATION_PATH.'/models/ContentType.php';

    }


    public function indexAction()
    {

        require_once 'FoofindApi.php';
        $server = new Zend_Rest_Server();
        $server->setClass('FoofindApi');
        $server->setEncoding('utf-8');
        $server->handle();
        
    }

}