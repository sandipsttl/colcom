<?php

class Config {

    static $confArray;

    public static function read($name) {
        return self::$confArray[$name];
    }

    public static function write($name, $value) {
        self::$confArray[$name] = $value;
    }

    public static function include_class($class_name, $class_dir = 'classes') {
        include Config::read('BASE_PATH') . DIRECTORY_SEPARATOR . $class_dir . DIRECTORY_SEPARATOR . strtolower($class_name) . '.class.php';
    }

    public static function load_language($file_name) {
        return include Config::read('BASE_PATH') . '/language/' . $file_name . '.php';
    }

    public static function PHPMailerAutoload($classname) {
        //Can't use __DIR__ as it's only in PHP 5.3+
        $filename = Config::read('BASE_PATH') . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'class.' . strtolower($classname) . '.php';
        if (is_readable($filename)) {
            require $filename;
        }
    }

}

/**
 * LOCAL SETTINGS
 */
/*
  Config::write('BASE_PATH', 'F:\xampp\htdocs\colcom');
  Config::write('BASE_URL', 'http://localhost/colcom');
  Config::write('DB_USERNAME', 'root');
  Config::write('DB_PASSWORD', '');
  Config::write('DB_CONSTR', "mysql:host=localhost;dbname=colcom");
  //Config::write('ANDROID_PUSH_API_KEY',"AIzaSyAfb7bkpBGm3Fl67iueTiyygqiyqYcEgLQ");
  //Config::write('ENCODING','UTF-8');
 */
/**
 * SERVER SETTINGS
 */
Config::write('BASE_PATH', 'C:\xampp\htdocs\colcom');
Config::write('BASE_URL', 'http://localhost/colcom');
Config::write('DB_USERNAME', 'root');
Config::write('DB_PASSWORD', '');
Config::write('DB_CONSTR', "mysql:host=localhost;dbname=colcom");
Config::write('ENCODING', 'UTF-8');
Config::write('DEFAULT_LANGUAGE', 'en');
Config::write('DEFAULT_TIMEZONE', 'America/Los_Angeles');

// LIVE
/* Config::write('BASE_PATH', '/home/4/s/solutions/www/colcom');
  Config::write('BASE_URL', 'http://www.solutions.as/colcom');
  Config::write('DB_USERNAME', 'solutions2');
  Config::write('DB_PASSWORD', '74QkmbGV');
  Config::write('DB_CONSTR', "mysql:host=solutions2.mysql.domeneshop.no;dbname=solutions2");
  Config::write('ENCODING', 'UTF-8');
  Config::write('DEFAULT_LANGUAGE', 'en');
  Config::write('DEFAULT_TIMEZONE', 'America/Los_Angeles'); */


/**
 * LANGUAGE CONFIGURATION
 */
$Lang = Config::load_language(Config::read('DEFAULT_LANGUAGE'));

/**
 * ORM CONFIGURATION
 */
Config::write('ORM_CONFIGURATION', array(
    'connection_string' => Config::read('DB_CONSTR'),
    'username' => Config::read('DB_USERNAME')
    , 'password' => Config::read('DB_PASSWORD')
    //,'id_column' => 'id'
    , 'id_column_overrides' => array(
        'users' => 'user_id'
        , 'events' => 'event_id'
        , 'event_invitations' => 'invitation_id'
        , 'friend_requests' => 'request_id'
    ),
    'logging' => true
    , 'return_result_sets' => true

        /*
         * default options
         * _______________
         *
         * 'error_mode' => PDO::ERRMODE_EXCEPTION,
         * 'driver_options' => null,
         * 'identifier_quote_character' => null, // if this is null, will be autodetected
         * 'limit_clause_style' => null, // if this is null, will be autodetected
         * 'logger' => null,
         * 'caching' => false,*
         */
        )
);


/**
 * OTHER CONFIGURATIONS
 */
Config::write('SALT_LENGTH', 9);
Config::write('EMAIL_DOMAIN', 'Solutions.as');
Config::write('EMAIL_NO_REPLY', 'noreply@solutions.as');
Config::write('ANDROID_PUSH_API_KEY', "AIzaSyC8hrVVvp4iziCLgfyQ088gPDLhpuigwp0");

/**
 * SMTP CONFIGURATIONS
 */
Config::write('SMTP_HOST', 'smtp.domeneshop.no');
Config::write('SMTP_PORT', 587);
Config::write('SMTP_USERNAME', 'solutions5');
Config::write('SMTP_PASSWROD', 'hUV2Mwfz');
Config::write('EMAIL_FROM', 'noreply@solutions.as');
Config::write('REPLY_TO', 'noreply@solutions.as');


/**
 * EVENT CONSTANTS
 */
Config::write('E_NOT_INVITED', 'uninvited');
Config::write('E_PENDING', 1);
Config::write('E_JOINED', 2);
Config::write('E_MAYBE', 3);
Config::write('E_DECLINED', 4);
Config::write('E_I_SENT', 0);
Config::write('E_I_DELIVERED', 1);
Config::write('E_I_VIEWED', 2);

/**
 * FRIEND REQUEST CONSTANTS
 */
Config::write('F_PENDING', 1);
Config::write('F_ACCEPTED', 2);
Config::write('F_DENIED', 3);

/**
 * PUSH NOTIFICATION TYPES
 */
Config::write('EVENT_MODIFY', 1);
Config::write('EVENT_DELETE', 2);
Config::write('EVENT_INVITE', 3);
Config::write('EVENT_RESPONSE_YES', 4);
Config::write('EVENT_RESPONSE_NO', 5);
Config::write('EVENT_RESPONSE_MAYBE', 6);

/**
 * ALLOWED FUNCTION LIST
 */
Config::write('FUNCTION_LIST', array(
    'get_users',
    'sign_up',
    'sign_in',
    'sign_out',
    'change_password',
    'reset_password',
    'create_event',
    'invite_to_event',
    'get_event_list',
    'get_invited_events',
    'get_created_events',
    'get_event_details',
    'respond_to_event',
    'cancel_event',
    'make_friend_request',
    'make_friend_request_to_many',
    'accept_friend_request',
    'accept_friend_request_array',
    'deny_friend_request',
    'deny_friend_request_array',
    'remove_friend_request',
    'get_pending_friend_requests',
    'get_friend_list',
    'find_friends',
    'get_current_user',
    'edit_current_user',
    'remove_friends',
    'sync_phone_contacts',
    'remove_sent_requests',
    'send_test_mail',
    'send_event_notification',
    'upload_image',
    'change_char_set',
    'import_db',
    'test_timezone',
    'get_group_list',
    'create_group',
    'get_profile_info',
    'set_profile_info'
        )
);
?>