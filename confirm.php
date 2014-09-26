<?php
include(dirname(__FILE__).'/includes/include.php');
    $response = "";
    $error = true;
    $rules = array(
    	'user'=>'required',
        'cc'=>'required'  
    );
    $filters = array(
        'user'=>'trim',
        'cc'=>'trim'            
    );
    $validator = new GUMP();    
    $data = $validator->sanitize($_REQUEST);    
    $data = $validator->filter($data, $filters);
    $validated = $validator->validate($data, $rules);
    if($validated === TRUE)
    {
        $user = ORM::for_table('users')
        ->where_equal('md5_id',$data['user'])
        ->where_equal('confirmation_code',$data['cc'])
        ->where_equal('confirmed',0)
        ->find_one();
        if($user)
        {
            $user->md5_id = '';
            $user->confirmation_code = '';
            $user->confirmed = 1;
            $user->updated_at = date("Y-m-d H:i:s", time());
            if($user->save())
            {
                $error = false;
                $response = $Lang['messages']['reg_confirmed'];
            }               
        }
        else
        {
            $response = $Lang['messages']['unauth_event'];
        }
    }
    else
    {
        $response = $Lang['messages']['unauth_event'];
    }      
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <title>
      Confirm Registration - Colcom
    </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
  </head>
  <body>
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
      <tbody>
        <tr>
          <td style="padding: 0 0 30px 0;">
            
            <table width="600" cellspacing="0" cellpadding="0" border="0" align="center" style="border: 1px solid #cccccc; border-collapse: collapse;">
              <tbody>
                <tr>
                  <td bgcolor="#003259" align="center" style="padding: 40px 0 30px 0; color: #FFFFFF; font-size: 24px; font-weight: bold; font-family: Arial, sans-serif;">
                    <?php echo $error?trim($response,'.'):"Registration completed" ?>
                  </td>
                </tr>
                <tr>
                  <td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tbody>
                        <tr>
                          <td style="padding: 20px 0px 30px; color: rgb(21, 54, 67); font-family: Arial,sans-serif; line-height: 20px; font-size: 15px;">
                            <p <?php if($error) echo 'style="color: #CC0000"'; ?>>
                                <b><?php echo $response; ?></b>
                            </p>
                          </td>
                        </tr>
                        
                      </tbody>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td bgcolor="#ee4c50" style="padding: 30px 30px 30px 30px;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tbody>
                        <tr>
                          <td width="75%" style="color: #ffffff; font-family: Arial, sans-serif; font-size: 14px;">
                            &copy; Colcom 2013
                            <br/>
                            <a style="color: #ffffff;" href="#">
                              <font color="#ffffff">
                                Subscribe
                              </font>
                            </a>
                            &nbsp;|&nbsp;
                            <a style="color: #ffffff;" href="#">
                              <font color="#ffffff">
                                Account Policy
                              </font>
                            </a>                            
                          </td>
                          <td width="25%" align="right">
                            <table cellspacing="0" cellpadding="0" border="0">
                              <tbody>
                                <tr>
                                  <td style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">
                                    <a style="color: #ffffff;" href="http://www.twitter.com/">
                                      <img width="38" border="0" height="38" style="display: block;" alt="Twitter" src="<?php echo Config::read('BASE_URL').'/assets/images/tw.gif' ?>"/>
                                    </a>
                                  </td>
                                  <td width="20" style="font-size: 0; line-height: 0;">
                                    &nbsp;
                                  </td>
                                  <td style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">
                                    <a style="color: #ffffff;" href="http://www.twitter.com/">
                                      <img width="38" border="0" height="38" style="display: block;" alt="Facebook" src="<?php echo Config::read('BASE_URL').'/assets/images/fb.gif' ?>" />
                                    </a>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            
          </td>
        </tr>
      </tbody>
    </table>
    
  </body>
</html>