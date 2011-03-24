<?php

if ( !file_exists('/tmp') ){
    defined('TMP_PATH') ||  define('TMP_PATH',  'c:/tmp');
} else {
    defined('TMP_PATH') || define('TMP_PATH',  '/tmp');
}


// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define path to foofind directory
defined('FOOFIND_PATH')
    || define('FOOFIND_PATH', realpath(dirname(__FILE__) . '/../'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

//define the static assets path
if ( APPLICATION_ENV == 'production' ){
    defined('STATIC_PATH') || define('STATIC_PATH',  'http://static.foof.in');
    defined('WEB_PATH') || define('WEB_PATH', 'http://foofind.com');

    $serverName = $_SERVER["SERVER_NAME"];
    if(strlen($serverName)==0 || substr_compare(WEB_PATH, $serverName, -strlen($serverName), strlen($serverName)) !== 0) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: ".WEB_PATH.$_SERVER["REQUEST_URI"]);
        exit();
    }
} else {
    defined('STATIC_PATH') || define('STATIC_PATH',  'http://static.foofind.dev');
}

require_once 'Zend/Application.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()->run();
