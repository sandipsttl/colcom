<?php

/*
	It is recommended for you to change 'auth_login_incorrect_password' and 'auth_login_username_not_exist' into something vague.
	For example: Username and password do not match.
*/

return array(
        'reg_email' => array(

             'subject'=> "Confirmation link - COLCOM"
            ,'body'   => "Hello<br />
                        Thank you for registering with us. Here are your login details...<br />

                        Email: %s <br />

                        Confirmation Link: %s<br />

                        Thank You<br />

                        Administrator<br />
                        %s
                        <br /><br />

                        ______________________________________________________<br />
                        THIS IS AN AUTOMATED RESPONSE. DO NOT RESPOND TO THIS EMAIL.<br /><br />"
            ,'sent' => "Your account has been created and a verification email has been sent to your "
                    ."registered email address. Please click on the verification link included in the "
                    ."email to activate your account. Your account will not be activated until you "
                    ."verify your email address."
            ,'not_sent' => "Mail sending error. No confirmation link has been sent to your email"
        ),
        'reset_email'=>array(
             'subject'=>"Colcom account password reset"
            ,'body'=>"Your new password is: %s"
            ,'sent' => "New password has been sent to your email. Please follow up."
            ,'not_sent' => "Mail sending error."
        ),
        'messages'=>array(
             'save_error'=>"Faiure in data saving."
            ,'authentiation_failed' => "Authentication failed."
            ,'user_not_found'=>"User not found."
            ,'inactive'=>"User still inactive. Please check your email for confirmation link."
            ,'pass_change_success'=>"Password successfully changed."
            ,'success'=>"success"
            ,'failure'=>"failure"
            ,'reset_success'=>"Password successfully reset."
            ,'param_empty'=>"Parameter value empty."
            ,'invalid_param'=>"Invalid parameter."
            ,'unauth_event'=>"Invalid/unauthorized access requested."
            ,'self_invitation'=>"Self invitation not allowed."
            ,'invite_stat'=>"%s Invited, %s Not invited"
            ,'invitation_status_change_success'=>"Invitation status changed successfully."
            ,'invitation_status_change_error'=>"Response to the invitation was not successfuly committed."
            ,'invitation_status_wrong_answer'=>"Response to the invitation was wrong."
            ,'invitation_removed'=>"Invitation is removed successfully."
            ,'unauth_or_self_event'=>"Either the event does not exist or the user is not allowed to respond to a self created event."
            ,'join_event_success'=>"Successfully joined the event."
            ,'maybe_event_success'=>"May join the event."
            ,'decline_event_success'=>"Event invitation declined."
            ,'unauth_event_edit'=>"The current user did not create this event."
            ,'event_not_found'=>"Event not found."
            ,'responded_event'=>"The event has been responded. It cannot be edited now."
            ,'event_deleted'=>"Event successfully cancelled."
            ,'event_not_deleted'=>"The event could not be cancelled."
            ,'arrival_unspecified'=>"Please provide an approximate arrival time."
            ,'sign_out_success'=>"Sign out successful."
            ,'request_to_self'=>"You cannot make friends with yourself."
            ,'freq_pending'=>"Request pending."
            ,'freq_accepted'=>"Request accepted."
            ,'freq_denied'=>"Request denied."
            ,'freq_mismatch'=>"Request did not match."
            ,'freq_not_found'=>"Request not found."
            ,'freq_sent_success'=>"Friend request successfully sent."
            ,'freq_removed'=>"Friend Requests removed successfully."
            ,'freq_already_accepted'=>"Friendship already exists."
            ,'saving_failed'=>"Database Error: Data could not be saved."
            ,'email_exists'=>"This email has been registered already."
            ,'reset_failure'=>"Reset failure."
            ,'system_email'=>"*** This is an automatically generated email, please do not reply ***"
            ,'reg_confirmed'=>"Registration completed successfully. You can now sign in to the app."
            ,'user_update_success'=>"User successfully updated."
            ,'sync_stat'=>"%d contacts synced."
            ,'delete_error'=>"Error in deletion."
            ,'edit_cur_user_success' => "User information updated successfully."
            ,'push_reg_success' => "Device Registration successful."
            ,'noti_send_success' => "Notification successfully sent."
            ,'noti_send_failure' => "Notification wasn't sent."
            ,'noti_event_modified' => "Event created by %s scheduled on %s happening at %s has been modified."
            ,'noti_event_invitation' => "You have been invited to an event %s by %s" //"You have been invited to an event scheduled %s happening at %s by %s."
            ,'noti_event_cancelled' => "%s  has cancelled an event."
            ,'noti_event_accepted' => "%s accepted the event request."
            ,'noti_event_rejected' => "%s rejected the event request."
            ,'noti_event_maybe' => "%s may or may not join the event."
            ,'new_fb_sign_up' => "SIGN_UP_REQUIRED"
            ,'more_invited' => "%s+%d"
            ,'none_responded' => "nobody"
            ,'response_old_event' => "You cannot respond to an old event."
            ,'photo_success' => "Profile photo is successfully updated."
            ,'photo_failure' => "Profile photo update failed. Try later."
            ,'group_created' => "Group created successfully."
            ,'self_group_member'=>"You can not add yourself."
            ,'group_exists' => "This group has been created already."
        )
);