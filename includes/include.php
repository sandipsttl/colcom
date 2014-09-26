<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once(dirname(dirname(__FILE__))).'/classes/config.class.php';
        
spl_autoload_register('Config::include_class');
$lang = Config::load_language('en');

/*
Config::include_class('orm');
Config::include_class('gump');
Config::include_class('helper','helpers');
Config::include_class('api');
*/
//require_once(Config::read('BASE_PATH').'/classes/Idiorm.class.php');
//require_once(Config::read('BASE_PATH').'/classes/Gump.class.php');
//require_once(Config::read('BASE_PATH').'/helpers/DBHelper.class.php');
//require_once(Config::read('BASE_PATH').'/helpers/Helper.class.php');
//require_once(Config::read('BASE_PATH').'/classes/Session.class.php');
//require_once(Config::read('BASE_PATH').'/classes/Simple_mail.class.php');
//require_once(Config::read('BASE_PATH').'/language/english/en.php');

//require_once(Config::read('BASE_PATH').'/classes/Api.class.php');

ORM::configure(Config::read('ORM_CONFIGURATION'));
?>