<?php

require_once 'init.php';
$application = new Zend_Application( APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
$application->bootstrap()->run();