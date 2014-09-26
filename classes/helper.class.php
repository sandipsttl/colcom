<?php
class Helper
{
    public static function filter($data)
    {
        $data = trim(htmlentities(strip_tags($data)));
        if (get_magic_quotes_gpc())
            $data = stripslashes($data);
        //$data = mysql_real_escape_string($data);
        return $data;
    }
    public static function EncodeURL($url)
    {
        $new = strtolower(ereg_replace(' ', '_', $url));
        return ($new);
    }
    public static function DecodeURL($url)
    {
        $new = ucwords(ereg_replace('_', ' ', $url));
        return ($new);
    }
    public static function ChopStr($str, $len)
    {
        if (strlen($str) < $len)
            return $str;
        $str = substr($str, 0, $len);
        if ($spc_pos = strrpos($str, " "))
            $str = substr($str, 0, $spc_pos);
        return $str . "...";
    }
    public static function isEmail($email)
    {
        return preg_match('/^\S+@[\w\d.-]{2,}\.[\w]{2,6}$/iU', $email) ? true : false;
    }
    public static function isUserID($username)
    {
        if (preg_match('/^[a-z\d_]{5,20}$/i', $username)) {
            return true;
        } else {
            return false;
        }
    }
    public static function isURL($url)
    {
        if (preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)) {
            return true;
        } else {
            return false;
        }
    }
    public static function checkPwd($x, $y)
    {
        if (empty($x) || empty($y)) {
            return false;
        }
        if (strlen($x) < 5 || strlen($y) < 5) {
            return false;
        }
        if (strcmp($x, $y) != 0) {
            return false;
        }
        return true;
    }
    public static function array_value_recursive($key, array $arr)
    {
        $val = array();
        array_walk_recursive($arr, function($v, $k) use ($key, &$val)
        {
            if ($k == $key)
                array_push($val, $v);
        });
        return $val;
    }
    public static function GenPwd($length = 7)
    {
        $password = "";
        $possible = "0123456789bcdfghjkmnpqrstvwxyz"; //no vowels
        $i        = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }
    public static function GenKey($length = 7)
    {
        $password = "";
        $possible = "0123456789abcdefghijkmnopqrstuvwxyz";
        $i        = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }
    public static function getReverseNumber($num)
    {
        $num    = (int) ($num);
        $revnum = 0;
        do {
            $revnum = ($revnum * 10) + ($num % 10);
            $num    = (int) ($num / 10);
        } while ($num > 0);
        echo $revnum; // 50201456
    }
    // Password and salt generation
    public static function PwdHash($pwd, $salt = null)
    {
        if ($salt === null) {
            $salt = substr(md5(uniqid(rand(), true)), 0, Config::read('SALT_LENGTH'));
        } else {
            $salt = substr($salt, 0, Config::read('SALT_LENGTH'));
        }
        return $salt . sha1($pwd . $salt);
    }
    public static function createUniqueKey()
    {
        $rnd_id = crypt(uniqid(rand(), 1));
        //to remove any slashes that might have come
        $rnd_id = strip_tags(stripslashes($rnd_id));
        //Removing any . or / and reversing the string
        $rnd_id = str_replace(".", "", $rnd_id);
        $rnd_id = strrev(str_replace("/", "", $rnd_id));
        $rnd_id = substr($rnd_id, 0, 10);
        return $rnd_id;
    }
    public static function getTimeDiff($t1, $t2)
    {
        $a1    = explode(":", $t1);
        $a2    = explode(":", $t2);
        $time1 = (($a1[0] * 60 * 60) + ($a1[1] * 60));
        $time2 = (($a2[0] * 60 * 60) + ($a2[1] * 60));
        $diff  = ($time1 - $time2);
        return $diff;
    }
    public static function getTimeTotal($t1, $t2)
    {
        $a1     = explode(":", $t1);
        $a2     = explode(":", $t2);
        $time1  = (($a1[0] * 60 * 60) + ($a1[1] * 60));
        $time2  = (($a2[0] * 60 * 60) + ($a2[1] * 60));
        $total  = abs($time1 + $time2);
        $hours  = floor($total / (60 * 60));
        $mins   = floor(($total - ($hours * 60 * 60)) / (60));
        $result = $hours . ":" . $mins;
        return $result;
    }
    public static function numberPad($number, $n)
    {
        return str_pad((int) $number, $n, "0", STR_PAD_LEFT);
    }
    public static function FileExtension($str) //returns jpg,JPG gif,etc
    {
        $i = strrpos($str, ".");
        if (!$i) {
            return "";
        }
        $l   = strlen($str) - $i;
        $ext = substr($str, $i + 1, $l);
        return strtolower($ext);
    }
    public static function createToken()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string     = '';
        for ($i = 0; $i < 4; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
    public static function output_file($file, $name, $mime_type = '')
    {
        /*
        This function takes a path to a file to output ($file),
        the filename that the browser will see ($name) and
        the MIME type of the file ($mime_type, optional).
        
        If you want to do something on download abort/finish,
        register_shutdown_function('function_name');
        */
        if (!is_readable($file))
            die('File not found or inaccessible!');
        $size             = filesize($file);
        $name             = rawurldecode($name);
        /* Figure out the MIME type (if not specified) */
        $known_mime_types = array(
            "pdf" => "application/pdf",
            "txt" => "text/plain",
            "html" => "text/html",
            "htm" => "text/html",
            "exe" => "application/octet-stream",
            "zip" => "application/zip",
            "doc" => "application/msword",
            "xls" => "application/vnd.ms-excel",
            "ppt" => "application/vnd.ms-powerpoint",
            "gif" => "image/gif",
            "png" => "image/png",
            "jpeg" => "image/jpg",
            "jpg" => "image/jpg",
            "php" => "text/plain"
        );
        if ($mime_type == '') {
            //$file_extension = strtolower(substr(strrchr($file,"."),1));
            $extensionstart = strrpos($file, ".") + 1;
            $file_extension = substr($file, $extensionstart);
            if (array_key_exists($file_extension, $known_mime_types)) {
                $mime_type = $known_mime_types[$file_extension];
            } else {
                $mime_type = "application/force-download";
            }
        }
        @ob_end_clean(); //turn off output buffering to decrease cpu usage
        // required for IE, otherwise Content-Disposition may be ignored
        if (ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        /* The three lines below basically make the
        download non-cacheable */
        header("Cache-control: private");
        header('Pragma: private');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        // multipart-download and download resuming support
        if (isset($_SERVER['HTTP_RANGE'])) {
            list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($range) = explode(",", $range, 2);
            list($range, $range_end) = explode("-", $range);
            $range = intval($range);
            if (!$range_end) {
                $range_end = $size - 1;
            } else {
                $range_end = intval($range_end);
            }
            $new_length = $range_end - $range + 1;
            header("HTTP/1.1 206 Partial Content");
            header("Content-Length: $new_length");
            header("Content-Range: bytes $range-$range_end/$size");
        } else {
            $new_length = $size;
            header("Content-Length: " . $size);
        }
        /* output the file itself */
        $chunksize  = 1 * (1024 * 1024); //you may want to change this
        $bytes_send = 0;
        if ($file = fopen($file, 'r')) {
            if (isset($_SERVER['HTTP_RANGE']))
                fseek($file, $range);
            while (!feof($file) && (!connection_aborted()) && ($bytes_send < $new_length)) {
                $buffer = fread($file, $chunksize);
                print($buffer); //echo($buffer); // is also possible
                flush();
                $bytes_send += strlen($buffer);
            }
            fclose($file);
        } else
            die('Error - can not open file.');
        die();
    }
    public static function createThumbnail($filename)
    {
        global $FINAL_WIDTH_OF_IMAGE, $UPLOAD_PATH, $THUMB_UPLOAD_PATH;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($UPLOAD_PATH . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromgif($UPLOAD_PATH . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($UPLOAD_PATH . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($UPLOAD_PATH . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);
        $nx = $FINAL_WIDTH_OF_IMAGE;
        $ny = floor($oy * ($FINAL_WIDTH_OF_IMAGE / $ox));
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($THUMB_UPLOAD_PATH)) {
            if (!mkdir($THUMB_UPLOAD_PATH)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($nm, $THUMB_UPLOAD_PATH . $filename);
        //die ($THUMB_UPLOAD_PATH . $filename);
    }
    public static function msort($array, $key, $sort_flags = SORT_REGULAR)
    {
        if (is_array($array) && count($array) > 0) {
            if (!empty($key)) {
                $mapping = array();
                foreach ($array as $k => $v) {
                    $sort_key = '';
                    if (!is_array($key)) {
                        $sort_key = $v[$key];
                    } else {
                        // @TODO This should be fixed, now it will be sorted as string
                        foreach ($key as $key_key) {
                            $sort_key .= $v[$key_key];
                        }
                        $sort_flags = SORT_STRING;
                    }
                    $mapping[$k] = $sort_key;
                }
                asort($mapping, $sort_flags);
                $sorted = array();
                foreach ($mapping as $k => $v) {
                    $sorted[] = $array[$k];
                }
                return $sorted;
            }
        }
        return $array;
    }
    /**
     *The basics **
     * 
     * The function make_comparer accepts a variable number of arguments that define the desired sort and returns a function 
     * that you are supposed to use as the argument to usort or uasort.
     * The simplest use case is to pass in the key that you 'd like to use to compare data items. 
     * For example, to sort $data by the name item you would do usort($data, make_comparer('name'));
     * 
     * The key can also be a number if the items are numerically indexed arrays. For the example in the question, this would be
     * 
        * usort($data, make_comparer(0)); // 0 = first numerically indexed column
     * 
     * Multiple sort columns **
     * 
     * You can specify multiple sort columns by passing additional parameters to make_comparer. 
     * For example, to sort by "number" and then by the zero-indexed column:
     * 
        * usort($data, make_comparer('number', 0));
     * 
     *
     * Advanced features **
     * 
     * More advanced features are available if you specify a sort column as an array instead of a simple string. 
     * This array should be numerically indexed, and must contain these items:
     * 
         * 0 => the column name to sort on (mandatory)
         * 1 => either SORT_ASC or SORT_DESC (optional)
         * 2 => a projection function (optional)
     * 
     * Let's see how we can use these features.
     * 
     * Reverse sort **
     * 
         * To sort by name descending: 
         * usort($data, make_comparer(['name', SORT_DESC]));
     * 
     * 
         * To sort by number descending and then by name descending:
         * usort($data, make_comparer(['number', SORT_DESC], ['name', SORT_DESC]));
     * 
     * Custom projections**
     * 
     * In some scenarios you may need to sort by a column whose values do not lend well to sorting. 
     * The "birthday" column in the sample data set fits this description: 
     * it does not make sense to compare birthdays as strings (because e.g. "01/01/1980" comes before "10/10/1970"). 
     * In this case we want to specify how to project the actual data to a form that can be compared directly with the desired semantics.
     * 
     * Projections can be specified as any type of callable: as strings, arrays, or anonymous functions. 
     * A projection is assumed to accept one argument and return its projected form.
     * 
     * It should be noted that while projections are similar to the custom comparison functions used with usort and family, they are simpler 
     * (you only need to convert one value to another) and take advantage of all the functionality already baked into make_comparer.
     * 
     * Let's sort the example data set without a projection and see what happens:
     * 
         * usort($data, make_comparer('birthday'));
     * 
     * 
     * That was not the desired outcome. But we can use date_create as a projection:
     * 
         * usort($data, make_comparer(['birthday', SORT_ASC, 'date_create']));
     * 
     * 
     * This is the correct order that we wanted.
     * 
     * There are many more things that projections can achieve. 
     * For example, a quick way to get a case-insensitive sort is to use strtolower as a projection.
     * 
     * That said, I should also mention that it's better to not use projections if your data set is large: 
     * in that case it would be much faster to project all your data manually up front and then sort without using a projection, 
     * although doing so will trade increased memory usage for faster sort speed.
     * 
     * Finally, here is an example that uses all the features: it first sorts by number descending, then by birthday ascending:
     * 
         * usort($data, make_comparer(
         * ['number', SORT_DESC],
         * ['birthday', SORT_ASC, 'date_create']
         * ));
     *  
     */
    public static function make_comparer()
    {
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
            $criteria[$index] = is_array($criterion) ? array_pad($criterion, 3, null) : array(
                $criterion,
                SORT_ASC,
                null
            );
        }
        return function($first, $second) use (&$criteria)
        {
            foreach ($criteria as $criterion) {
                // How will we compare this round?
                list($column, $sortOrder, $projection) = $criterion;
                $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;
                // If a projection was defined project the values now
                if ($projection) {
                    $lhs = call_user_func($projection, $first[$column]);
                    $rhs = call_user_func($projection, $second[$column]);
                } else {
                    $lhs = $first[$column];
                    $rhs = $second[$column];
                }
                // Do the actual comparison; do not return if equal
                if ($lhs < $rhs) {
                    return -1 * $sortOrder;
                } else if ($lhs > $rhs) {
                    return 1 * $sortOrder;
                }
            }
            return 0; // tiebreakers exhausted, so $first == $second
        };
    }
}
?>