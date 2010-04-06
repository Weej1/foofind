<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setEncoding('UTF-8');
        $view->doctype('XHTML1_STRICT');

        //ZendX_JQuery::enableView($view);

       

    }

    protected function _initAutoload()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath' => APPLICATION_PATH));
        
        return $moduleLoader;
    }



    protected function _initPlugins()
    {

        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin ( new Foofind_Controller_Plugin_Language() );

    
	//setting the language route url
       
        $route = new Zend_Controller_Router_Route ( ':language/:controller/:action/*', array ('language' => $_COOKIE['lang'], 'module' => 'default', 'controller' => 'index', 'action' => 'index' ) );

	$router = $front->getRouter ();
	// Remove any default routes
	$router->removeDefaultRoutes ();
	$router->addRoute ( 'default', $route );
	$front->setRouter ( $router );

    return $front;
    }


}

