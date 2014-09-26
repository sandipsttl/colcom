<?php
class DBHelper{
public static function parse_mysql_dump($file_path)
	{
		$output = array('status'=>'success','message'=>'');
        try 
        {  
            ORM::get_db()->beginTransaction();
            $file_content = file($file_path);                        
            $query='';
            foreach($file_content as $sql_line)
            {
                if(trim($sql_line) != "" && strpos($sql_line, "--") === false)
                {
                    //echo $sql_line . '<br>';
                    $query.=' '.$sql_line;
                    if(strpos($sql_line,";")!==false)
                    {
                        if(ORM::get_db()->exec($query)==false)
                        {
                            $output['status'] = 'failure';
                            $output['message'][] = "Couldn't execute :$query";
                        }
                        $query='';
                    }
                }
            }
            if(ORM::get_db()->commit())
            {
                $output['status'] = 'success';
                $output['message'] = "Successful Commit";
            }
          
        } 
        catch (Exception $e) 
        {
            ORM::get_db()->rollBack();
            $output['message'][] = "Failed: " . $e->getMessage();
        }
        return $output;
    }	
    public static function changeCharSet($char_set,$collation)
    {
        $output = array('error'=>1,'status'=>'');
        $res = ORM::get_db()->query("SHOW TABLES");
        foreach($res as $key => $row)
        {
            foreach($row as $table)
            {
                //DBConfig::exec("ALTER TABLE " . $table . " CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
                if(ORM::get_db()->exec("ALTER TABLE " . $table . " CONVERT TO CHARACTER SET ".$char_set." COLLATE ".$collation))
                {
                    $output['error'] = 0;
                    $output['status'][] = "Table '".$table . "' Converted";                
                }
            }
        }
        return json_encode($output);
    }    
}
?>