<?php

abstract class API {

    /**
     * API::sign_up()
     *
     * @return
     */
    public static function sign_up() {
        global $Lang;
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'email' => 'required|valid_email',
            'phone_number' => 'required|max_len,15|min_len,8',
            'phone_number_tr' => 'exact_len,8',
            'name' => 'max_len,100|min_len,2',
            'password' => 'required|max_len,100|min_len,6',
            'verify' => 'integer|exact_len,1'
        );
        $filters = array(
            'email' => 'trim|sanitize_email',
            'phone_number' => 'trim|sanitize_string',
            'phone_number_tr' => 'trim|sanitize_string',
            'name' => 'trim|sanitize_string',
            'password' => 'trim|sha1',
            'verify' => 'trim'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $result = ORM::for_table('users')->where_equal('email', $data['email'])->find_one();
            if (ORM::for_table('users')->where_equal('email', $data['email'])->find_one()) {
                $response['message'][] = $Lang['messages']['email_exists'];
                return json_encode($response, JSON_NUMERIC_CHECK);
            } else {
                $confirmation_code = md5(rand(1000, 9999));
                $user = ORM::for_table('users')->create();
                if (isset($data['name'])) {
                    $user->name = $data['name'];
                } else {
                    $user->name = NULL;
                }
                $user->email = $data['email'];
                $user->password = $data['password'];
                $user->phone_number = $data['phone_number'];
                if (isset($data['phone_number_tr'])) {
                    $user->phone_number_tr = $data['phone_number_tr'];
                } else {
                    $user->phone_number_tr = NULL;
                }
                if (isset($data['verify']) && $data['verify'] == 1) {
                    $user->confirmation_code = $confirmation_code;
                    $user->confirmed = 0;
                } else {
                    $user->confirmed = 1;
                }
                $user->phonebook_contact_count = 0;
                $user->created_at = date("Y-m-d H:i:s", time());
                if ($user->save()) {
                    $user->md5_id = md5($user->user_id);
                    if ($user->save()) {
                        if (isset($_FILES['photo'])) {
                            API::upload_image($user->user_id);
                        }
                        $confirmation_link = Config::read('BASE_URL') . "/confirm.php?user=" . md5($user->user_id) . "&cc=" . $confirmation_code;
                        $address = array(
                            'email' => $user->email,
                            'name' => $user->name
                        );
                        $subject = $Lang['reg_email']['subject'];

                        ob_start();
                        include(Config::read('BASE_PATH') . '/docs/confirmation.php');
                        $email_body = ob_get_clean();
                        //$email_body = file_get_contents(Config::read('BASE_URL').'/docs/confirmation.php?a='.base64_encode($user->email).'&b='.base64_encode($_REQUEST['password']).'&c='.base64_encode($confirmation_link));
                        $email_sent = API::send_email($address, $subject, $email_body);
                        if ($email_sent) {
                            $response['status'] = $Lang['messages']['success'];
                            if (isset($data['verify']) && $data['verify'] == 1) {
                                $response['message'] = $Lang['reg_email']['sent'];
                            } else {
                                $response['message'] = $Lang['messages']['reg_confirmed'];
                            }
                        }
                        return json_encode($response, JSON_NUMERIC_CHECK);
                    } else {
                        $response['message'] = $Lang['save_error'];
                    }
                } else {
                    $response['message'][] = $Lang['messages']['save_error'];
                    return json_encode($response, JSON_NUMERIC_CHECK);
                }
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
            return json_encode($response, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * API::sign_in()
     *
     * @return
     */
    public static function sign_in() {
        global $Lang;
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'email' => 'required|valid_email',
            'password' => 'required|max_len,100|min_len,6',
                //'push_token' => 'required',
                //'client_type' => 'required|max_len,8|alpha'
        );
        $filters = array(
            'email' => 'trim|sanitize_email',
            'password' => 'trim|sha1',
            'fb_auth' => 'trim|sanitize_string',
            'push_token' => 'trim|sanitize_string',
            'client_type' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            if (isset($data['fb_auth']) && strlen($data['fb_auth']) > 0) {
                $user = ORM::for_table('users')->where_equal('email', $data['email'])->find_one();
                if ($user === false) {
                    $new_fb_user = array(
                        'status' => $Lang['messages']['success'],
                        'message' => array(
                            'action' => $Lang['messages']['new_fb_sign_up'],
                            'password' => rand(10000000, 99999999)
                        )
                    );
                    return json_encode($new_fb_user, JSON_NUMERIC_CHECK);
                    exit();
                }
            } else {
                $user = ORM::for_table('users')->where_equal('email', $data['email'])->where_equal('password', $data['password'])->find_one();
            }
            if ($user) {
                if ($user->confirmed) {
                    if (isset($data['push_token']) && isset($data['client_type'])) {
                        $user->push_token = $data['push_token'];
                        $user->client_type = strtoupper($data['client_type']);
                        $user->save();
                    }
                    $old_user_sessions = ORM::for_table('user_sessions')->where_equal('user_id', $user->user_id);
                    $old_user_sessions->delete_many();
                    $session_token = md5(mt_rand(100000, 999999));
                    $new_user_session = ORM::for_table('user_sessions')->create();
                    $new_user_session->user_id = $user->user_id;
                    $new_user_session->session_token = $session_token;
                    $new_user_session->signin_date = date("Y-m-d H:i:s", time());
                    $new_user_session->remote_ip = $_SERVER['REMOTE_ADDR'];
                    if ($new_user_session->save()) {
                        $output = array();
                        $output['user_id'] = $user->user_id;
                        $output['session_token'] = $session_token;
                        $output['phonebook_contact_count'] = $user->phonebook_contact_count;
                        $response['status'] = $Lang['messages']['success'];
                        $response['message'] = $output;
                    } else {
                        $response['message'][] = $Lang['messages']['save_error'];
                    }
                } else {
                    $response['message'][] = $Lang['messages']['inactive'];
                }
            } else {
                $response['message'][] = $Lang['messages']['authentiation_failed'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::sign_out()
     *
     * @return
     */
    public static function sign_out() {
        global $Lang;
        //$session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        //$user_sessions_deleted = ORM::for_table('user_sessions')->where_equal('user_id',$session->user_id)->delete_many();
        $user_sessions_deleted = ORM::for_table('user_sessions')->where_equal('user_id', $_REQUEST['user_id'])->delete_many();
        if ($user_sessions_deleted) {
            $response['status'] = 'success';
            $response['message'] = $Lang['messages']['sign_out_success'];
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::change_password()
     *
     * @return
     */
    public static function change_password() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'old_pass' => 'required',
            'new_pass' => 'required'
        );
        $filters = array(
            'old_pass' => 'trim|sha1',
            'new_pass' => 'trim|sha1'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $user = ORM::for_table('users')->where_equal('user_id', $session->user_id)->where_equal('password', $data['old_pass'])->find_one();
            if (!empty($user)) {
                $user->password = $data['new_pass'];
                $user->save();
                $response['status'] = $Lang['messages']['success'];
                $response['message'] = $Lang['messages']['pass_change_success'];
            } else {
                $response['message'][] = $Lang['messages']['authentiation_failed'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::reset_password()
     *
     * @return
     */
    public static function reset_password() {
        global $Lang;
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'email' => 'required|valid_email'
        );
        $filters = array(
            'email' => 'trim|sanitize_email'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $new_pass = "" . mt_rand(100000, 999999);
            $new_data = $validator->filter(array('new_pass' => $new_pass), array('new_pass' => 'trim|sha1'));
            $user = ORM::for_table('users')->where_equal('email', $data['email'])->find_one();
            if ($user) {
                if ($user->confirmed) {
                    $user->password = $new_data['new_pass'];
                    if ($user->save()) {
                        $address = array(
                            'email' => $user->email,
                            'name' => $user->name
                        );
                        $subject = $Lang['reset_email']['subject'];
                        $email_body = file_get_contents(Config::read('BASE_URL') . '/docs/reset.php?a=' . base64_encode($new_pass));
                        $email_sent = API::send_email($address, $subject, $email_body);
                        if ($email_sent) {
                            $response['status'] = $Lang['messages']['success'];
                            $response['message'] = $Lang['reset_email']['sent'];
                        } else {
                            $response['message'][] = $Lang['reset_email']['not_sent'];
                        }
                        /*
                          $mail = new PHPMailer();
                          $mail->isSendmail();
                          $mail->setFrom(Config::read('EMAIL_NO_REPLY'), Config::read('EMAIL_DOMAIN'));
                          $mail->addReplyTo(Config::read('EMAIL_NO_REPLY'), Config::read('EMAIL_NO_REPLY'));
                          $mail->addAddress($user->email, $user->name);
                          $mail->Subject = $Lang['reset_email']['subject'];
                          //$mail->msgHTML($body);
                          $mail->msgHTML(file_get_contents(Config::read('BASE_URL').'/docs/reset.php?a='.base64_encode($new_pass)));
                          if($mail->send())
                          {
                          $response['status'] = $Lang['messages']['success'];
                          $response['message'] = $Lang['reset_email']['sent'];
                          }
                          else
                          {
                          $response['message'][] = $Lang['reset_email']['not_sent'];
                          }
                         */
                    }
                }
            } else {
                $response['message'][] = $Lang['messages']['reset_failure'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::sync_phone_contacts()
     *
     * @return
     */
    public static function sync_phone_contacts() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'phonebook' => 'required'
        );
        $filters = array(
            'phonebook' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $sync_counter = 0;
            $phonebook = $data['phonebook'];
            if (count($phonebook) > 0) {
                //update current user
                $current_user = ORM::for_table('users')->find_one($session->user_id);
                $current_user->phonebook_contact_count = count($phonebook);
                $current_user->save();

                //get users from phone contacts
                $users_from_phonebook = ORM::for_table('users')->select('user_id')->where_in('phone_number_tr', $phonebook)->order_by_asc('user_id')->find_array();
                $users_from_phonebook = Helper::array_value_recursive('user_id', $users_from_phonebook);

                //get existing friends
                $existing_friends = array();
                $sql = "( SELECT receiver_id AS user_id
                            FROM friend_requests
                            WHERE sender_id = :user_id
                                AND receiver_id != :user_id
                        ) UNION
                        ( SELECT sender_id AS user_id
                            FROM friend_requests
                            WHERE sender_id != :user_id
                            AND receiver_id = :user_id
                        )
                        ORDER BY user_id ASC";
                $existing_friends = ORM::for_table('friend_requests')->raw_query($sql, array('user_id' => $session->user_id))->find_array();
                if (empty($existing_friends)) {
                    $existing_friends[] = $session->user_id;
                } else {
                    $existing_friends = Helper::array_value_recursive('user_id', $existing_friends);
                    $existing_friends[] = $session->user_id;
                }

                //get friends from phone but not friends in app yet
                $friends_from_phonebook = array_diff($users_from_phonebook, $existing_friends);

                //send frinds requests
                if (!empty($friends_from_phonebook)) {
                    foreach ($friends_from_phonebook as $friend) {
                        $friend_request = ORM::for_table('friend_requests')->create();
                        $friend_request->sender_id = $session->user_id;
                        $friend_request->receiver_id = $friend;
                        $friend_request->status = Config::read('F_PENDING');
                        if ($friend_request->save()) {
                            $sync_counter++;
                        }
                    }
                }
                $response['status'] = $Lang['messages']['success'];
                $response['message'][] = sprintf($Lang['messages']['sync_stat'], $sync_counter);
            } else {
                $response['status'] = $Lang['messages']['success'];
                $response['message'][] = sprintf($Lang['messages']['sync_stat'], $sync_counter);
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::create_event()
     *
     * @return
     */
    public static function create_event() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'event_type' => 'required|integer',
            'event_title' => 'required|min_len,3',
            'event_time' => 'required',
            'street_address' => 'required',
            'latitude' => 'required|float',
            'longitude' => 'required|float',
            'event_id' => 'integer',
            'comment' => 'required|min_len,6',
            'event_title' => 'required|min_len,2',
            'event_status' => 'required'
        );
        $filters = array(
            'event_type' => 'trim|sanitize_numbers',
            'event_title' => 'trim|sanitize_string',
            'event_time' => 'trim|sanitize_string',
            'street_address' => 'trim|sanitize_string',
            'latitude' => 'trim',
            'longitude' => 'trim',
            'comment' => 'trim|basic_tags',
            'event_id' => 'trim|sanitize_numbers',
            'members' => 'json_decode',
            'groups' => 'json_decode',
            'comment' => 'trim|sanitize_string',
            'event_title' => 'trim|sanitize_string',
            'event_status' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $editable = (isset($data['event_id']) && $data['event_id'] > 0) ? (API::is_event_editable($data['event_id'], $session)) : false;
            if ($editable === TRUE) {
                $event = ORM::for_table('events')->find_one($data['event_id']);
            } elseif ($editable === FALSE) {
                $event = ORM::for_table('events')->create();
            } else {
                $response['message'] = $editable;
                return json_encode($response, JSON_NUMERIC_CHECK);
                exit();
            }
            if (isset($data['event_id']) && $data['event_id'] > 0) {
                $pre_event = $event->as_array();
            }
            $event->event_creator = $session->user_id;
            $event->event_type = $data['event_type'];
            $event->event_title = $data['event_title'];
            $event->event_time = $data['event_time'];
            $event->street_address = $data['street_address'];
            $event->latitude = $data['latitude'];
            $event->longitude = $data['longitude'];
            $event->comment = $data['comment'];
            $data['members'] = (isset($data['members'])) ? $data['members'] : array();
            $data['groups'] = (isset($data['groups'])) ? $data['groups'] : array();
            if (empty($data['members']) && empty($data['groups'])) {
                $event->event_status = 'saved';
            } else {
                $event->event_status = $data['event_status'];
            }
            $event->timeZoneId = API::GetTimeZoneIDFromLatLong($event->latitude, $event->longitude);
            if ($editable === FALSE) {
                $event->created_at = date("Y-m-d H:i:s", time());
            }
            $event->updated_at = date("Y-m-d H:i:s", time());
            if ($event->save()) {
                if (isset($_FILES['photo'])) {
                    API::upload_event_image($event->event_id);
                }
                API::invite_to_event($event->event_id);
                if (isset($data['event_id']) && $data['event_id'] > 0) {
                    $pre_event_time = '';
                    if ((strtotime($pre_event['event_time']) - time()) / 24 / 60 / 60 < 1) {
                        $pre_event_time = "Today at " . date("H:i", strtotime($pre_event['event_time']));
                    } elseif ((strtotime($pre_event['event_time']) - time()) / 24 / 60 / 60 >= 1 && (strtotime($pre_event['event_time']) - time()) / 24 / 60 / 60 < 2) {
                        $pre_event_time = "Tomorrow at " . date("H:i", strtotime($pre_event['event_time']));
                    } elseif ((strtotime($pre_event['event_time']) - time()) / 24 / 60 / 60 <= 7) {
                        $pre_event_time = date("l M d, Y", strtotime($pre_event['event_time'])) . " at " . date("H:i", strtotime($pre_event['event_time']));
                    } else {
                        $pre_event_time = date("l M d, Y", strtotime($pre_event['event_time'])) . " at " . date("H:i", strtotime($pre_event['event_time']));
                    }
                    $message = sprintf($Lang['messages']['noti_event_modified'], API::get_username($event->event_creator), $pre_event_time, $pre_event['street_address']);
                    //$message = "The event previously scheduled on ".$pre_event->event_time." happening at ".$event->street_address." has been modified.";
                    API::send_event_notification(array('user_id' => $session->user_id, 'session_token' => $session->session_token, 'message' => $message, 'event_id' => $data['event_id'], 'action' => Config::read('EVENT_MODIFY')));
                }
                $output = array();
                $output['event_id'] = $event->event_id;
                $response['status'] = $Lang['messages']['success'];
                $response['message'] = $output;
            } else {
                $response['message'][] = $Lang['messages']['save_error'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::cancel_event()
     *
     * @return
     */
    public static function cancel_event() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'event_id' => 'required|integer|max_len,11',
        );
        $filters = array(
            'event_id' => 'trim|sanitize_numbers',
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $event = ORM::for_table('events')->where_equal('event_id', $data['event_id'])->find_one();
            if ($event) {
                if ($event->event_creator == $session->user_id) {
                    if ($event->delete()) {
                        $event_time = '';
                        if ((strtotime($event->event_time) - time()) / 24 / 60 / 60 < 1) {
                            $event_time = "Today at " . date("H:i", strtotime($event->event_time));
                        } elseif ((strtotime($event->event_time) - time()) / 24 / 60 / 60 >= 1 && (strtotime($event->event_time) - time()) / 24 / 60 / 60 < 2) {
                            $event_time = "Tomorrow at " . date("H:i", strtotime($event->event_time));
                        } elseif ((strtotime($event->event_time) - time()) / 24 / 60 / 60 <= 7) {
                            $event_time = date("l M d, Y", strtotime($event->event_time)) . " at " . date("H:i", strtotime($event->event_time));
                        } else {
                            $event_time = date("l M d, Y", strtotime($event->event_time)) . " at " . date("H:i", strtotime($event->event_time));
                        }
                        //$message = sprintf($Lang['messages']['noti_event_cancelled'],API::get_username($event->event_creator),$event_time,$event->street_address);
                        $message = sprintf($Lang['messages']['noti_event_cancelled'], API::get_username($event->event_creator));
                        API::send_event_notification(array('message' => $message, 'event_id' => $data['event_id'], 'action' => Config::read('EVENT_DELETE')));
                        //here was delete code
                        $response['status'] = $Lang['messages']['success'];
                        $response['message'] = $Lang['messages']['event_deleted'];
                    } else {
                        $response['message'][] = $Lang['messages']['event_not_deleted'];
                    }
                } else {
                    $response['message'][] = $Lang['messages']['unauth_event_edit'];
                }
            } else {
                $response['message'][] = $Lang['messages']['event_not_found'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::invite_to_event()
     *
     * @return
     */
    public static function invite_to_event($event_id) {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array();
        $filters = array(
            'members' => 'json_decode',
            'groups' => 'json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $count_invited = 0;
            $count_not_invited = 0;
            $old_invitation_array = $current_inviation_array = array();
            $old_invitation_array['group_member'] = $current_inviation_array['group_member'] = $old_invitation_array['lonly_member'] = $current_inviation_array['lonly_member'] = array();
            $valid_event = ORM::for_table('events')->where_equal('event_id', $event_id)->where_equal('event_creator', $session->user_id)->find_one(); //if only creator can invite*/
            if ($valid_event) {
                $old_invitations = ORM::for_table('event_invitations')->where_equal('event_id', $event_id)->find_array();
                if (!empty($old_invitations)) {
                    foreach ($old_invitations as $old_invitation) {
                        if (!empty($old_invitation['group_id'])) {
                            $old_invitation_array['group_member'][$old_invitation['user_id']] = $old_invitation['group_id'];
                        } else {
                            $old_invitation_array['lonly_member'][$old_invitation['user_id']] = $old_invitation['group_id'];
                        }
                    }
                }
                if (isset($data['members']) && !empty($data['members'])) {
                    foreach ($data['members'] as $user_id) {
                        if ($user_id != $session->user_id) {
                            $current_inviation_array['lonly_member'][$user_id] = NULL;
                        }
                    }
                }
                if (isset($data['groups']) && !empty($data['groups'])) {
                    $group_members = ORM::for_table('group_members')->where_in('group_id', $data['groups'])->find_array();
                    foreach ($group_members as $member) {
                        if (!array_key_exists($member['user_id'], $current_inviation_array)) {
                            $current_inviation_array['group_member'][$member['user_id']] = $member['group_id'];
                        }
                    }
                }
//                    print_r($old_invitation_array);
//                    print_r($current_inviation_array);
                $old_delete_lonly_member = array_diff_assoc($old_invitation_array['lonly_member'], $current_inviation_array['lonly_member']);
                $new_inserted_lonly_member = array_diff_assoc($current_inviation_array['lonly_member'], $old_invitation_array['lonly_member']);
                $old_delete_group_member = array_diff_assoc($old_invitation_array['group_member'], $current_inviation_array['group_member']);
                $new_inserted_group_member = array_diff_assoc($current_inviation_array['group_member'], $old_invitation_array['group_member']);
//                    print_r($old_delete_lonly_member);
//                    print_r($new_inserted_lonly_member);
//                    print_r($old_delete_group_member);
//                    print_r($new_inserted_group_member);
//                    exit;
                if (!empty($old_delete_lonly_member)) {
                    ORM::for_table('event_invitations')->where_equal('event_id', $event_id)->where_in('user_id', array_keys($old_delete_lonly_member))->where_null('group_id')->delete_many();
                }
                if (!empty($old_delete_group_member)) {
                    foreach ($old_delete_group_member as $member_id => $group_id) {
                        ORM::for_table('event_invitations')->where_equal('event_id', $event_id)->where_equal('user_id', $member_id)->where_equal('group_id', $group_id)->find_one()->delete();
                    }
                }
                if (!empty($new_inserted_lonly_member)) {
                    foreach ($new_inserted_lonly_member as $member_id => $group_id) {
                        $new_invitation = ORM::for_table('event_invitations')->create();
                        $new_invitation->event_id = $event_id;
                        $new_invitation->invited_by = $session->user_id;
                        $new_invitation->user_id = $member_id;
                        $new_invitation->created_at = date("Y-m-d H:i:s", time());
                        $new_invitation->invitation_status = Config::read('E_PENDING');
                        $new_invitation->save();
                    }
                }
                if (!empty($new_inserted_group_member)) {
                    foreach ($new_inserted_group_member as $member_id => $group_id) {
                        $new_invitation = ORM::for_table('event_invitations')->create();
                        $new_invitation->event_id = $event_id;
                        $new_invitation->invited_by = $session->user_id;
                        $new_invitation->user_id = $member_id;
                        $new_invitation->group_id = $group_id;
                        $new_invitation->created_at = date("Y-m-d H:i:s", time());
                        $new_invitation->invitation_status = Config::read('E_PENDING');
                        $new_invitation->save();
                    }
                }

//                    $notifiable_invitations = array();
//                    foreach ($data['members'] as $user_id) {
//                        if ($user_id == $session->user_id) {
//                            $response['message'][] = $Lang['messages']['self_invitation'];
//                            return json_encode($response, JSON_NUMERIC_CHECK);
//                            exit();
//                        }
////                        if (API::is_user_eligible_for_invitation($user_id)) {
//                        $existing_invitation = ORM::for_table('event_invitations')->where_equal('event_id', $event_id)->where_equal('user_id', $user_id)->count();
//                        if ($existing_invitation) {
//                            $count_not_invited++;
//                        } else {
//                            $new_invitation = ORM::for_table('event_invitations')->create();
//                            $new_invitation->event_id = $event_id;
//                            $new_invitation->user_id = $user_id;
//                            $new_invitation->arrival_time = "";
//                            $new_invitation->invited_by = $session->user_id;
//                            $new_invitation->created_at = date("Y-m-d H:i:s", time());
//                            $new_invitation->invitation_status = Config::read('E_PENDING');
//                            if ($new_invitation->save()) {
//                                $count_invited++;
//                                $notifiable_invitations[] = $new_invitation->invitation_id;
//                            }
//                        }
////                        } else {
////                            $count_not_invited++;
////                        }
//                        $response['status'] = 'success';
//                        $response['message'] = array('invited' => $count_invited, 'not_invited' => $count_not_invited);
//                    }
            } else {
                $response['message'][] = $Lang['messages']['unauth_event'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::get_event_list()
     *
     * @return
     */
    public static function get_event_list() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array();
        $filters = array(
            'event_flag' => 'trim|sanitize_string',
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $all_events = $received_events = $sent_events = $saved_events = array();
            $received_events = ORM::for_table('event_invitations')
                    ->table_alias('ei')
                    //->select_many('e.*','ei.*')
                    ->select_many('e.*', 'ei.invitation_id', 'ei.arrival_time', 'ei.invitation_status', 'ei.sent_status')
                    ->join('events', array('ei.event_id', '=', 'e.event_id'), 'e')
                    ->where_raw('(ei.user_id = ?)', array($session->user_id))
                    ->group_by_expr('ei.event_id,ei.user_id');
            if (isset($data['event_flag']) && $data['event_flag'] == 'past') {
                $received_events = $received_events->where_lt('e.event_time', date('Y:m:d H:i:s'))->order_by_desc('e.event_time');
            } else {
                $received_events = $received_events->where_gt('e.event_time', date('Y:m:d H:i:s'))->order_by_asc('e.event_time');
            }
            $received_events = $received_events->find_array();
//        print_r($received_events);exit; 

            foreach ($received_events as $key => $received_event) {
                if ($received_event['sent_status'] == Config::read('E_I_SENT')) {
                    $this_invitation = ORM::for_table('event_invitations')->find_one($received_event['invitation_id']);
                    $this_invitation->sent_status = Config::read('E_I_DELIVERED');
                    $this_invitation->save();
                }
                unset($received_events[$key]['invitation_id']);
                $received_events[$key]['event_creator_name'] = API::get_username($received_events[$key]['event_creator']);
                $received_events[$key]['sent_status'] = API::get_readable_invitation_sent_status($received_event['sent_status']);
                $received_events[$key]['separator'] = 'received';
            }
            $rec_sent_events = ORM::for_table('events')
                    ->where_equal('event_creator', $session->user_id);
            if (isset($data['event_flag']) && $data['event_flag'] == 'past') {
                $rec_sent_events = $rec_sent_events->where_lt('event_time', date('Y:m:d H:i:s'))->order_by_desc('event_time');
            } else {
                $rec_sent_events = $rec_sent_events->where_gt('event_time', date('Y:m:d H:i:s'))->order_by_asc('event_time');
            }
            $rec_sent_events = $rec_sent_events->find_array();
            foreach ($rec_sent_events as $key => $rec_sent_event) {
                $sent_invitations = ORM::for_table('event_invitations')
                        ->table_alias('ei')
                        ->select('ei.user_id')
                        ->select_expr('u.name', 'screen_name')
                        ->select_expr('g.name', 'group_name')
                        ->left_outer_join('users', array('u.user_id', '=', 'ei.user_id'), 'u')
                        ->left_outer_join('group', array('g.id', '=', 'ei.group_id'), 'g')
                        ->select_expr('count(ei.user_id)', 'total_members')
                        ->select_expr('count(ei.group_id)', 'total_groups')
                        ->where_equal('event_id', $rec_sent_event['event_id'])
                        ->find_array();
                if ($rec_sent_events[$key]['event_status'] == 'saved') {
                    $saved_events[$key] = $rec_sent_event;
                    $saved_events[$key]['total_groups'] = $sent_invitations[0]['total_groups'];
                    $saved_events[$key]['total_members'] = $sent_invitations[0]['total_members'];
                    unset($rec_sent_events[$key]['event_status']);
                    $saved_events[$key]['separator'] = 'saved';

                    if ($sent_invitations[0]['screen_name']) {
                        $msg = ($sent_invitations[0]['total_members'] - 1) ? ' +' . ($sent_invitations[0]['total_members'] - 1) : '';
                        $saved_events[$key]['more_invited'] = $sent_invitations[0]['screen_name'] . $msg;
                    } elseif ($sent_invitations[0]['group_name']) {
                        $msg = ($sent_invitations[0]['total_groups'] - 1) ? ' +' . ($sent_invitations[0]['total_groups'] - 1) : '';
                        $saved_events[$key]['more_invited'] = $sent_invitations[0]['group_name'] . $msg;
                    } else {
                        $saved_events[$key]['more_invited'] = 'nobody';
                    }
                } elseif (empty($sent_invitations[0]['total_groups']) && empty($sent_invitations[0]['total_members'])) {
                    $saved_events[$key] = $rec_sent_event;
                    unset($sent_invitations[$key]['event_status']);
                    $saved_events[$key]['separator'] = 'saved';
                    $saved_events[$key]['total_groups'] = $sent_invitations[0]['total_groups'];
                    $saved_events[$key]['total_members'] = $sent_invitations[0]['total_members'];

                    if ($sent_invitations[0]['screen_name']) {
                        $msg = ($sent_invitations[0]['total_members'] - 1) ? ' +' . ($sent_invitations[0]['total_members'] - 1) : '';
                        $saved_events[$key]['more_invited'] = $sent_invitations[0]['screen_name'] . $msg;
                    } elseif ($sent_invitations[0]['group_name']) {
                        $msg = ($sent_invitations[0]['total_groups'] - 1) ? ' +' . ($sent_invitations[0]['total_groups'] - 1) : '';
                        $saved_events[$key]['more_invited'] = $sent_invitations[0]['group_name'] . $msg;
                    } else {
                        $saved_events[$key]['more_invited'] = 'nobody';
                    }
                } else {
                    $sent_events[$key] = $rec_sent_event;
                    $sent_events[$key]['total_groups'] = $sent_invitations[0]['total_groups'];
                    $sent_events[$key]['total_members'] = $sent_invitations[0]['total_members'];
                    if ($sent_invitations[0]['screen_name']) {
                        $msg = ($sent_invitations[0]['total_members'] - 1) ? ' +' . ($sent_invitations[0]['total_members'] - 1) : '';
                        $sent_events[$key]['more_invited'] = $sent_invitations[0]['screen_name'] . $msg;
                    } elseif ($sent_invitations[0]['group_name']) {
                        $msg = ($sent_invitations[0]['total_groups'] - 1) ? ' +' . ($sent_invitations[0]['total_groups'] - 1) : '';
                        $sent_events[$key]['more_invited'] = $sent_invitations[0]['group_name'] . $msg;
                    } else {
                        $sent_events[$key]['more_invited'] = 'nobody';
                    }
                    unset($sent_invitations[$key]['event_status']);
                    $sent_events[$key]['separator'] = 'sent';
                }
            }
            $sent_events = array_values($sent_events);
            $saved_events = array_values($saved_events);
            //group by event type
            //usort($received_events, Helper::make_comparer(array('event_type', SORT_ASC), array('updated_at', SORT_DESC)));
            //usort($rec_sent_events, Helper::make_comparer(array('event_type', SORT_ASC), array('updated_at', SORT_DESC)));
            //sort in order: latest updated first, then the rest sorted by event time in descending order
            $latest_sent_event = $latest_received_event = array();
            $received_events_sorted = $received_events;
            $sent_events_sorted = $rec_sent_events;

//        if (count($received_events) > 0) {
//            $latest_received_event = $received_events[0];
//            unset($received_events[0]);
//            usort($received_events, Helper::make_comparer(array('event_time', SORT_ASC)));
//            $received_events_sorted[0] = $latest_received_event;
//            for ($i = 0; $i < count($received_events); $i++) {
//                $received_events_sorted[($i + 1)] = $received_events[$i];
//            }
//        }
//        if (count($sent_events) > 0) {
//            $latest_sent_event = $sent_events[0];
//            unset($sent_events[0]);
//            usort($sent_events, Helper::make_comparer(array('event_time', SORT_ASC)));
//            $sent_events_sorted[0] = $latest_sent_event;
//            for ($i = 0; $i < count($sent_events); $i++) {
//                $sent_events_sorted[($i + 1)] = $sent_events[$i];
//            }
//        }
            //sorting logic end        
//        print_r($sent_events);
//        print_r($received_events);
//        print_r($saved_events);
//        exit;            
            $all_events = array_merge($sent_events, $received_events, $saved_events);
            if (!empty($all_events)) {
                usort($all_events, Helper::make_comparer(array('event_time', SORT_ASC)));
            }
//        if (count($all_events) > 0) {
//                $latest_received_event = $received_events[0];
//                unset($received_events[0]);
//                usort($received_events, Helper::make_comparer(array('event_time', SORT_ASC)));
//                $received_events_sorted[0] = $latest_received_event;
//                for ($i = 0; $i < count($received_events); $i++) {
//                    $received_events_sorted[($i + 1)] = $received_events[$i];
//                }
//            }
            $all_events = array('sent' => $sent_events, 'received' => $received_events, 'saved' => $saved_events, 'all' => $all_events);
//        print_r($all_events);
//        exit;
//        $all_events = array('sent' => $sent_events_sorted, 'received' => $received_events_sorted, 'saved' => $saved_events);
            $response['status'] = 'success';
            $response['message'] = $all_events;
            return json_encode($response, JSON_NUMERIC_CHECK);
        } else {
            $response['message'] = $validator->get_readable_errors();
            return json_encode($response, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * API::get_invited_events()
     *
     * @return
     */
    public static function get_invited_events() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'invitation_status' => 'integer|exact_len,1',
            'group' => 'alpha'
        );
        $filters = array(
            'invitation_status' => 'trim|sanitize_numbers',
            'group' => 'trim'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $invited_events = array();
            if (isset($data['invitation_status']) && $data['invitation_status'] > 0 && $data['invitation_status'] < 4) {
                $invited_events = ORM::for_table('event_invitations')
                        ->table_alias('ei')
                        ->select('e.*')
                        ->select_many(array('invitation_status' => 'ei.invitation_status', 'arrival_time' => 'ei.arrival_time'))
                        //->select('ei.invitation_status', 'invitation_status')
                        ->join('events', array('ei.event_id', '=', 'e.event_id'), 'e')
                        ->where_raw('(ei.user_id = ?)', array($session->user_id))
                        ->where_equal('invitation_status', $data['invitation_status'])
                        ->order_by_desc('e.updated_at')
                        ->find_array();
            } else {
                $invited_events = ORM::for_table('event_invitations')
                        ->table_alias('ei')
                        ->select('e.*')
                        ->select_many(array('invitation_status' => 'ei.invitation_status', 'arrival_time' => 'ei.arrival_time'))
                        //->select('ei.invitation_status', 'invitation_status')
                        ->join('events', array('ei.event_id', '=', 'e.event_id'), 'e')
                        ->where_raw('(ei.user_id = ?)', array($session->user_id))
                        ->order_by_desc('e.updated_at')
                        ->find_array();
            }
            if (isset($data['group']) && strtoupper($data['group']) == 'YES') {
                $grouped_events = array();
                foreach ($invited_events as $event) {
                    switch ($event['invitation_status']) {
                        case Config::read('E_PENDING'):
                            $grouped_events['pending'][] = $event;
                            break;
                        case Config::read('E_JOINED'):
                            $grouped_events['joined'][] = $event;
                            break;
                        case Config::read('E_MAYBE'):
                            $grouped_events['maybe'][] = $event;
                            break;
                        case Config::read('E_DECLINED'):
                            $grouped_events['declined'][] = $event; //(array_keys($event),array_values($event));
                            break;
                    }
                }
                $invited_events = $grouped_events;
            }
            $response['status'] = $Lang['messages']['success'];
            $response['message'] = $invited_events;
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::get_created_events()
     *
     * @return
     */
    public static function get_created_events() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $created_events = ORM::for_table('events')->where_equal('event_creator', $session->user_id)->order_by_desc('updated_at')->find_array();
        if ($created_events) {
            $response['status'] = $Lang['messages']['success'];
            $response['message'] = $created_events;
        } else {
            $response['message'][] = $Lang['messages']['event_not_found'];
        }
        return json_encode($response);
    }

    /**
     * API::get_event_details()
     *
     * @return
     */
    public static function get_event_details() {
        global $Lang;
        $event = array();
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'event_id' => 'required|integer|max_len,11'
        );
        $filters = array(
            'event_id' => 'trim|sanitize_numbers'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $event = ORM::for_table('events')->find_one($data['event_id']);
            if ($event) {
                $event = $event->as_array();
                if ($event['event_creator'] == $session->user_id) {
                    $event['self_event'] = (int) 1;
                } else {
                    $event['self_event'] = (int) 0;
                }
                $event['event_id'] = (int) $event['event_id'];
                $event['event_creator'] = (int) $event['event_creator'];
                $event['event_type'] = (int) $event['event_type'];
                $event['latitude'] = (float) $event['latitude'];
                $event['longitude'] = (float) $event['longitude'];
                $event['photo'] = (!empty($event['photo'])) ? Config::read('BASE_URL') . '/event_images/' . $event['photo'] : Config::read('BASE_URL') . '/event_images/default.png';

                $invitation_status = Config::read('E_NOT_INVITED');
                $invitation = ORM::for_table('event_invitations')
                        ->select_many('invitation_id', 'sent_status', 'invitation_status')
                        ->where_equal('event_id', $event['event_id'])
                        ->where_equal('invited_by', $event['event_creator'])
                        ->where_equal('user_id', $session->user_id)
                        ->find_one();
                if ($invitation !== false) {
                    if ($invitation->sent_status != Config::read('E_I_VIEWED')) {
                        $invitation->sent_status = Config::read('E_I_VIEWED');
                        $invitation->save();
                    }
                    $invitation_status = array_pop($invitation->as_array());
                }
                //$invitation_status = API::get_readable_invitation_status($invitation_status);
                $event['current_user_invitation_status'] = API::get_readable_invitation_status($invitation_status);
                $event_creator = ORM::for_table('users')->select_many('name', 'phone_number')->find_one($event['event_creator']);
                $event['event_creator_name'] = $event_creator->name; //array_pop(ORM::for_table('users')->select('name')->find_one($event['event_creator'])->as_array());
                $event['event_creator_phone_number'] = $event_creator->phone_number;
                $joinee = $invitees = array();
                $invitees = ORM::for_table('event_invitations')
                        ->table_alias('ei')
                        ->select_many('u.user_id', 'u.avatar', 'ei.arrival_time', 'ei.invitation_status', 'ei.sent_status')
                        ->select_expr('u.name', 'screen_name')
                        ->select_expr('g.id', 'group_id')
                        ->select_expr('g.name', 'group_name')
                        ->left_outer_join('users', array('ei.user_id', '=', 'u.user_id'), 'u')
                        ->left_outer_join('group', array('ei.group_id', '=', 'g.id'), 'g')
                        ->where_not_in('ei.user_id', array($session->user_id))
                        ->where_equal('event_id', $data['event_id'])
                        ->order_by_asc('ei.user_id')
                        ->find_array();
                $mem_incr = 0;
                if (!empty($invitees)) {
                    foreach ($invitees as $key => $invitee) {
                        if (empty($invitee['group_id'])) {
                            $joinee['members'][$mem_incr]['avatar'] = ($invitee['avatar'] != '') ? Config::read('BASE_URL') . '/avatar/' . $invitee['avatar'] : Config::read('BASE_URL') . '/avatar/default.png';
                            $joinee['members'][$mem_incr]['user_id'] = (int) $invitee['user_id'];
                            $joinee['members'][$mem_incr]['name'] = $invitee['screen_name'];
                            $joinee['members'][$mem_incr]['invitation_status'] = API::get_readable_invitation_status($invitee['invitation_status']);
                            $joinee['members'][$mem_incr]['sent_status'] = $sent_status = API::get_readable_invitation_sent_status($invitee['sent_status']);
                            $mem_incr++;
                        } else {
                            $group_id = (int) $invitee['group_id'];
                            $joinee['groups'][$group_id]['group_id'] = (int) $invitee['group_id'];
                            $joinee['groups'][$group_id]['group_name'] = $invitee['group_name'];
                        }
                        $joinee['arrival_time'] = $invitee['arrival_time'];
                        $joinee['arrival_time'] = $invitee['arrival_time'];
                        $joinee['arrival_time'] = $invitee['arrival_time'];
                    }
                }
                if (isset($joinee['groups']) && !empty($joinee['groups'])) {
                    $joinee['groups'] = array_values($joinee['groups']);
                }

                $event['invitees'] = $joinee;
                $response['status'] = $Lang['messages']['success'];
                $response['message'] = $event;
            } else {
                $response['message'][] = $Lang['messages']['event_not_found'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        //return json_encode($response, JSON_NUMERIC_CHECK);
        return json_encode($response);
    }

    /**
     * API::respond_to_event()
     *
     * @return
     */
    public static function respond_to_event() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'event_id' => 'required|integer|max_len,11',
            'answer' => 'required|alpha|max_len,8'
        );
        $filters = array(
            'event_id' => 'trim|sanitize_numbers',
            'answer' => 'trim|sanitize_string',
            'arrival_time' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $event = ORM::for_table('event_invitations')
                    ->where_equal('event_id', $data['event_id'])
                    ->where_equal('user_id', $session->user_id)
                    ->find_one();
            if ($event) {
                $correct_answer = true;

                switch (strtoupper($data['answer'])) {
                    case 'YES':
                        $event_details = ORM::for_table('events')->select_many('event_time', 'timeZoneId')->find_one($data['event_id']);

                        //set the timezone of the event as default timezone
                        if ($event_details->timeZoneId != "")
                            date_default_timezone_set($event_details->timeZoneId);

                        if (strtotime($event_details->event_time) < time()) {
                            $correct_answer = false;
                            $response['message'] = $Lang['messages']['response_old_event'];
                        } else {
                            if (!isset($data['arrival_time']) || $data['arrival_time'] == "") {
                                $response['message'][] = $Lang['messages']['arrival_unspecified'];
                            } else {
                                $event->invitation_status = Config::read('E_JOINED');
                                $event->arrival_time = isset($data['arrival_time']) ? $data['arrival_time'] : NULL;
                                $response['message'] = $Lang['messages']['join_event_success'];
                            }
                            $message = sprintf($Lang['messages']['noti_event_accepted'], API::get_username($event->user_id));
                            API::send_event_notification(array('message' => $message, 'action' => Config::read('EVENT_RESPONSE_YES'), 'event_id' => $data['event_id']));
                        }
                        break;
                    case 'NO':
                        $event_details = ORM::for_table('events')->select_many('event_time', 'timeZoneId')->find_one($data['event_id']);
                        if ($event_details->timeZoneId != "")
                            date_default_timezone_set($event_details->timeZoneId);
                        if (strtotime($event_details->event_time) < time()) {
                            $correct_answer = false;
                            $response['message'] = $Lang['messages']['response_old_event'];
                        } else {
                            $event->invitation_status = Config::read('E_DECLINED');
                            $response['message'] = $Lang['messages']['decline_event_success'];
                            $message = sprintf($Lang['messages']['noti_event_rejected'], API::get_username($event->user_id));
                            API::send_event_notification(array('message' => $message, 'action' => Config::read('EVENT_RESPONSE_NO'), 'event_id' => $data['event_id']));
                        }
                        break;
                    case 'MAYBE':
                        $event_details = ORM::for_table('events')->select_many('event_time', 'timeZoneId')->find_one($data['event_id']);
                        if ($event_details->timeZoneId != "")
                            date_default_timezone_set($event_details->timeZoneId);
                        if (strtotime($event_details->event_time) < time()) {
                            $correct_answer = false;
                            $response['message'] = $Lang['messages']['response_old_event'];
                        } else {
                            $event->invitation_status = Config::read('E_MAYBE');
                            $response['message'] = $Lang['messages']['maybe_event_success'];
                            $message = sprintf($Lang['messages']['noti_event_maybe'], API::get_username($event->user_id));
                            API::send_event_notification(array('message' => $message, 'action' => Config::read('EVENT_RESPONSE_MAYBE'), 'event_id' => $data['event_id']));
                        }
                        break;
                    case 'REMOVE':
                        $event->delete();
                        $response['message'] = $Lang['messages']['invitation_removed'];
                        break;
                    default:
                        $correct_answer = false;
                        $response['message'] = $Lang['messages']['invitation_status_wrong_answer'];
                        break;
                }
                if ($correct_answer) {
                    if ($event->save()) {
                        $response['status'] = 'success';
                    } else {
                        $response['message'][] = $Lang['messages']['invitation_status_change_error'];
                    }
                }
            } else {
                $response['message'][] = $Lang['messages']['unauth_or_self_event'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::make_friend_request()
     *
     * @return
     */
    public static function make_friend_request() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'receiver_email' => 'required|valid_email'
        );
        $filters = array(
            'receiver_email' => 'trim|sanitize_email'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $existing_user = ORM::for_table('users')->where_equal('email', $data['receiver_email'])->find_one();
            if ($existing_user) {
                $user_id1 = $session->user_id;
                $user_id2 = $existing_user->user_id;
                if ($user_id1 == $user_id2) {
                    $response['message'][] = $Lang['messages']['request_to_self'];
                } else {
                    $existing_request = ORM::for_table('friend_requests')->where_raw('( sender_id = ? AND receiver_id = ? ) OR ( sender_id = ? AND receiver_id = ? )', array($user_id1, $user_id2, $user_id2, $user_id1))->find_one();
                    if ($existing_request) {
                        switch ($existing_request['status']) {
                            case Config::read('F_PENDING'):
                                $response['message'][] = $Lang['messages']['freq_pending'];
                                break;
                            case Config::read('F_ACCEPTED'):
                                $response['message'] = $Lang['messages']['freq_already_accepted'];
                                break;
                            case Config::read('F_DENIED'):
                                $response['message'][] = $Lang['messages']['freq_denied'];
                                break;
                            default:
                                $response['message'][] = $Lang['messages']['freq_mismatch'];
                                break;
                        }
                    } else {
                        $friend_request = ORM::for_table('friend_requests')->create();
                        $friend_request->sender_id = $user_id1;
                        $friend_request->receiver_id = $user_id2;
                        $friend_request->status = Config::read('F_PENDING');
                        if ($friend_request->save()) {
                            $response['status'] = $Lang['messages']['success'];
                            $response['message'] = $Lang['messages']['freq_sent_success'];
                        } else {
                            $response['message'][] = $Lang['messages']['saving_failed'];
                        }
                    }
                }
            } else {
                $response['message'][] = $Lang['messages']['user_not_found'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::make_friend_request_to_many()
     *
     * @return
     */
    public static function make_friend_request_to_many() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'users' => 'required'
        );
        $filters = array(
            'users' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $result = array('sent' => array(), 'not_sent' => array());
            $counter = array('sent' => 0, 'not_sent' => 0);
            foreach ($data['users'] as $user) {
                $existing_user = ORM::for_table('users')->find_one($user);
                if ($existing_user) {
                    $user_id1 = $session->user_id;
                    $user_id2 = $existing_user->user_id;
                    if ($user_id1 == $user_id2) {
                        $result['not_sent'][] = $user;
                        $counter['not_sent'] ++;
                    } else {
                        $existing_request = ORM::for_table('friend_requests')->where_raw('( sender_id = ? AND receiver_id = ? ) OR ( sender_id = ? AND receiver_id = ? )', array($user_id1, $user_id2, $user_id2, $user_id1))->find_one();
                        if ($existing_request) {
                            $result['not_sent'][] = $user;
                            $counter['not_sent'] ++;
                        } else {
                            $friend_request = ORM::for_table('friend_requests')->create();
                            $friend_request->sender_id = $user_id1;
                            $friend_request->receiver_id = $user_id2;
                            $friend_request->status = Config::read('F_PENDING');
                            if ($friend_request->save()) {
                                $result['sent'][] = $user;
                                $counter['sent'] ++;
                            } else {
                                $result['not_sent'][] = $user;
                                $counter['not_sent'] ++;
                            }
                        }
                    }
                } else {
                    $result['not_sent'][] = $user;
                    $counter['not_sent'] ++;
                }
            }
            $response['status'] = $Lang['messages']['success'];
            $response['message'] = array('counter' => $counter, 'user_id_list' => $result);
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::accept_friend_request()
     *
     * @return
     */
    public static function accept_friend_request() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'sender_id' => 'required|integer|max_len,11'
        );
        $filters = array(
            'sender_id' => 'trim|sanitize_numbers'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $sender_id = $data['sender_id'];
            $receiver_id = $session->user_id;
            $friend_request = ORM::for_table('friend_requests')
                    ->where_raw('( sender_id = ? AND receiver_id = ? )', array($sender_id, $receiver_id))
                    ->find_one();
            if ($friend_request) {
                if ($friend_request['status'] == Config::read('F_PENDING')) {
                    $friend_request->status = Config::read('F_ACCEPTED');
                    if ($friend_request->save()) {
                        $response['status'] = $Lang['messages']['success'];
                        $response['message'] = $Lang['messages']['freq_accepted'];
                    } else {
                        $response['message'][] = $Lang['messages']['saving_failed'];
                    }
                } elseif ($friend_request['status'] == Config::read('F_ACCEPTED')) {
                    $response['message'][] = $Lang['messages']['freq_already_accepted'];
                }
            } else {
                $response['message'][] = $Lang['messages']['freq_not_found'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::accept_friend_request_array()
     *
     * @return
     */
    public static function accept_friend_request_array() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'accept_list' => 'required'
        );
        $filters = array(
            'accept_list' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        $counter = array('accepted' => 0, 'not_accepted' => 0);
        if ($validated === TRUE) {
            //die(implode(',',$data['accept_list']));
            if (!empty($data['accept_list'])) {
                $receiver_id = $session->user_id;
                $friend_requests = ORM::for_table('friend_requests')->where_in('sender_id', $data['accept_list'])->where_equal('receiver_id', $receiver_id)->find_many(); // where_raw('( sender_id IN ( ? ) AND receiver_id = ? )', array(implode(',',$data['accept_list']),$receiver_id))->find_many();
                if ($friend_requests) {
                    foreach ($friend_requests as $friend_request) {
                        if ($friend_request->status == Config::read('F_PENDING')) {
                            $friend_request->status = Config::read('F_ACCEPTED');
                            if ($friend_request->save()) {
                                $counter['accepted'] ++;
                            } else {
                                $counter['not_accepted'] ++;
                            }
                        } elseif ($friend_request->status == Config::read('F_ACCEPTED')) {
                            $counter['not_accepted'] ++;
                        }
                    }
                    $response['status'] = $Lang['messages']['success'];
                    $response['message'] = $counter;
                } else {
                    $response['message'][] = $Lang['messages']['freq_not_found'];
                }
            } else {
                $response['message'][] = $Lang['messages']['param_empty'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::deny_friend_request()
     *
     * @return
     */
    public static function deny_friend_request() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'sender_id' => 'required|integer|max_len,11'
        );
        $filters = array(
            'sender_id' => 'trim|sanitize_numbers'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            if (count($data['sender_id']) > 0) {
                $friend_request = ORM::for_table('friend_requests')->where_equal('receiver_id', $session->user_id)->where_equal('sender_id', $data['sender_id'])->where('status', Config::read('F_PENDING'))->find_one();
                if ($friend_request) {
                    $friend_request->delete();
                    $response['status'] = $Lang['messages']['success'];
                    $response['message'] = $Lang['messages']['freq_denied'];
                } else {
                    $response['message'] = $Lang['messages']['freq_not_found'];
                }
            } else {
                $response['message'][] = $Lang['messages']['param_empty'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::deny_friend_request_array()
     *
     * @return
     */
    public static function deny_friend_request_array() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'deny_list' => 'required'
        );
        $filters = array(
            'deny_list' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            if (count($data['deny_list']) > 0) {
                $friend_requests_count = ORM::for_table('friend_requests')->where_equal('receiver_id', $session->user_id)->where_in('sender_id', $data['deny_list'])->where('status', Config::read('F_PENDING'))->count();
                if ($friend_requests_count > 0) {
                    $deleted = ORM::for_table('friend_requests')->where_equal('receiver_id', $session->user_id)->where_in('sender_id', $data['deny_list'])->where('status', Config::read('F_PENDING'))->delete_many();
                    if ($deleted) {
                        $response['status'] = $Lang['messages']['success'];
                        $response['message'] = $Lang['messages']['freq_denied'];
                    } else {
                        $response['message'] = $Lang['messages']['delete_error'];
                    }
                } else {
                    $response['message'] = $Lang['messages']['freq_not_found'];
                }
            } else {
                $response['message'][] = $Lang['messages']['param_empty'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::get_friend_list()
     *
     * @return
     */
    public static function get_friend_list() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'fr_status' => 'alpha'
        );
        $filters = array(
            'fr_status' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $friends = array();
            $friend_list = array();
            //"(case when ".Config::read('F_PENDING')." in status then 1 else 0 end) as sent";
            $sql = "( SELECT receiver_id AS user_id, status AS friendship_status, 1 AS request_sent
                        FROM friend_requests
                        WHERE sender_id = :user_id
                            AND receiver_id != :user_id
                            %s
                    ) UNION
                    ( SELECT sender_id AS user_id, status AS friendship_status, 0 AS request_sent
                        FROM friend_requests
                        WHERE sender_id != :user_id
                        AND receiver_id = :user_id
                        %s
                    )
                    ORDER BY user_id ASC";
            if (isset($data['fr_status'])) {
                $query = sprintf($sql, 'AND status = :fr_status', 'AND status = :fr_status');
                switch (strtoupper($data['fr_status'])) {
                    case 'ACCEPTED':
                        $friends = ORM::for_table('friend_requests')
                                ->raw_query($query, array('user_id' => $session->user_id, 'fr_status' => Config::read('F_ACCEPTED')))
                                ->find_array();
                        break;
                    case 'PENDING':
                        $friends = ORM::for_table('friend_requests')
                                ->raw_query($query, array('user_id' => $session->user_id, 'fr_status' => Config::read('F_PENDING')))
                                ->find_array();
                        break;
                    default:
                        $query = sprintf($sql, '', '');
                        $friends = ORM::for_table('friend_requests')
                                ->raw_query($query, array('user_id' => $session->user_id))
                                ->find_array();
                        break;
                }
            } else {
                $query = sprintf($sql, '', '');
                $friends = ORM::for_table('friend_requests')->raw_query($query, array('user_id' => $session->user_id))->find_array();
            }
            if (!empty($friends)) {
                $users = Helper::array_value_recursive('user_id', $friends);
                $friend_list = ORM::for_table('users')->select_many('user_id', array('name', 'email', 'avatar'))->where_in('user_id', $users)->order_by_asc('user_id')->find_array();
                foreach ($friends as $key => $friend) {
                    $friend['friendship_status'] = API::get_readable_friendship_request_status($friend['friendship_status']);
                    $friend_list[$key]['avatar'] = ($friend_list[$key]['avatar'] != '') ? Config::read('BASE_URL') . '/avatar/' . $friend_list[$key]['avatar'] : Config::read('BASE_URL') . '/avatar/default.jpg';
                    $friend_list[$key] = array_merge($friend_list[$key], $friend);
                }
            }
            usort($friend_list, Helper::make_comparer(array('request_sent', SORT_ASC), array('friendship_status', SORT_DESC)));
            $response['status'] = $Lang['messages']['success'];
            $response['message'] = $friend_list;
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::remove_sent_request()
     *
     * @return
     */
    public static function remove_sent_requests() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'receiver_list' => 'required'
        );
        $filters = array(
            'receiver_list' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $friend_requests_count = ORM::for_table('friend_requests')->where_equal('sender_id', $session->user_id)->where_in('receiver_id', $data['receiver_list'])->where_equal('status', Config::read('F_PENDING'))->count();
            if ($friend_requests_count > 0) {
                $deleted = ORM::for_table('friend_requests')->where_equal('sender_id', $session->user_id)->where_in('receiver_id', $data['receiver_list'])->where_equal('status', Config::read('F_PENDING'))->delete_many();
                if ($deleted) {
                    $response['status'] = $Lang['messages']['success'];
                    $response['message'] = $Lang['messages']['freq_removed'];
                } else {
                    $response['message'] = $Lang['messages']['delete_error'];
                }
            } else {
                $response['message'] = $Lang['messages']['freq_not_found'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::find_friends()
     *
     * @return
     */
    public static function find_friends() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'email' => 'max_len,255|min_len,1',
            'phone_number' => 'max_len,15|min_len,1',
            'name' => 'max_len,255|min_len,1'
        );
        $filters = array(
            'email' => 'trim|sanitize_string',
            'phone_number' => 'trim|sanitize_string',
            'name' => 'trim|sanitize_string',
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $data = array_intersect_key($data, array('name' => '', 'email' => '', 'phone_number' => ''));
            $existing_friends = array();
            $sql = "( SELECT receiver_id AS user_id
                        FROM friend_requests
                        WHERE sender_id = :user_id
                            AND receiver_id != :user_id
                    ) UNION
                    ( SELECT sender_id AS user_id
                        FROM friend_requests
                        WHERE sender_id != :user_id
                        AND receiver_id = :user_id
                    )
                    ORDER BY user_id ASC";
            $existing_friends = ORM::for_table('friend_requests')->raw_query($sql, array('user_id' => $session->user_id))->find_array();
            if (empty($existing_friends)) {
                $existing_friends[] = $session->user_id;
            } else {
                $existing_friends = Helper::array_value_recursive('user_id', $existing_friends);
                $existing_friends[] = $session->user_id;
            }
            foreach ($data as $key => $value) {
                if ($key === 'phone_number') {
                    $key = 'phone_number_tr';
                    $value = substr($value, -8);
                }
                $like_conditions[] = $key . " LIKE '" . $value . "%' ";
            }
            $where_clause = implode(' OR ', $like_conditions);
            $found_friends = array();
            $found_friends = ORM::for_table('users')->select_many('user_id', 'name', 'email', 'phone_number', 'avatar')->where_raw($where_clause)->where_not_in('user_id', $existing_friends)->find_array();
            foreach ($found_friends as $key => $friend) {
                $friend['user_id'] = (int) $friend['user_id'];
                $friend['avatar'] = ($friend['avatar'] != '') ? Config::read('BASE_URL') . '/avatar/' . $friend['avatar'] : Config::read('BASE_URL') . '/avatar/default.jpg';
                $found_friends[$key] = $friend;
            }
            $response['status'] = $Lang['messages']['success'];
            $response['message'] = $found_friends;
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response);
    }

    /**
     * API::remove_friends()
     *
     * @return
     */
    public static function remove_friends() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'friends' => 'required'
        );
        $filters = array(
            'friends' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $counter = array('removed' => 0, 'not_removed' => 0);
            $existing_friends = ORM::for_table('friend_requests')->where_equal('sender_id', $session->user_id)->where_in('receiver_id', $data['friends'])->delete_many();
            $statement = ORM::get_last_statement();
            $counter['removed'] += (int) $statement->rowCount();
            $existing_friends = ORM::for_table('friend_requests')->where_equal('receiver_id', $session->user_id)->where_in('sender_id', $data['friends'])->delete_many();
            $statement = ORM::get_last_statement();
            $counter['removed'] += (int) $statement->rowCount();
            $counter['not_removed'] = count($data['friends']) - $counter['removed'];
            $response['status'] = $Lang['messages']['success'];
            $response['message'] = $counter;
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response);
    }

    /**
     * API::get_current_user()
     *
     * @return
     */
    public static function get_current_user() {
        global $Lang;
        $session = Session::authenticate();
        $current_user = ORM::for_table('users')->select_many('user_id', 'name', 'phone_number', 'avatar')->find_one($session->user_id)->as_array();
        $current_user['user_id'] = (int) $current_user['user_id'];
        $current_user['avatar'] = ($current_user['avatar'] != '') ? Config::read('BASE_URL') . '/avatar/' . $current_user['avatar'] : Config::read('BASE_URL') . '/avatar/default.jpg';
        $response['status'] = $Lang['messages']['success'];
        $response['message'] = $current_user;
        return json_encode($response);
    }

    /**
     * API::edit_current_user()
     *
     * @return
     */
    public function edit_current_user() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'name' => 'required|max_len,100|min_len,2',
            'phone_number' => 'required|max_len,15|min_len,4',
            'phone_number_tr' => 'required|max_len,8|min_len,4'
        );
        $filters = array(
            'name' => 'trim|sanitize_string',
            'phone_number' => 'trim|sanitize_string',
            'phone_number_tr' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $current_user = ORM::for_table('users')->select_many('user_id', 'name', 'phone_number')->find_one($session->user_id);
            $current_user->name = $data['name'];
            $current_user->phone_number = $data['phone_number'];
            $current_user->phone_number_tr = $data['phone_number_tr'];
            if ($current_user->save()) {
                if (isset($_FILES['photo'])) {
                    API::upload_image();
                }
                $current_user = $current_user->as_array();
                $current_user['user_id'] = (int) $current_user['user_id'];
                $response['status'] = $Lang['messages']['success'];
                $response['message'] = $Lang['messages']['edit_cur_user_success'];
            } else {
                $response['message'][] = $Lang['messages']['save_error'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response);
    }

    /**
     * API::get_pending_friend_requests()
     *
     * @return
     */
    public static function get_pending_friend_requests() {
        
    }

    /**
     * API::push_register()
     *
     * @return
     */
    public static function push_register() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'push_token' => 'required',
            'client_type' => 'required|alpha'
        );
        $filters = array(
            'push_token' => 'trim|sanitize_string',
            'client_type' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            ORM::for_table('device_users')->where_equal('user_id', $session['user_id'])->delete_many();
            $push = ORM::for_table('device_users')->create();
            $push->user_id = $session['user_id'];
            $push->session_token = $session['session_token'];
            $push->push_token = $data['push_token'];
            $push->client_type = strtoupper($data['client_type']);
            if ($push->save()) {
                $response['status'] = $Lang['messages']['success'];
                $response['message'][] = $Lang['messages']['push_reg_success'];
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response);
    }

    /**
     * API::send_event_notification()
     *
     * @return
     */
    public static function send_event_notification($params = false) {
        global $Lang;
        $pass_params = $params ? $params : $_REQUEST;
        if (!$params) {
            $session = Session::authenticate();
        }
        require_once(Config::read('BASE_PATH') . '/includes/device_notification.php');
        $response = array(
            'status' => $Lang['messages']['failure']
        );
        $rules = array(
            'message' => 'required',
            'event_id' => 'required|integer'
        );
        $filters = array(
            'message' => 'trim|sanitize_string',
            'event_id' => 'trim|sanitize_numbers'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($pass_params);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $users = array();
            if (isset($data['action']) && strtolower($data['action']) == Config::read('EVENT_DELETE')) {
                $users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->find_array(); //->where_in('invitation_status',array(Config::read('E_JOINED'),Config::read('E_MAYBE')))
                $users = Helper::array_value_recursive('user_id', $users);
                $event_invitations = ORM::for_table('event_invitations')->where_equal('event_id', $data['event_id'])->delete_many();
            } elseif (isset($data['action']) && strtolower($data['action']) == Config::read('EVENT_INVITE')) {
                $users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->where_in('invitation_id', $data['invitations'])->find_array();
                $users = Helper::array_value_recursive('user_id', $users);
                //$users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->where_in('invitation_status',array(Config::read('E_JOINED'),Config::read('E_MAYBE')))->where_not_equal('invitation_notification_sent',1)->find_array();
            } elseif (isset($data['action']) && strtolower($data['action']) == Config::read('EVENT_MODIFY')) {
                $users = ORM::for_table('event_invitations')->select('user_id')->distinct()->where_equal('event_id', $data['event_id'])->where_in('invitation_status', array(Config::read('E_PENDING'), Config::read('E_JOINED'), Config::read('E_MAYBE')))->find_array();
                $users = Helper::array_value_recursive('user_id', $users);
            } elseif (isset($data['action']) && strtolower($data['action']) == Config::read('EVENT_RESPONSE_YES') || strtolower($data['action']) == Config::read('EVENT_RESPONSE_NO') || strtolower($data['action']) == Config::read('EVENT_RESPONSE_MAYBE')) {
                $users = ORM::for_table('events')->select('event_creator')->where_equal('event_id', $data['event_id'])->find_array();
                $users = Helper::array_value_recursive('event_creator', $users);
            }
            //$users = Helper::array_value_recursive('user_id',$users);
            if (!empty($users)) {
                $devices = ORM::for_table('users')->where_in('user_id', $users)->find_array();
                if (!empty($devices)) {
                    $ios_devices = array_filter($devices, function($v) {
                        return strcasecmp(strtoupper($v['client_type']), "IPHONE") == 0;
                    });
                    $android_devices = array_filter($devices, function($v) {
                        return strcasecmp(strtoupper($v['client_type']), "ANDROID") == 0;
                    });
                    if (!empty($ios_devices)) {
                        $chunk_ios_devices = array_chunk($ios_devices, 25);
                        foreach ($chunk_ios_devices as $chunk_ios_devices) {
                            $device_tokens = Helper::array_value_recursive('push_token', $chunk_ios_devices);
                            $result = sendMessageToIPhone($device_tokens, $data['message'], $data['action']);
                        }
                        $response['status'] = $Lang['messages']['success'];
                        $response['message'] = $Lang['messages']['noti_send_success'];
                    }
                    if (!empty($android_devices)) {
                        $result = array();
                        $chunk_android_devices = array_chunk($android_devices, 25);
                        foreach ($chunk_android_devices as $chunk_android_device) {
                            $message = new stdClass();
                            $message->aps = new stdClass();
                            $message->aps->alert = $data['message'];
                            $message->aps->sound = "default";
                            $message->aps->t = $data['action'];
                            $message = json_encode($message);
                            $device_tokens = Helper::array_value_recursive('push_token', $chunk_android_device);
                            $result = sendMessageToAndroidPhone(Config::read('ANDROID_PUSH_API_KEY'), $device_tokens, $message);
                            $result = json_decode($result, true);
                        }
                        if (isset($result['success']) && (int) $result['success'] > 0) {
                            $response['status'] = $Lang['messages']['success'];
                            $response['message'] = $Lang['messages']['noti_send_success'];
                        } elseif (isset($result['failure'])) {
                            $response['message'][] = $Lang['messages']['noti_send_failure'];
                        }
                    }
                }
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response);
    }

    /**
     * API::upload_image()
     *
     * @return
     */
    public static function upload_image($user_id = NULL) {
        global $Lang, $_FILES;
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        if ($user_id == NULL) {
            $session = Session::authenticate();
            $user_id = $session->user_id;
        }
        if ($_FILES['photo']['error'] > 0) {
            return json_encode(array('status' => "failure", 'message' => "Error in file uploading"));
        } else {
            $imageBaseDir = Config::read('BASE_PATH') . "/avatar/";
            $file_name = basename($_FILES['photo']['name']);
            $file_format = Helper::FileExtension($file_name);
            $fn = md5($file_name) . time() . "." . $file_format;
            $file_path = Config::read('BASE_PATH') . '/avatar/' . $fn;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                $user = ORM::for_table('users')->find_one($user_id);
                if ($user->avatar != '') {
                    if (file_exists(Config::read('BASE_PATH') . '/avatar/' . $user->avatar)) {
                        unlink(Config::read('BASE_PATH') . '/avatar/' . $user->avatar);
                    }
                }
                $user->avatar = $fn;
                if ($user->save()) {
                    $response['status'] = $Lang['messages']['success'];
                    $response['message'] = $Lang['messages']['photo_success'];
                }
            } else {
                $response['message'][] = $Lang['messages']['photo_failure'];
            }
        }
        return json_encode($response);
    }

    /**
     * API::upload_image()
     *
     * @return
     */
    public static function upload_event_image($event_id) {
        global $Lang, $_FILES;
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $session = Session::authenticate();
        if ($_FILES['photo']['error'] > 0) {
            return json_encode(array('status' => "failure", 'message' => "Error in file uploading"));
        } else {
            $imageBaseDir = Config::read('BASE_PATH') . "/event_images/";
            $file_name = basename($_FILES['photo']['name']);
            $file_format = Helper::FileExtension($file_name);
            $fn = md5($file_name) . time() . "." . $file_format;
            $file_path = Config::read('BASE_PATH') . '/event_images/' . $fn;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                $event = ORM::for_table('events')->find_one($event_id);
                if ($event->photo != '') {
                    if (file_exists(Config::read('BASE_PATH') . '/event_images/' . $event->photo)) {
                        unlink(Config::read('BASE_PATH') . '/event_images/' . $event->photo);
                    }
                }
                $event->photo = $fn;
                if ($event->save()) {
                    $response['status'] = $Lang['messages']['success'];
                    $response['message'] = $Lang['messages']['photo_success'];
                }
            } else {
                $response['message'][] = $Lang['messages']['photo_failure'];
            }
        }
        return json_encode($response);
    }

    /*
      public static function send_event_notification($params = false)
      {
      global $Lang;
      $data = $params?$params:$_REQUEST;
      if(!$params)
      {
      $session = Session::authenticate();
      }
      require_once(Config::read('BASE_PATH').'/includes/device_notification.php');
      $response = array(
      'status' => $Lang['messages']['failure'],
      'message' => ''
      );
      $rules = array(
      'message' => 'required',
      'event_id' => 'required|integer'
      );
      $filters = array(
      'message' => 'trim|sanitize_string',
      'event_id' => 'trim|sanitize_numbers'
      );
      $validator = new GUMP();
      $data = $validator->sanitize($data);
      $validated = $validator->validate($data, $rules);
      $data = $validator->filter($data, $filters);
      if($validated === TRUE)
      {
      if(isset($data['action']) && strtolower($data['action']) == 'delete')
      {
      $invited_users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->where_in('invitation_status',array(Config::read('E_JOINED'),Config::read('E_MAYBE')))->find_array();
      }
      elseif(isset($data['action']) && strtolower($data['action']) == 'invite')
      {
      $invited_users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->where_in('invitation_id',$data['invitations'])->find_array();
      //$invited_users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->where_in('invitation_status',array(Config::read('E_JOINED'),Config::read('E_MAYBE')))->where_not_equal('invitation_notification_sent',1)->find_array();
      }
      else
      {
      $invited_users = ORM::for_table('event_invitations')->select('user_id')->where_equal('event_id', $data['event_id'])->where_in('invitation_status',array(Config::read('E_PENDING'),Config::read('E_JOINED'),Config::read('E_MAYBE')))->find_array();
      }
      $invited_users = Helper::array_value_recursive('user_id',$invited_users);
      if(!empty($invited_users))
      {
      $devices = ORM::for_table('users')->where_in('user_id',$invited_users)->find_array();
      if(!empty($devices))
      {
      $ios_devices = array_filter($devices, function($v) { return strcasecmp(strtoupper($v['client_type']),"IPHONE") == 0; });
      $android_devices = array_filter($devices, function($v) { return strcasecmp(strtoupper($v['client_type']),"ANDROID") == 0; });
      if(!empty($ios_devices))
      {
      $chunk_ios_devices = array_chunk($ios_devices,25);
      foreach($chunk_ios_devices as $chunk_ios_devices)
      {
      $device_tokens = Helper::array_value_recursive('push_token',$chunk_ios_devices);
      $result = sendMessageToIPhone($device_tokens, $data['message']);
      //echo '<pre>'; print_r($result); die("");
      }
      $response['status'] = $Lang['messages']['success'];
      $response['message'] = $Lang['messages']['noti_send_success'];

      }
      if(!empty($android_devices))
      {
      $result = array();
      $chunk_android_devices = array_chunk($android_devices,25);
      foreach($chunk_android_devices as $chunk_android_device)
      {
      $message = new stdClass();
      $message->aps = new stdClass();
      $message->aps->alert = $data['message'];
      $message->aps->sound = "default";
      $message = json_encode($message);
      $device_tokens = Helper::array_value_recursive('push_token',$chunk_android_device);
      $result = sendMessageToAndroidPhone(Config::read('ANDROID_PUSH_API_KEY'), $device_tokens, $message);
      $result = json_decode($result,true);
      }
      if(isset($result['success']) && (int)$result['success'] > 0)
      {
      $response['status'] = $Lang['messages']['success'];
      $response['message'] = $Lang['messages']['noti_send_success'];
      }
      else if(isset($result['failure']))
      {
      $response['message'][] = $Lang['messages']['noti_send_failure'];
      }
      }
      }
      }
      }
      else
      {
      $response['message'] = $validator->get_readable_errors();
      }
      return json_encode($response);
      }
     */

    /**
     * API::is_event_editable()
     *
     * @param mixed $event_id
     * @param mixed $session
     * @return
     */
    public static function is_event_editable($event_id, $session) {
        global $Lang;
        $event = ORM::for_table('events')->find_one($event_id);
        if ($event) {
            if ($event->event_creator != $session->user_id) {
                return $Lang['messages']['unauth_event_edit'];
                exit();
            }
            $responded_invitation_count = ORM::for_table('event_invitations')->where_equal('event_id', $event_id)->where_gt('invitation_status', Config::read('E_PENDING'))->count();
            if ($responded_invitation_count > 0) {
                return $Lang['messages']['responded_event'];
            } else {
                return true;
            }
        } else {
            return $Lang['messages']['event_not_found'];
            exit();
        }
    }

    /**
     * API::is_user_eligible_for_invitation()
     *
     * @param mixed $user_id
     * @param mixed $event_id
     * @return
     */
    public static function is_user_eligible_for_invitation($user_id, $event_id = null) {
        global $Lang;
        $eligible = false;
        $user_count = ORM::for_table('users')->where_equal('user_id', $user_id)->count();
        if ($user_count) {
            $eligible = true;
            /* if only joined user can invite */
            /*
              if($event_id)
              {
              $user_joined = ORM::for_table('event_invitations')->where_equal('user_id',$user_id)->where_equal('event_id',$event_id)->where_equal('invitation_status',Config::read('E_JOINED'))->count();
              if($user_joined == 0)
              {
              $eligible = false;
              }
              }
             */
        }
        return $eligible;
    }

    /**
     * API::get_readable_invitation_sent_status()
     *
     * @param mixed $invitation_status
     * @return
     */
    public static function get_readable_invitation_sent_status($sent_status) {
        switch ($sent_status) {
            case Config::read('E_I_DELIVERED'):
                $sent_status = 'delivered';
                break;
            case Config::read('E_I_VIEWED'):
                $sent_status = 'viewed';
                break;
            default:
                $sent_status = 'sent';
                break;
        }
        return $sent_status;
    }

    /**
     * API::get_readable_invitation_status()
     *
     * @param mixed $invitation_status
     * @return
     */
    public static function get_readable_invitation_status($invitation_status) {
        switch ($invitation_status) {
            case Config::read('E_PENDING'):
                $invitation_status = 'pending';
                break;
            case Config::read('E_JOINED'):
                $invitation_status = 'joined';
                break;
            case Config::read('E_MAYBE'):
                $invitation_status = 'maybe';
                break;
            case Config::read('E_DECLINED'):
                $invitation_status = 'declined';
                break;
            default:
                Config::read('E_NOT_INVITED');
                break;
        }
        return $invitation_status;
    }

    /**
     * API::get_readable_friendship_request_status()
     *
     * @param mixed $friendship_req_status
     * @return
     */
    public static function get_readable_friendship_request_status($friendship_req_status) {
        switch ($friendship_req_status) {
            case Config::read('F_PENDING'):
                $friendship_req_status = 'pending';
                break;
            case Config::read('F_ACCEPTED'):
                $friendship_req_status = 'accepted';
                break;
            case Config::read('F_DENIED'):
                $friendship_req_status = 'denied';
                break;
        }
        return $friendship_req_status;
    }

    /**
     * API::get_users()
     *
     * @return
     */
    public static function get_users() {
        $users = ORM::for_table('users')->find_array();
        return json_encode($users);
    }

    /**
     * API::get_username()
     *
     * @return
     */
    public static function get_username($user_id) {
        $user = ORM::for_table('users')->find_one($user_id);
        return ucwords($user->name);
    }

    public static function send_email($address, $subject, $email_body) {
        //SMTP needs accurate times, and the PHP time zone MUST be set
        //This should be done in your php.ini, but this is how to do it if you don't have access to that
        date_default_timezone_set('Etc/UTC');
        //Create a new PHPMailer instance
        $mail = new PHPMailer();
        //Tell PHPMailer to use SMTP
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;
        //Ask for HTML-friendly debug output
        $mail->Debugoutput = 'html';
        //Set the hostname of the mail server
        $mail->Host = Config::read('SMTP_HOST');
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = Config::read('SMTP_PORT');
        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = Config::read('SMTP_USERNAME');
        //Password to use for SMTP authentication
        $mail->Password = Config::read('SMTP_PASSWROD');
        //Set who the message is to be sent from
        $mail->setFrom(Config::read('EMAIL_FROM'), 'COLCOM');
        //Set an alternative reply-to address
        $mail->addReplyTo(Config::read('REPLY_TO'), 'REPLY TO');
        //Set who the message is to be sent to
        $mail->addAddress($address['email'], $address['name']);
        //Set the subject line
        $mail->Subject = $subject; //'PHPMailer GMail SMTP test';
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $mail->msgHTML($email_body);
        //Replace the plain text body with one created manually
        //$mail->AltBody = 'This is a plain-text message body';
        //Attach an image file
        //$mail->addAttachment('images/phpmailer_mini.gif');
        //send the message, check for errors
        if (!$mail->send()) {
            return false;
            //echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            return true;
            //echo "Message sent!";
        }
    }

    public static function send_test_mail() {
        //SMTP needs accurate times, and the PHP time zone MUST be set
        //This should be done in your php.ini, but this is how to do it if you don't have access to that
        date_default_timezone_set('Etc/UTC');
        //Create a new PHPMailer instance
        $mail = new PHPMailer();
        //Tell PHPMailer to use SMTP
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 2;
        //Ask for HTML-friendly debug output
        $mail->Debugoutput = 'html';
        //Set the hostname of the mail server
        $mail->Host = Config::read('SMTP_HOST');
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = Config::read('SMTP_PORT');
        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = 'tls';
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = Config::read('SMTP_USERNAME');
        //Password to use for SMTP authentication
        $mail->Password = Config::read('SMTP_PASSWROD');
        //Set who the message is to be sent from
        $mail->setFrom(Config::read('EMAIL_FROM'), 'COLCOM');
        //Set an alternative reply-to address
        $mail->addReplyTo(Config::read('REPLY_TO'), 'REPLY TO');
        //Set who the message is to be sent to
        $mail->addAddress('tareqrahim@gmail.com', 'Tareq Rahim');
        //Set the subject line
        $mail->Subject = 'PHPMailer GMail SMTP test';
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $mail->msgHTML("this is a test message");
        //Replace the plain text body with one created manually
        $mail->AltBody = 'This is a plain-text message body';
        //Attach an image file
        //$mail->addAttachment('images/phpmailer_mini.gif');
        //send the message, check for errors
        if (!$mail->send()) {
            //return false;
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            //return true;
            echo "Message sent!";
        }
    }

    /**
     * API::import_db()
     *
     * @return
     */
    public static function import_db() {
        $response = array(
            'status' => 'failure',
            'message' => ''
        );
        $rules = array(
            'db' => 'required'
        );
        $filters = array(
            'db' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $file_path = Config::read('BASE_PATH') . '/db_schema/' . $data['db'] . '.sql';
            $response = DBHelper::parse_mysql_dump($file_path);
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /**
     * API::change_char_set()
     *
     * @return
     */
    public static function change_char_set() {
        $rules = array(
            'char_set' => 'required|alpha_numeric',
            'collation' => 'required|alpha_numeric'
        );
        $filters = array(
            'char_set' => 'trim|sanitize_string',
            'collation' => 'trim|sanitize_string'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            return DBHelper::changeCharSet($data['char_set'], $data['collation']);
        } else {
            return json_encode(array(
                'error' => 1,
                'status' => $validator->get_readable_errors()
            ));
        }
    }

    /**
     * Get Timezone id form lat,lon
     * e.g. America/Los_Angeles
     */
    public static function GetTimeZoneIDFromLatLong($latitude, $longitude) {
        $url = 'https://maps.googleapis.com/maps/api/timezone/json?location=' . (float) $latitude . ',' . (float) $longitude . '&timestamp=1331766000&sensor=true&key=AIzaSyCInbEp8GU87U9aadwQAGB-vd2UQH9vzl0';
        $timezoneJson = (string) file_get_contents($url);
        $timeZoneId = Config::read('DEFAULT_TIMEZONE');
        if ($timezoneJson != "") {
            $timezoneJson = json_decode($timezoneJson, true);
            if (!empty($timezoneJson) && $timezoneJson['status'] == 'OK') {
                $timeZoneId = $timezoneJson['timeZoneId'];
            }
        } else {
            $timeZoneId = 'America/Los_Angeles';
        }
        return $timeZoneId;
        /*
          $gmtRows = ORM::for_table('timezone_gmt')->where_equal('timeZoneId',$timeZoneId)->find_array();
          if(!empty($gmtRows))
          {
          return $gmtRows[0]['gmt_value'];
          }
          else
          {
          return '+00:00';
          } */
    }

    public static function test_timezone() {
        date_default_timezone_set('America/Los_Angeles');
        echo date('Y-m-d H:i:s') . '<br>';
        date_default_timezone_set('America/Argentina/Catamarca');
        echo date('Y-m-d H:i:s');
        //self::GetTimeZoneIDFromLatLong(39.6034810,-119.6822510);
    }

    /**
     * API::get_group_list()
     *
     * @author - Sandip Bhalodia
     *
     * @return
     */
    public static function get_group_list() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => 'failure',
            'message' => $Lang['messages']['user_not_found']
        );
        if (isset($session['user_id']) && !empty($session['user_id'])) {
            $user_id = $session['user_id'];
            $groups = ORM::for_table('group')->select_many('id', 'name')->where_equal('creator_id', $user_id)->order_by_asc('name')->find_array();
            if (!empty($groups)) {
                $response = array(
                    'status' => 'success',
                    'data' => $groups
                );
            } else {
                $response = array(
                    'status' => 'success',
                    'message' => $groups
                );
            }
        }

        return json_encode($response);
    }

    /**
     * API::create_group()
     *
     * @author - Twisha Gupte
     *
     * @return
     */
    public static function create_group() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'name' => 'required|max_len,100|min_len,2',
            'members' => 'required',
        );
        $filters = array(
            'name' => 'trim|sanitize_string',
            'members' => 'trim',
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);
        if ($validated === TRUE) {
            $existing_group = ORM::for_table('group')->where_equal('name', $data['name'])->where_equal('creator_id', $session['user_id'])->count();
            if ($existing_group > 0) {
                $response['message'][] = $Lang['messages']['group_exists'];
                return json_encode($response, JSON_NUMERIC_CHECK);
            } else {
                $group = ORM::for_table('group')->create();
                $group->name = $data['name'];
                $group->creator_id = $session['user_id'];
                $group->created_at = date("Y-m-d H:i:s", time());
                if ($group->save()) {

                    $count_added = 0;
                    $count_not_added = 0;
                    $members = explode(',', $data['members']);
                    if (count($members) > 0) {
                        foreach ($members as $user_id) {
                            if ($user_id == $session['user_id']) {
                                $response['message'][] = $Lang['messages']['self_group_member'];
                                return json_encode($response, JSON_NUMERIC_CHECK);
                            }
                            $existing_member = ORM::for_table('group_members')->where_equal('user_id', $user_id)->count();
                            if ($existing_member) {
                                $count_not_added++;
                            } else {
                                $group_members = ORM::for_table('group_members')->create();
                                $group_members->group_id = $group->id;
                                $group_members->user_id = $user_id;
                                $group_members->created_at = date("Y-m-d H:i:s", time());
                                if ($group_members->save()) {
                                    $count_added++;
                                }
                                $response['status'] = 'success';
                                $response['message'] = array('added' => $count_added, 'not_added' => $count_not_added);
                            }
                        }
                    } else {
                        $response['message'][] = $Lang['messages']['param_empty'];
                    }

                    $response['status'] = $Lang['messages']['success'];
                    $response['message'] = $Lang['messages']['group_created'];

                    return json_encode($response, JSON_NUMERIC_CHECK);
                } else {
                    $response['message'][] = $Lang['messages']['save_error'];
                    return json_encode($response, JSON_NUMERIC_CHECK);
                }
            }
            //}
        } else {
            $response['message'] = $validator->get_readable_errors();
            return json_encode($response, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * API::get_profile_info()
     *
     * @return
     */
    public static function get_profile_info() {
        global $Lang;
        $session = Session::authenticate();
        $user = ORM::for_table('users')->find_one($session['user_id']);
        if ($user) {
            $pass_array = array(
                'user_id' => $user->user_id,
                'photo' => (!empty($user->avatar)) ? Config::read('BASE_URL') . '/avatar/' . $user->avatar : Config::read('BASE_URL') . '/avatar/default.png',
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'country' => $user->country,
                'gender' => $user->gender,
                'age' => $user->age,
                'has_reminder' => $user->has_reminder
            );
            $response = array(
                'status' => 'success',
                'data' => $pass_array
            );
        } else {
            $response = array(
                'status' => 'failure',
                'message' => 'User not found'
            );
        }
        return json_encode($response);
    }

    /**
     * API::set_profile_info()
     *
     * @return
     */
    public static function set_profile_info() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => 'failure',
            'message' => ''
        );
        $rules = array(
            'email' => 'required|valid_email',
            'phone_number' => 'required|max_len,15|min_len,8',
            'name' => 'max_len,100|min_len,2',
            'age' => 'required|integer',
            'country' => 'required|alpha',
            'gender' => 'required|alpha',
            'password' => 'max_len,100|min_len,6',
        );
        $filters = array(
            'email' => 'trim|sanitize_email',
            'phone_number' => 'trim|sanitize_string',
            'name' => 'trim|sanitize_string',
            'age' => 'trim|sanitize_numbers',
            'country' => 'trim|sanitize_string',
            'gender' => 'trim|sanitize_string',
            'password' => 'trim|sha1',
        );

        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $data = $validator->filter($data, $filters);
        $validated = $validator->validate($data, $rules);

        if ($validated === TRUE) {
            $result = ORM::for_table('users')->where_equal('email', $data['email'])->where_not_equal('user_id', $session['user_id'])->find_one();
            if ($result) {
                $response['message'][] = $Lang['messages']['email_exists'];
                return json_encode($response, JSON_NUMERIC_CHECK);
            } else {
                $user = ORM::for_table('users')->where_equal('user_id', $session['user_id'])->find_one();
//                $user->avatar = (isset($data['']) && !empty($data[''])) ? $data[''] : '';
                $user->name = (isset($data['name']) && !empty($data['name'])) ? $data['name'] : $user->name;
                $user->phone_number = (isset($data['phone_number']) && !empty($data['phone_number'])) ? $data['phone_number'] : $user->phone_number;
                $user->email = (isset($data['email']) && !empty($data['email'])) ? $data['email'] : $user->email;
                $user->country = (isset($data['country']) && !empty($data['country'])) ? $data['country'] : $user->country;
                $user->gender = (isset($data['gender']) && !empty($data['gender'])) ? $data['gender'] : $user->gender;
                $user->age = (isset($data['age']) && !empty($data['age'])) ? $data['age'] : $user->age;
                $user->has_reminder = (isset($data['has_reminder']) && !empty($data['has_reminder'])) ? $data['has_reminder'] : $user->has_reminder;
                $user->password = (isset($_REQUEST['password']) && !empty($_REQUEST['password'])) ? $data['password'] : $user->password;
                $user->updated_at = date("Y-m-d H:i:s", time());
                if ($user->save()) {
                    if (isset($_FILES['photo'])) {
                        API::upload_image($user->user_id);
                    }
                    $response['status'] = 'success';
                    $response['message'] = $Lang['messages']['edit_cur_user_success'];
                    return json_encode($response, JSON_NUMERIC_CHECK);
                } else {
                    $response['message'][] = $Lang['messages']['save_error'];
                    return json_encode($response, JSON_NUMERIC_CHECK);
                }
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
            return json_encode($response, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * API::filter_friend_contacts()
     *
     * @return
     */
    public static function filter_friend_contacts() {
        global $Lang;
        $session = Session::authenticate();
        $response = array(
            'status' => $Lang['messages']['failure'],
            'message' => ''
        );
        $rules = array(
            'phonebook' => 'required'
        );
        $filters = array(
            'phonebook' => 'trim|json_decode'
        );
        $validator = new GUMP();
        $data = $validator->sanitize($_REQUEST);
        $validated = $validator->validate($data, $rules);
        $data = $validator->filter($data, $filters);
        if ($validated === TRUE) {
            $sync_counter = 0;
            $phonebook = $data['phonebook'];
            if (count($phonebook) > 0) {
                //update current user
                $current_user = ORM::for_table('users')->find_one($session->user_id);
                $current_user->phonebook_contact_count = count($phonebook);
                $current_user->save();

                //get users from phone contacts
                $users_from_phonebook = ORM::for_table('users')->select('user_id')->where_in('phone_number_tr', $phonebook)->order_by_asc('user_id')->find_array();
                $users_from_phonebook = Helper::array_value_recursive('user_id', $users_from_phonebook);
                //get existing friends
                $existing_friends = array();
                $sql = "( SELECT receiver_id AS user_id
                            FROM friend_requests
                            WHERE sender_id = :user_id
                                AND receiver_id != :user_id
                        ) UNION
                        ( SELECT sender_id AS user_id
                            FROM friend_requests
                            WHERE sender_id != :user_id
                            AND receiver_id = :user_id
                        )
                        ORDER BY user_id ASC";
                $existing_friends = ORM::for_table('friend_requests')->raw_query($sql, array('user_id' => $session->user_id))->find_array();
                if (empty($existing_friends)) {
                    $existing_friends[] = $session->user_id;
                } else {
                    $existing_friends = Helper::array_value_recursive('user_id', $existing_friends);
                    $existing_friends[] = $session->user_id;
                }

                //get friends from phone but not friends in app yet
                $friends_from_phonebook = array_diff($users_from_phonebook, $existing_friends);

                //send frinds requests
                if (!empty($friends_from_phonebook)) {
                    foreach ($friends_from_phonebook as $friend) {
                        $friend_request = ORM::for_table('friend_requests')->create();
                        $friend_request->sender_id = $session->user_id;
                        $friend_request->receiver_id = $friend;
                        $friend_request->status = Config::read('F_PENDING');
                        if ($friend_request->save()) {
                            $sync_counter++;
                        }
                    }
                }
                $response['status'] = $Lang['messages']['success'];
                $response['message'][] = sprintf($Lang['messages']['sync_stat'], $sync_counter);
            } else {
                $response['status'] = $Lang['messages']['success'];
                $response['message'][] = sprintf($Lang['messages']['sync_stat'], $sync_counter);
            }
        } else {
            $response['message'] = $validator->get_readable_errors();
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

}

?>