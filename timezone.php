<?php
echo date_default_timezone_get().' '.date('Y-m-d H:i:s');exit;
$url = 'https://maps.googleapis.com/maps/api/timezone/json?location=39.6034810,-119.6822510&timestamp=1331766000&sensor=true&key=AIzaSyCInbEp8GU87U9aadwQAGB-vd2UQH9vzl0';
$timezoneJson = (string)file_get_contents($url);
echo '<pre>';
print_r(json_decode($timezoneJson));
?>