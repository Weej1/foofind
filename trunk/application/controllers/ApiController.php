<?php

class ApiController extends Zend_Controller_Action
{
   
    protected $_server;


    public function init()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();
    }


    public function indexAction()
    {

        require_once 'FoofindApi.php';
        $server = new Zend_Rest_Server();
        $server->setClass('SearchrestServer');
        $server->setEncoding('utf-8');
        $server->handle();
        

    }


   
}