<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initMetadataCache()
    {
        $cache = Zend_Cache::factory('Core', 'File',
                array('automatic_serialization' => true),
                array('cache_dir' => TMP_PATH));
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
    }

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
                            'Exception')
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
        
        //init the routes
        $router = $front->getRouter ();

        //set the language route url (the default also)
        $routeLang = new Zend_Controller_Router_Route ( ':language/:controller/:action/*', array ('language' => null, 'controller' => 'index', 'action' => 'index', 'module' =>'default' ) );

        //set the download file page route
        $routeDownload = new Zend_Controller_Router_Route( ':language/download/:id/*', array( 'language' => null, 'controller' => 'download', 'action' => 'file') );

        //set the vote page route
        $routeVote = new Zend_Controller_Router_Route( ':language/vote/:action/:id/:type/*', array( 'language' => null, 'controller' => 'vote', 'action' => 'file') );

        //set the user profile route
        $routeProfile = new Zend_Controller_Router_Route( ':language/profile/:username', array( 'language' => null, 'controller' => 'user', 'action' => 'profile') );

        //set the api route
        $routeApi = new Zend_Controller_Router_Route('/api/:action/*', array(  'controller' => 'api', 'action' => 'index') );

        $router->addRoute ( 'default', $routeLang );//important, put the default route first!
        $router->addRoute ( 'download/id', $routeDownload );
        $router->addRoute ( 'vote', $routeVote );
        $router->addRoute ( 'profile/username', $routeProfile );
        $router->addRoute ( 'api', $routeApi );
        
        //set all routes
        $front->setRouter ( $router );

        return $front;
    }


}
