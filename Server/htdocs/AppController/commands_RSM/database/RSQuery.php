<?php
class RSQuery {
    public function makeQuery($theQuery, $types, $parameters, $registerError = true) {
        global $RSallowDebug;
        global $queryCount;
        global $mysqli;
        global $cstRS_POST;
        echo 'HAN LLAMADO AQUÍ' . PHP_EOL;
        echo 'HAN LLAMADO AQUÍ' . PHP_EOL;
        echo 'HAN LLAMADO AQUÍ' . PHP_EOL;
        echo 'HAN LLAMADO AQUÍ' . PHP_EOL;
        echo 'HAN LLAMADO AQUÍ' . PHP_EOL;
        // Look for a variable called RSdebug
        // If present, we will print in the error_log additional debug information
        // including time taken to execute the requests in the server
        // and the executed query statements
        $RSdebug = FALSE;
    
        if ($RSallowDebug && isset($GLOBALS[$cstRS_POST]['RSdebug'])) {
            $RSdebug = TRUE;
    
            // The RSdebug parameter is usually only sent by POST Master
            // global $queryCount;
            $queryCount++;
            $start = microtime(TRUE);
        }
        $statement = $mysqli->prepare($theQuery);
        if ($statement==null) {
            error_log('SQL Error:'.$mysqli->error);
        }
        
        $statement->bind_param($types,...$parameters);
        $statement->execute();
        // $result = $mysqli->query($theQuery);
        $result = $statement->get_result();
        if ($result===false && $registerError) {
            $this->RSerror("RSdatabase: failed query: $theQuery");
        }
    
        if (($RSallowDebug && $RSdebug)) {
            usleep(1);
            error_log ($theQuery . "\n");
            error_log ("Total queries executed: " . $queryCount . "\n\n");
            error_log ("time elapsed(seconds): " . (microtime(TRUE) - $start) . "\n\n");
        }
    
        //return query result
        return ($result);
    }

    //store a new error in database
    function RSError($message, $type = ""){
        /* global $mysqli;
        global $cstClientID;
        global $cstRS_POST;
    
        $query = "INSERT INTO `rs_error_log` (`RS_DATE`,`RS_URL`,`RS_POST`,`RS_RESULT`,`RS_TYPE`,`RS_CLIENT_ID`) VALUES (NOW(),'".
        $mysqli->real_escape_string("//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}").
        "','".$mysqli->real_escape_string(print_r($GLOBALS[$cstRS_POST],true))."','"
        .$mysqli->real_escape_string($message)."','".$type."',".$GLOBALS[$cstRS_POST][$cstClientID].")";
    
        // Query the database
        $result = $mysqli->query($query); */
        echo 'HA HABIDO UN ERROR!! --> ' . $message . PHP_EOL;
    }
}