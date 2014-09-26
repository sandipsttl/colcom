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
                    <?php
                        if(isset($data['verify']) && $data['verify'] == 1)
                        {
                            echo "Confirm your registration";
                        }
                        else
                        {
                            echo "Registration completed.";
                        } 
                    ?>
                  </td>
                </tr>
                <tr>
                  <td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tbody>
                        <tr>
                          <td style="padding: 20px 0px 30px; color: rgb(21, 54, 67); font-family: Arial,sans-serif; line-height: 20px; font-size: 15px;">
                            <p>
                                Thank you for registering with COLCOM. <?php if(isset($data['verify']) && $data['verify'] == 1) echo "Please click the link below to complete your registration" ?>.
                                <br />
                                <?php
                                if(isset($data['verify']) && $data['verify'] == 1)
                                {
                                    ?>
                                        &nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo $confirmation_link; ?>">Verify account.</a>
                                        <br /><br />                                    
                                    <?php
                                }
                                ?>
                                You can find your credentials below:
                                <br />&nbsp;&nbsp;&nbsp;&nbsp;Username: <b style="text-decoration: none;"><?php echo $user->email; ?></b>
                                <br />&nbsp;&nbsp;&nbsp;&nbsp;Password: <b><?php echo $_REQUEST['password']; ?></b>
                                <br /><br /><br /><br /><br />                            
                                <font size="-2">
                                  <b>
                                    <?php echo strtoupper($Lang['messages']['system_email']); ?>
                                  </b>
                                </font>
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