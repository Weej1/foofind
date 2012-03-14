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

$allowedDomains = array("foofind.is", "foofind.com");
$serverName = $_SERVER['HTTP_HOST'];
defined('WEB_PATH') || define('WEB_PATH', $serverName);

if ( APPLICATION_ENV == 'production' || (APPLICATION_ENV == 'staging' && preg_match("/bot|spider/i", $_SERVER["HTTP_USER_AGENT"])>0) ){
    defined('STATIC_PATH') || define('STATIC_PATH',  'http://static.foof.in');
    if(strlen($serverName)==0 || !in_array($serverName, $allowedDomains)) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: ".$allowedDomains[0].$_SERVER["REQUEST_URI"]);
        exit();
    }
} else {
    defined('STATIC_PATH') || define('STATIC_PATH',  '');
}

require_once 'Zend/Application.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()->run();
