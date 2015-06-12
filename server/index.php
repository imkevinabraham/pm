<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('APP_ROUTE', ROOT . '/application');

//TODO:namespace this stuff


//make sure the script is installed
if(!file_exists('config' . DS . 'config.php')){
    echo json_encode(array(
        'code' => 200,
        'auth' => 'not_installed'
    ));

    exit;
}

require_once ('config' . DS . 'config.php');
require_once ('core' . DS . 'shared.functions.php');
require_once ('core' . DS . 'request.class.php');

spl_autoload_register('duet_autoload');

//todo:app class
set_reporting();

Language::load();

new Request();

?>