<?php

// load libraries
require_once __DIR__ . '/protected/lib/core50/src/CS50/CS50.php';
require_once 'Predis/Autoloader.php';

Predis\Autoloader::register();
@session_start();

// change the following paths if necessary
$yii = dirname(__FILE__).'/../yii/framework/yii.php';
$config = dirname(__FILE__).'/protected/config/main.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG', true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

require_once($yii);
Yii::createWebApplication($config)->run();
