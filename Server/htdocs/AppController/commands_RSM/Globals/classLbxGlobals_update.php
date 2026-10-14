<?php
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMbinaryUploads.php';

$updates = array(); $inserts = array(); $deletes = array();
try {
    for ($i = 0; isset($GLOBALS[$cstRS_POST]['var' . $i]); $i++) {
        $fields = explode(' ', $GLOBALS[$cstRS_POST]['var' . $i]);
        switch ($fields[0]) {
            case '1':
                if (count($fields) !== 5) throw new InvalidArgumentException('Invalid global update');
                $updates[] = array('dbName' => base64_decode($fields[1], true), 'name' => base64_decode($fields[2], true),
                    'value' => base64_decode($fields[3], true), 'type' => $fields[4]);
                break;
            case '2':
                if (count($fields) !== 4) throw new InvalidArgumentException('Invalid global insert');
                $inserts[] = array('name' => base64_decode($fields[1], true), 'value' => base64_decode($fields[2], true), 'type' => $fields[3]);
                break;
            case '3':
                if (count($fields) !== 2) throw new InvalidArgumentException('Invalid global delete');
                $deletes[] = array('dbName' => base64_decode($fields[1], true));
                break;
            default:
                throw new InvalidArgumentException('Invalid global operation');
        }
    }
    $results = RSsaveGlobalVariables($GLOBALS[$cstRS_POST][$cstClientID], $updates, $inserts, $deletes);
} catch (LengthException $exception) {
    $results = array('result' => 'NOK', 'message' => 'Image exceeds the configured size limit');
} catch (Throwable $exception) {
    $results = array('result' => 'NOK', 'message' => 'Unable to save variables');
}
RSReturnArrayResults($results);
