<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setEncoding('UTF-8');
        $view->doctype('XHTML1_STRICT');

        ZendX_JQuery::enableView($view);

    }

    protected function _initAutoload()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath' => APPLICATION_PATH));
        
        return $moduleLoader;
    }

    protected function _initZFDebug()
{

    if (APPLICATION_ENV!='production'){
    $autoloader = Zend_Loader_Autoloader::getInstance();
    $autoloader->registerNamespace('ZFDebug');

    $options = array(
        'plugins' => array('Variables',
                           'File' => array('base_path' => APPLICATION_PATH),
                           'Memory',
                           'Time',
                           'Registry',
                           'Exception')
    );

    # Instantiate the database adapter and setup the plugin.
    # Alternatively just add the plugin like above and rely on the autodiscovery feature.
    if ($this->hasPluginResource('db')) {
        $this->bootstrap('db');
        $db = $this->getPluginResource('db')->getDbAdapter();
        $options['plugins']['Database']['adapter'] = $db;
    }

    # Setup the cache plugin
    if ($this->hasPluginResource('cache')) {
        $this->bootstrap('cache');
        $cache = $this-getPluginResource('cache')->getDbAdapter();
        $options['plugins']['Cache']['backend'] = $cache->getBackend();
    }

    $debug = new ZFDebug_Controller_Plugin_Debug($options);

    $this->bootstrap('frontController');
    $frontController = $this->getResource('frontController');
    $frontController->registerPlugin($debug);
    }
}

    protected function _initPlugins()
    {

        Zend_Controller_Action_HelperBroker::addPath( APPLICATION_PATH .'/controllers/helpers');

        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin ( new Foofind_Controller_Plugin_Language() );        

        //init the routes
        $router = $front->getRouter ();

        
        //set the language route url (the default also)
        $routeLang = new Zend_Controller_Router_Route ( ':language/:controller/:action/*', array ('language' => $_COOKIE['lang'], 'controller' => 'index', 'action' => 'index', 'module' =>'default' ) );
        //set the download file page route
        $routeDownload = new Zend_Controller_Router_Route( ':language/download/:id/*', array( 'language' => $_COOKIE['lang'], 'controller' => 'download', 'action' => 'file') );
        
        //set the vote page route
        $routeVote = new Zend_Controller_Router_Route( ':language/vote/:action/:id/:type/*', array( 'language' => $_COOKIE['lang'], 'controller' => 'vote', 'action' => 'file') );
       
        //set the api route
        $routeApi = new Zend_Controller_Router_Route('/api/:action/*', array(  'controller' => 'api', 'action' => 'index') );


        $router->addRoute ( 'default', $routeLang );//important, put the default route first!
        $router->addRoute ( 'download/id', $routeDownload );
        $router->addRoute ( 'vote', $routeVote );
        $router->addRoute ( 'api', $routeApi );

        //set all routes
	$front->setRouter ( $router );
        
        
         return $front;
    }


}

