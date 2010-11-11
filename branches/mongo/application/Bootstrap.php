<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initRegistry()
    {
        $config = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production' );

        // build a caching object
        $backend = new Zend_Cache_Backend_Memcached(
                        array('servers' => array( array('host' => '127.0.0.1', 'port' => '11211') ),
                                'compression' => true) );
        $frontend = new Zend_Cache_Core(array('caching' => true, 'lifetime' => 3600,
                        'cache_id_prefix' => 'foofind', 'automatic_serialization' => true) );
        $cache = Zend_Cache::factory( $frontend, $backend );

        // databases connections
        $main = new Mongo($config->mongo->server, array("connect"=>false));
        $oldids = new Mongo($config->mongo->oldids, array("connect"=>false));

        //registry set
        Zend_Registry::set('db_main', $main);
        Zend_Registry::set('db_oldids', $oldids);

        Zend_Registry::set('cache', $cache);
        Zend_Registry::set('config', $config);
    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setEncoding('UTF-8');
        $view->doctype('XHTML1_STRICT');

        ZendX_JQuery::enableView($view);
        $view->jQuery()->enable();
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")!==FALSE)
            $view->jQuery()->addJavascriptFile("/js/jquery.msbr.min.js");
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

        if (APPLICATION_ENV!='production')
        {

            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloader->registerNamespace('ZFDebug');

            $options = array(
                    'plugins' => array('Variables',
                            'File' => array('base_path' => APPLICATION_PATH),
                            'Memory',
                            'Time',
                            'Registry',
                            'Exception',
                            'Xhprof')
            );
            
            if ($this->hasPluginResource('db'))
            {
                $this->bootstrap('db');
                $db = $this->getPluginResource('db')->getDbAdapter();
                $options['plugins']['Database']['adapter'] = $db;
            }

            # Setup the cache plugin
            if ($this->hasPluginResource('cache'))
            {
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
        //$front->registerPlugin ( new Foofind_Controller_Plugin_Language() );

        //init the routes
        $router = $front->getRouter ();

        //set the language route url (the default also)
        $routeLang = new Zend_Controller_Router_Route ( ':language/:controller/:action/*', array ('language' => null, 'controller' => 'index', 'action' => 'index', 'module' =>'default' ) );
        //set the download file page route
        $routeDownload = new Zend_Controller_Router_Route( ':language/download/:uri/*', array( 'language' => null, 'controller' => 'download', 'action' => 'file') );
        //set the vote page route
        $routeVote = new Zend_Controller_Router_Route( ':language/vote/:action/:id/:type/*', array( 'language' => null, 'controller' => 'vote', 'action' => 'file') );
        //set the user profile route
        $routeProfile = new Zend_Controller_Router_Route( ':language/profile/:username', array( 'language' => null, 'controller' => 'user', 'action' => 'profile') );
        //set the edit username route
        $routeEdituser = new Zend_Controller_Router_Route( ':language/user/edit/:username', array( 'language' => null, 'controller' => 'user', 'action' => 'edit') );
        //set the api route
        $routeApi = new Zend_Controller_Router_Route('/api/:action/*', array(  'controller' => 'api', 'action' => 'index') );

        $router->addRoute ( 'default', $routeLang );//important, put the default route first!
        $router->addRoute ( 'download/uri', $routeDownload );
        $router->addRoute ( 'vote', $routeVote );
        $router->addRoute ( 'profile/username', $routeProfile );
        $router->addRoute ( 'user/edit', $routeEdituser );
        $router->addRoute ( 'api', $routeApi );

        //set all routes
        $front->setRouter ( $router );

        return $front;
    }
}