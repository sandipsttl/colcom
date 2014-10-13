<?php

function sendMessageToAndroidPhone($API_KEY, $registrationIds, $messageText = '') {
    $headers = array();
    $headers[] = "Content-Type: application/json";
    $headers[] = 'Authorization: key=' . $API_KEY;
    //$messageText = base64_encode($messageText);
    //
		$data = array('registration_ids' => $registrationIds, 'data' => array('payload' => utf8_encode($messageText)));
    $data_string = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");
    //curl_setopt($ch, CURLOPT_URL, "http://localhost/4sale/receive.php");
    if ($headers)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function sendMessageToIPhone($deviceTokens, $msg = '', $notificationType = 0) {
    $output = '';
//    $deviceToken = '03c69df535fb9d38142ef78ed29589516e29a3752fc7b2d8a024900ace999103';
    $passphrase = '';
    $message = $msg;
    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', dirname(__file__) . DIRECTORY_SEPARATOR . 'colcom.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 2, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
//    $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 2, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);
    $body['aps'] = array('alert' => $message, 'url' => '', 'sound' => 'default', 'badge' => 1, 't' => $notificationType);
    $payload = json_encode($body);
    foreach ($deviceTokens as $deviceToken) {
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
        $result = fwrite($fp, $msg, strlen($msg));
        if (!$result)
            $output .= $deviceToken . ",";
        else
            $output .= $result . '<br>';
    }
    fclose($fp);
    return $output;
}
?>