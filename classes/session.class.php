<?php
abstract class Session{
    public static function authenticate()
    {
        global $Lang;
        
        $response = array('status'=>'failure','message'=>'');
        $rules = array(
        	'user_id'       => 'required|integer',
            'session_token' => 'required'  
        );            
        $filters = array(
            'user_id'       => 'trim|sanitize_numbers',
            'session_token' => 'trim',
        );
        $validator = new GUMP();
        
        $_REQUEST = $validator->filter($_REQUEST, $filters);
        $validated = $validator->validate($_REQUEST, $rules);

        if($validated === TRUE) 
        {
            $user_id = $_REQUEST['user_id'];
            $session_token = $_REQUEST['session_token']; 
            $session = ORM::for_table('user_sessions')->where_equal('user_id',$user_id)->where_equal('session_token',$session_token)->find_one();
            if($session)
            {
                return $session;
            }
            else
            {
                $response['message'] = $Lang['messages']['authentiation_failed'];
                echo json_encode($response, JSON_NUMERIC_CHECK);
                exit();                    
            }            
        } 
        else 
        {
            $response['message'] = $validator->get_readable_errors();
            echo json_encode($response, JSON_NUMERIC_CHECK);
            exit();
        }      
    }        
}
?>