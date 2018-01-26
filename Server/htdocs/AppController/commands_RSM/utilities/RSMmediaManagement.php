<?php

/* Function that uploads a passed file to media server
**Params: (file property identification and data)
****$clientID
****$itemID
****$propertyID
****$file_data
****$file_name
**Returns: Array with keys:
****result: OK/NOK
****description:error description if result=nok
*/
function setMediaFile($clientID,$itemID,$propertyID,$file_data,$file_name){
    $results= array();

    // prepare cURL
    $ch = curl_init($RSMmediaURL . "api_setFile.php");
    $data = 'clientID=' . $clientID . '&itemID=' . $itemID . '&propertyID=' . $propertyID . '&data=' . urlencode(base64_encode($file_data)) . '&name=' . urlencode(base64_encode($file_name));

    // set options
    curl_setopt($ch, CURLOPT_POST          , true );
    curl_setopt($ch, CURLOPT_POSTFIELDS    , $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_HEADER        , 0    );

    // grab URL and receive xml
    $xmlret = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);

    // parse xml
    $xml = simplexml_load_string($xmlret);
    $xml_elements = $xml -> rows -> row;

    foreach ($xml_elements[0] -> column as $propertyValue) {
        if ($propertyValue -> attributes() -> name == 'result'     ) $results['result'     ] = trim($propertyValue);
        if ($propertyValue -> attributes() -> name == 'description') $results['description'] = trim($propertyValue);
    }

    if(!isset($results['result'])) {
        $results['result'     ] = "NOK";
        $results['description'] = "Missing response uploading file: clientID=" . $clientID . ', itemID=' . $itemID . ', propertyID=' . $propertyID ;
    }

    return $results;
}


/* Function that downloads a specified file from media server
**Params: (file property identification)
****$clientID
****$itemID
****$propertyID
**Returns: Array with keys:
****RS_DATA: binary file
****RS_NAME: file name
*/
function getMediaFile($clientID,$itemID,$propertyID){
    $file = array();
    $curlFileName = "";

    $ch = curl_init($RSMmediaURL . "api_getFile.php");
    $data = 'clientID=' . $clientID . '&itemID=' . $itemID . '&propertyID=' . $propertyID;

    // set options
    curl_setopt($ch, CURLOPT_POST          , true                );
    curl_setopt($ch, CURLOPT_POSTFIELDS    , $data               );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true                );
    //curl_setopt($ch, CURLOPT_HEADER        , 0                   );
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback');

    // grab URL and receive file
    $file['RS_DATA'] = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);

    $file['RS_NAME'] = $curlFileName;

    return $file;
}


/* Function that deletes passed file from media server
**Params: (file property identification)
****$clientID
****$itemID
****$propertyID
**Returns: Array with keys:
****result: OK/NOK
****description:error description if result=nok
*/
function deleteMediaFile($clientID,$itemID,$propertyID){
    $results= array();

    // prepare cURL
    $ch = curl_init($RSMmediaURL . "api_deleteFile.php");
    $data = 'clientID=' . $clientID . '&itemID=' . $itemID . '&propertyID=' . $propertyID;

    // set options
    curl_setopt($ch, CURLOPT_POST          , true );
    curl_setopt($ch, CURLOPT_POSTFIELDS    , $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_HEADER        , 0    );

    // grab URL and receive xml
    $xmlret = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);

    // parse xml
    $xml = simplexml_load_string($xmlret);
    $xml_elements = $xml -> rows -> row;

    foreach ($xml_elements[0] -> column as $propertyValue) {
        if ($propertyValue -> attributes() -> name == 'result'     ) $results['result'     ] = trim($propertyValue);
        if ($propertyValue -> attributes() -> name == 'description') $results['description'] = trim($propertyValue);
    }

    if(!isset($results['result'])) {
        $results['result'     ] = "NOK";
        $results['description'] = "Missing response deleting file: clientID=" . $clientID . ', itemID=' . $itemID . ', propertyID=' . $propertyID ;
    }

    return $results;
}


/* Function that deletes all files for passed property from media server
**Params:
****$clientID
****$propertyID
**Returns: Array with keys:
****result: OK/NOK
****description:error description if result=nok
*/
function deleteMediaProperty($clientID,$propertyID){
    $results= array();

    // prepare cURL
    $ch = curl_init($RSMmediaURL . "api_deleteProperty.php");
    $data = 'clientID=' . $clientID . '&propertyID=' . $propertyID;

    // set options
    curl_setopt($ch, CURLOPT_POST          , true );
    curl_setopt($ch, CURLOPT_POSTFIELDS    , $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_HEADER        , 0    );

    // grab URL and receive xml
    $xmlret = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);

    // parse xml
    $xml = simplexml_load_string($xmlret);
    $xml_elements = $xml -> rows -> row;

    foreach ($xml_elements[0] -> column as $propertyValue) {
        if ($propertyValue -> attributes() -> name == 'result'     ) $results['result'     ] = trim($propertyValue);
        if ($propertyValue -> attributes() -> name == 'description') $results['description'] = trim($propertyValue);
    }

    if(!isset($results['result'])) {
        $results['result'     ] = "NOK";
        $results['description'] = "Missing response deleting property: clientID=" . $clientID . ', propertyID=' . $propertyID ;
    }

    return $results;
}


/* Function for parsing curl response headers and store file name in $curlFileName var*/
function curlHeaderCallback($resURL, $strHeader) {
    global $curlFileName;
    $reDispo = '/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/im';

    if (preg_match($reDispo, $strHeader, $mDispo)){
        $curlFileName = trim($mDispo['f'],' ";');

    }
    return strlen($strHeader);
}

?>
