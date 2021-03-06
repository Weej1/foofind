<?php

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define path to foofind directory
defined('FOOFIND_PATH')     || define('FOOFIND_PATH', realpath(dirname(__FILE__) . '/../'));

// Define application environment
defined('APPLICATION_ENV')  || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array( realpath(APPLICATION_PATH . '/../library'), get_include_path())));

if ( !file_exists('/tmp') ){
    defined('TMP_PATH') ||  define('TMP_PATH',  'c:/tmp');
} else {
    defined('TMP_PATH') || define('TMP_PATH',  '/tmp');
}

//define the static assets path
if ( APPLICATION_ENV == 'development' ){
    defined('STATIC_PATH') ||  define('STATIC_PATH',  '');
} else {
    defined('STATIC_PATH') || define('STATIC_PATH',  'http://static.foof.in');
}

require_once 'Zend/Application.php';