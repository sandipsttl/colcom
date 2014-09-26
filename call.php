<?php
include(dirname(__FILE__).'/includes/include.php');
    if(isset($_REQUEST['fn']) && strlen($_REQUEST['fn'])>0)
    {
        $function_name = $_REQUEST['fn'];
        if(in_array($function_name,Config::read('FUNCTION_LIST')))
        {
            $handler = array('API',$function_name);
            $response = call_user_func($handler);

            /**
             * UNCOMMENT TO RENDER UNICODE RESPONSE
             */
             /*
             ORM::raw_execute("SET CHARACTER SET utf8");
             ORM::raw_execute("SET SESSION collation_connection ='utf8_general_ci'");
             header('Content-Type: text/html; charset=utf-8');
             */
             echo $response;
        }
        else
        {
            echo json_encode(array('status'=>"script doesn't exist or access forbidden"));
        }
    }
?>