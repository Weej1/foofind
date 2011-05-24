<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initRegistry()
    {
        $config = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production', array("allowModifications"=>true) );
        if (file_exists(APPLICATION_PATH . '/configs/local.ini')) {
            $lconfig = new Zend_Config_Ini( APPLICATION_PATH . '/configs/local.ini' , 'production' );
            $config->merge($lconfig);
            $config->setReadOnly();
        }
        
        // build a caching object
        $backend = new Zend_Cache_Backend_Memcached(
                        array('servers' => array( array('host' => '127.0.0.1', 'port' => '11211') ),
                                'compression' => true) );
        $frontend = new Zend_Cache_Core(array('caching' => true, 'lifetime' => 3600,
                        'cache_id_prefix' => 'foofind', 'automatic_serialization' => true) );
        $cache = Zend_Cache::factory( $frontend, $backend );

        // databases connections
        $main = new Mongo("mongodb://{$config->mongo->server1},{$config->mongo->server2}", array("connect"=>false, "timeout"=>$config->mongo->timeout));
        $users = new Mongo($config->mongo->users, array("connect"=>false));
        $feedback = new Mongo($config->mongo->feedback, array("connect"=>false));
        $oldids = new Mongo($config->mongo->oldids, array("connect"=>false));

        //registry set
        Zend_Registry::set('db_main', $main);
        Zend_Registry::set('db_users', $users);
        Zend_Registry::set('db_feedback', $feedback);
        Zend_Registry::set('db_oldids', $oldids);

        Zend_Registry::set('cache', $cache);
        Zend_Registry::set('config', $config);

        $key = "files_count";
        $existsCache = $cache->test($key);
        if  ( $existsCache  ) {
            //cache hit, load from memcache.
            $total = $cache->load( $key  );
        } else {
            require_once APPLICATION_PATH . '/models/Files.php';
            $model = new Model_Files();
            $total = $model->countFiles();
            unset($model);
            $cache->save( $total, $key, array(), 60 );
        }

        Zend_Registry::set('files_count', $total);
    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setEncoding('UTF-8');
        $view->extra="";
        ZendX_JQuery::enableView($view);
        $view->jQuery()->enable();
        $view->doctype('XHTML1_TRANSITIONAL');
        
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")!==FALSE)
            $view->jQuery()->addJavascriptFile(STATIC_PATH."/js/jquery.msbr.min.js");

        date_default_timezone_set('Europe/Madrid');
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

        if (APPLICATION_ENV=='development')
        {

            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloader->registerNamespace('ZFDebug');

            $options = array(
                    'jquery_path'       => STATIC_PATH.'/js/jquery.min.js',
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
        //set the oauth route
        $routeOauthuser = new Zend_Controller_Router_Route( ':language/user/oauth/:type/:step', array( 'language' => null, 'controller' => 'user', 'action' => 'oauth') );
        //set the api route
        $routeApi = new Zend_Controller_Router_Route('/api/:action/*', array(  'controller' => 'api', 'action' => 'index') );

        $router->addRoute ( 'default', $routeLang );//important, put the default route first!
        $router->addRoute ( 'download/uri', $routeDownload );
        $router->addRoute ( 'vote', $routeVote );
        $router->addRoute ( 'profile/username', $routeProfile );
        $router->addRoute ( 'user/edit', $routeEdituser );
        $router->addRoute ( 'user/oauth', $routeOauthuser );
        $router->addRoute ( 'api', $routeApi );

        //set all routes
        $front->setRouter ( $router );

        return $front;
    }
}