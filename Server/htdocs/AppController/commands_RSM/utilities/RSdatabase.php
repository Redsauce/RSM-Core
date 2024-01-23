<?php
//****************************************************
// RSdatabase.php
//****************************************************
// Description:
//  holds the database configuration parameters. This
//  file is included by every othr file in need of
//  connecting with the database. Also holds a default
//  connection and error reporting routines.
//***************************************************

require_once "RSconfiguration.php";
require_once "RSMeventsManagement.php";

// Save the start time for debugging
$php_start = microtime(TRUE);

// This variable counts the number of queries performed
$queryCount = 0;

// Clean POST data in order to avoid SQL injections
$search  = array("'", "\"");
$replace = array("&rsquo;" , "&quot;");

foreach ($_POST as $key => $value) {
    $GLOBALS[$cstRS_POST][$key] = str_replace($search, $replace, $value);
}

// Variables used to track the amount of items created / modified / deleted
$RSMcreatedItemIDs = array();
$RSMupdatedItemIDs = array();
$RSMdeletedItemIDs = array();

// Connect to the database using the above settings
$mysqli = new mysqli($RShost, $RSuser, $RSpassword, $RSdatabase);
if ($mysqli->connect_errno) {
    RSReturnError("CANNOT CONNECT TO DATABASE SERVER", -1);
}

// Check database compatibility and user permisions
if (!isset($RSUpdatingProcess)) {
    require_once ("RSsecurityCheck.php");
}

// If execution reaches this point, the user has permissions to work
// against the database, and the application version is authorized

/**
 * GZIPs a file on disk (appending .gz to the name)
 *
 * From https://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
 * Based on function by Kioob at:
 * https://www.php.net/manual/en/function.gzwrite.php#34955
 *
 * @param string $source Path to file that should be compressed
 * @param integer $level GZIP compression level (default: 9)
 * @return string New filename (with .gz appended) if success, or false if operation fails
 */
function gzCompressFile($source, $level = 9){
    $dest = $source . '.gz';
    $mode = 'wb' . $level;
    $error = false;
    if ($fp_out = gzopen($dest, $mode)) {
        if ($fp_in = fopen($source,'rb')) {
            while (!feof($fp_in)) {
                gzwrite($fp_out, fread($fp_in, 1024 * 512));
            }

            fclose($fp_in);
        } else {
            $error = true;
        }
        gzclose($fp_out);

    } else {
        $error = true;
    }

    if ($error) {
        return false;
    } else {
        return $dest;
    }
}

//For compatibility with older versions of app using 'FILESIZE:::' in compressed string
function checkCompressionVersion($response){
	global $cstRS_POST;
	
    if (isset($GLOBALS[$cstRS_POST]['RSbuild']) && substr(strrchr($GLOBALS[$cstRS_POST]['RSbuild'], "."), 1) < 149) {
        return strlen($response).':::';
    } else {
        return '';
    }
}

function mem_increase_check(&$lastValues){
    global $RSallowDebug;
	
    if($RSallowDebug) {
        error_log ("\n\nused memory increment from #"      . $lastValues["i"] . "(MiB): " . (memory_get_usage()         - $lastValues["startUsage"])/1024/1024);
        error_log ("max used memory increment from #"      . $lastValues["i"] . "(MiB): " . (memory_get_peak_usage()    - $lastValues["startPeakUsage"])/1024/1024);
        error_log ("allocated memory increment from #"     . $lastValues["i"] . "(MiB): " . (memory_get_usage(true)     - $lastValues["startAllocated"])/1024/1024);
        error_log ("max allocated memory increment from #" . $lastValues["i"] . "(MiB): " . (memory_get_peak_usage(true)- $lastValues["startPeakallocated"])/1024/1024 . "\n");
        
        $lastValues["i"]++;
        $lastValues["startUsage"        ] = memory_get_usage();
        $lastValues["startPeakUsage"    ] = memory_get_peak_usage();
        $lastValues["startAllocated"    ] = memory_get_usage(true);
        $lastValues["startPeakallocated"] = memory_get_peak_usage(true);
        
        error_log ("used memory at #"          . $lastValues["i"] . "(MiB): " . ($lastValues["startUsage"        ])/1024/1024);
        error_log ("max used memory at #"      . $lastValues["i"] . "(MiB): " . ($lastValues["startPeakUsage"    ])/1024/1024);
        error_log ("allocated memory at #"     . $lastValues["i"] . "(MiB): " . ($lastValues["startAllocated"    ])/1024/1024);
        error_log ("max allocated memory at #" . $lastValues["i"] . "(MiB): " . ($lastValues["startPeakallocated"])/1024/1024 . "\n");
    }
}


function mem_usage_check($maxMem=50){
    global $RSallowDebug;
	global $cstRS_POST;
	
    if($RSallowDebug) {
        $maxBytes=$maxMem*1024*1024;
        if(memory_get_peak_usage() > $maxBytes || memory_get_peak_usage(true) > $maxBytes) {
            error_log ("\n\nMemory limit of " . $maxMem . " MiB exceded");
            error_log ("Request path: //{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            error_log ("Max used memory(MiB): " . memory_get_peak_usage()/1024/1024);
            error_log ("Max allocated memory(MiB): " . memory_get_peak_usage(true)/1024/1024);
            error_log ("POST data: " . print_r($GLOBALS[$cstRS_POST],true) . "\n\n");
        }
    }
}

//save a recordset to xml file and returns file identifier
function mysqlToXML($resource,$clientID,$itemTypeID,$propertiesToTranslate=array(),$extFilterRules="",$decodeEntities=false){
    global $RStempPath;
	global $cstCDATAseparator;
	global $cstMainPropertyID;
	global $cstMainPropertyType;
	global $cstReferredItemTypeID;
	global $cstUTF8;

    // create a temporary file with a unique filename
    $filename = @tempnam($RStempPath, "RSR");
    if ($filename) {
        $writer = new XMLWriter();
        $writer->openUri($filename);

        $writer->setIndentString('  ');
        $writer->setIndent(true);

        $writer->startDocument( '1.0', 'UTF-8' );

        $writer->startElement('RSRecordset');
        $writer->startElement('rows');

        //prepare extFilters
        $extFilterArr = explode(',', $extFilterRules);
        $extFilters = array();
        if($extFilterRules != "") {
            foreach ($extFilterArr as $extFilterItem) {
                // get property data
                $filterArr = explode(';', $extFilterItem);

                // get all ascendants matching the filter
                $ascendantItemTypeID = getItemTypeIDFromProperties(array($filterArr[0]), $clientID);
                $filterProperties = array( array('ID' => parsePID($filterArr[0],$clientID), 'value' => str_replace("&amp;", "&", htmlentities(base64_decode($filterArr[1]), ENT_COMPAT, $cstUTF8)), 'mode' => $filterArr[2]));

                $validAscendants = getFilteredItemsIDs($ascendantItemTypeID, $clientID, $filterProperties, array());

                //get ascendant ItemType MainProperty ID and Type for treePath root level
                $ascendantItemTypeMainPropertyID   = getMainPropertyID($ascendantItemTypeID, $clientID);
                $ascendantItemTypeMainPropertyType = getPropertyType  ($ascendantItemTypeMainPropertyID, $clientID);

                // get all paths between filtered and destination itemtype
                $allowedItemTypes = array();
                if (isset($filterArr[3]) && $filterArr[3] != "") $allowedItemTypes = explode(",", base64_decode($filterArr[3]));

                $treePath = array();

                getTreePath($clientID, $treePath, array( array('itemTypeID' => $ascendantItemTypeID,$cstMainPropertyID=>$ascendantItemTypeMainPropertyID,$cstMainPropertyType=>$ascendantItemTypeMainPropertyType)), $itemTypeID, $allowedItemTypes, 4);

                $extFilters[] = array('ascendantItemTypeID' => $ascendantItemTypeID, 'validAscendants' => $validAscendants, 'treePath' => $treePath);
            }
        }

        // Prepare ids translation
        $propertiesToReplace = array();
        foreach ($propertiesToTranslate as $propertyKey => $property) {
            if (($property['type'] == 'identifier') || ($property['type'] == 'identifiers')) {

                // get identifier property referred item type
                $referredItemTypeID = getClientPropertyReferredItemType($property['ID'], $clientID);
                $mainPropertyID     = getMainPropertyID($referredItemTypeID, $clientID);
                $mainPropertyType   = getPropertyType($mainPropertyID, $clientID);
                $propertiesToTranslate[$propertyKey][$cstReferredItemTypeID] = $referredItemTypeID;
                $propertiesToTranslate[$propertyKey][$cstMainPropertyID] = $mainPropertyID;
                $propertiesToTranslate[$propertyKey][$cstMainPropertyType] = $mainPropertyType;
            }

            // If property has not translated name we should replace the original, so move to another list for replacement instead of addition
            if (!isset($property['trName'])) {
                $propertiesToReplace[$propertiesToTranslate[$propertyKey]['name']] = $propertiesToTranslate[$propertyKey];
                unset($propertiesToTranslate[$propertyKey]);
            }
        }

        while ($row = $resource->fetch_assoc()) {

            if (count($extFilters) == 0) {
                $found = true;
            }

            foreach ($extFilters as $extFilter){

                // construct IDs tree for each result
                $tempPaths = getPathsForItem($clientID, $itemTypeID, $row['ID'], $extFilter['treePath'], 0, "");

                // search for any valid (filter matching) ascendant in generated paths
                $found = false;
                foreach ($extFilter['validAscendants'] as $validAscendant) {
                    foreach ($tempPaths as $element) {
                        if ($element["nodeID"] == $validAscendant["ID"] && $element["nodeItemType"] == $extFilter['ascendantItemTypeID']) {
                            $found = true;
                            break 2;
                        }
                    }
                }

                // If it doesn't have any matching ascendant for any filter don't add to file
                if (!$found) {
                    break;
                }
            }

            if ($found) {
                $writer->startElement('row');

                foreach ($row as $field => $value) {
                    if (array_key_exists($field, $propertiesToReplace)) {
                        // We have to translate and replace the value
                        $value = getTranslatedValue($clientID, $propertiesToReplace[$field], $value);
                    }

                    if ($decodeEntities) {
                        $field = html_entity_decode($field, ENT_COMPAT|ENT_QUOTES, $cstUTF8);
                        $value = html_entity_decode($value, ENT_COMPAT|ENT_QUOTES, $cstUTF8);
                    }

                    $writer->startElement('column');
                    $writer->writeAttribute('name', $field);
                    $writer->writeCData(str_replace("]]>", $cstCDATAseparator, $value));
                    $writer->endElement(); // </column>
                }

                //Now add translated properties if needed
                foreach ($propertiesToTranslate as $propertyKey => $property) {
                    $field = $propertiesToTranslate[$propertyKey]['trName'];
                    $value = getTranslatedValue($clientID, $propertiesToTranslate[$propertyKey], $row[$propertiesToTranslate[$propertyKey]['name']]);

                    if ($decodeEntities) {
                        $field = html_entity_decode($field, ENT_COMPAT|ENT_QUOTES, $cstUTF8);
                        $value = html_entity_decode($value, ENT_COMPAT|ENT_QUOTES, $cstUTF8);
                    }

                    $writer->startElement('column');
                    $writer->writeAttribute('name', $field);
                    $writer->writeCData(str_replace("]]>", $cstCDATAseparator, $value));
                    $writer->endElement(); // </column>
                }

                $writer->endElement(); // </row>
            }
        }

        $writer->endElement(); // </rows>
        $writer->flush();
        $writer->endElement(); // </RSRecordset>
        $writer->endDocument();

        return $filename;
    } else {
        return false;
    }
}

// Get the translated value of passed property depending on the property type
function getTranslatedValue($clientID, $property, $sourceValue) {
	global $cstMainPropertyID;
	global $cstMainPropertyType;
	global $cstReferredItemTypeID;

    if ($property['type'] == 'identifier') {
        $value = getItemPropertyValue($sourceValue, $property[$cstMainPropertyID], $clientID, $property[$cstMainPropertyType], $property[$cstReferredItemTypeID]);

    } elseif ($property['type'] == 'identifiers') {
        // If multiidentifier value = '' the function getItemsPropertyValues would bring all rows for this property so treat this case separatedly
        if ($sourceValue != '') {
            $values = getItemsPropertyValues($property[$cstMainPropertyID], $clientID, $sourceValue, $property[$cstMainPropertyType], $property[$cstReferredItemTypeID]);
            $value = implode('; ', $values);
        } else {
            $value = '';
        }

    } elseif ($property['type'] == 'identifier2itemtype') {
        $value = getClientItemTypeName($sourceValue, $clientID);

    } elseif ($property['type'] == 'identifier2property') {
        $value = getClientPropertyName($sourceValue, $clientID);

    }

    return $value;
}

// Write the error message
function RSReturnError($message, $code) {
	global $cstClientID;
	global $cstRS_POST;

    $theFile = "";
    $theFile .= ("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
    $theFile .= ("<RSError>");
    $theFile .= (  "<rows>");
    $theFile .= (    "<row>");
    $theFile .= (      "<column name=\"RSerrorMessage\">" . $message . "</column>");
    $theFile .= (      "<column name=\"RSerrorCode\">" . $code . "</column>");
    $theFile .= (    "</row>");
    $theFile .= (  "</rows>");
    $theFile .= ("</RSError>");

    Header('Content-type: text/xml');
    Header("Content-Length: " . strlen($theFile));

    echo $theFile;

    if (isset($GLOBALS[$cstRS_POST][$cstClientID])) {
        checkTriggeredEvents($GLOBALS[$cstRS_POST][$cstClientID]);
    }

    // Terminate PHP execution
    exit ;
}

// Converts the passed array of results to XML
function RSReturnArrayQueryResults($result, $compressed = true) {
    global $RSallowUncompressed;
    global $RStempPath;
	global $cstCDATAseparator;
	global $cstRSsendUncompressed;
	global $cstClientID;
	global $cstRS_POST;

    // Depending of the number of items returned, it's faster working with a file than with PHP variables
    // If the number of returned fields is greater than the optimizer value, a file will be used
    // set optimizer value (if the number of the result fields is greater than this number, file method will be apply)
    $optimizerValue = 2000;
    // FIX ME: ---> this value can be changed... test it to get optimum results

    $theFile = '';
    $filename = '';

    if (_predictNumberOfFields($result) > $optimizerValue) {

        // create a temporary file with a unique filename
        $filename = @tempnam($RStempPath, "RSR");

        if ($filename) {
            // open the temporary file
            $file = @fopen($filename, "w");

            if ($file) {
                // write XML response into the file
                fwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>");
                fwrite($file, "<RSRecordset>");
                fwrite($file, "<rows>");

                if (is_array($result)) {
                    foreach ($result as $row) {
                        fwrite($file, "<row>");

                        foreach ($row as $field => $value) {
                            fwrite($file, "<column name=\"" . $field . "\"><![CDATA[" . str_replace("]]>", $cstCDATAseparator, $value) . "]]></column>");
                        }

                        fwrite($file, "</row>");
                    }
                }

                fwrite($file, "</rows>");
                fwrite($file, '</RSRecordset>');

                // close the file
                fclose($file);
            }
        }
    }

    // Check compression required
    $compress = ((isset($GLOBALS[$cstRS_POST][$cstRSsendUncompressed]) || !$compressed) && ($RSallowUncompressed))? FALSE : TRUE;

    if (file_exists($filename) && filesize($filename) > 0) {
        header("Content-type: text/xml");
        if ($compress) {
            $comp_result = gzCompressFile($filename);
            if ($comp_result !== false) {
                // delete uncompressed temporary file
                removeTmpFile($filename);
                $filename = $comp_result;
                Header('Content-type: application/x-gzip');
            }
        }
        Header('Content-Length: ' . filesize($filename));
        readfile($filename);
    } else {
        // build response using the string concatenation; it is slower than file method if the number of results is high
        $theFile  = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
        $theFile .= "<RSRecordset>";
        $theFile .= "<rows>";

        if (is_array($result) || is_a($result,'SplFixedArray')) {
            foreach ($result as $row) {
                $theFile .= "<row>";

                foreach ($row as $field => $value) {
                    $theFile .= "<column name=\"" . $field . "\"><![CDATA[" . $value . "]]></column>";
                }

                $theFile .= "</row>";
            }
        }

        $theFile .= "</rows>";
        $theFile .= '</RSRecordset>';

        header("Content-type: text/xml");

        if ($compress) {
            $theFile = checkCompressionVersion($theFile).gzCompress($theFile, 9);
            Header('Content-type: application/x-gzip');
        }

        Header("Content-Length: " . strlen($theFile));
        echo $theFile;
    }

    // delete the temporary file
    if ($filename) {
        removeTmpFile($filename);
    }

    if (isset($GLOBALS[$cstRS_POST][$cstClientID])) {
        checkTriggeredEvents($GLOBALS[$cstRS_POST][$cstClientID]);
    }

    mem_usage_check();

    // Terminate PHP execution
    exit ;
}

// Converts the passed database results to XML
function RSReturnQueryResults($result, $compressed = true) {
    global $RSallowUncompressed;
    global $RStempPath;
	global $cstCDATAseparator;
	global $cstRSsendUncompressed;
	global $cstClientID;
	global $cstRS_POST;

    $theFile = "";
    $filename = '';

    // set optimizer value (if the number of the result fields is greater than this number, file method will be apply)
    $optimizerValue = 2000;
    // FIX ME: ---> this value can be changed... test it to get optimum results

    if (($result->num_rows * $result->field_count) > $optimizerValue) {

        // create a temporary file with a unique filename
        $filename = @tempnam($RStempPath, "RSR");

        if ($filename) {
            // open the temporary file
            $file = @fopen($filename, "w");

            if ($file) {
                // write XML response
                fwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>");
                fwrite($file, "<RSRecordset>");
                fwrite($file, "<rows>");

                while ($row = $result->fetch_assoc()) {
                    fwrite($file, "<row>");

                    foreach ($row as $field => $value) {
                        fwrite($file, "<column name=\"" . $field . "\"><![CDATA[" . str_replace("]]>", $cstCDATAseparator, $value) . "]]></column>");
                    }

                    fwrite($file, "</row>");
                }

                fwrite($file, "</rows>");
                fwrite($file, "</RSRecordset>");

                // close the file
                fclose($file);
            }
        }
    }

    // Check compression required
    $compress = ((isset($GLOBALS[$cstRS_POST][$cstRSsendUncompressed]) || !$compressed) && ($RSallowUncompressed))? FALSE : TRUE;

    if (file_exists($filename) && filesize($filename) > 0) {
        header("Content-type: text/xml");
        if ($compress) {
            $comp_result = gzCompressFile($filename);
            if ($comp_result !== false) {
                // delete uncompressed temporary file
                removeTmpFile($filename);
                $filename = $comp_result;
                Header('Content-type: application/x-gzip');
            }
        }

        Header('Content-Length: ' . filesize($filename));
        readfile($filename);

    } else {
        // build response using the string concatenation; it is slower than file method if the number of results is high
        $theFile .= "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
        $theFile .= "<RSRecordset>";
        $theFile .= "<rows>";

        while ($row = $result->fetch_assoc()) {
            $theFile .= "<row>";

            foreach ($row as $field => $value) {
                $theFile .= "<column name=\"" . $field . "\"><![CDATA[" . $value . "]]></column>";
            }

            $theFile .= "</row>";
        }

        $theFile .= "</rows>";
        $theFile .= "</RSRecordset>";

        header("Content-type: text/xml");

        if ($compress) {
            $theFile = checkCompressionVersion($theFile).gzCompress($theFile, 9);
            Header('Content-type: application/x-gzip');
        }

        Header("Content-Length: " . strlen($theFile));
        echo $theFile;
    }

    // delete the temporary file
    if ($filename) {
        removeTmpFile($filename);
    }

    if (isset($GLOBALS[$cstRS_POST][$cstClientID])) {
        checkTriggeredEvents($GLOBALS[$cstRS_POST][$cstClientID]);
    }

    mem_usage_check();

    // Terminate PHP execution
    exit;
}

// Converts the passed database results to XML
function RSReturnArrayResults($array, $compressed = true) {
    global $RSallowUncompressed;
	global $cstCDATAseparator;
	global $cstRSsendUncompressed;
	global $cstClientID;
	global $cstRS_POST;

    // this function uses to return few data and, overall, the number of concatenations is always small... so it's better using
    // the string concatenation method
    $theFile = '';
    $theFile .= "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
    $theFile .= "<RSRecordset>";
    $theFile .= "<rows>";
    $theFile .= "<row>";

    if (is_array($array)) {
        foreach ($array as $name => $value) {
            $theFile .= "<column name=\"" . $name . "\"><![CDATA[" . str_replace("]]>", $cstCDATAseparator, $value) . "]]></column>";
        }
    }

    $theFile .= "</row>";
    $theFile .= "</rows>";
    $theFile .= "</RSRecordset>";

    $compress = ((isset($GLOBALS[$cstRS_POST][$cstRSsendUncompressed]) || !$compressed) && ($RSallowUncompressed))? FALSE : TRUE;

    header("Content-type: text/xml");

    if ($compress) {
        $theFile = checkCompressionVersion($theFile).gzCompress($theFile, 9);
        Header('Content-type: application/x-gzip');
    }

    Header('Content-Length: ' . strlen($theFile));
    echo ($theFile);

    if (isset($GLOBALS[$cstRS_POST][$cstClientID])) {
        checkTriggeredEvents($GLOBALS[$cstRS_POST][$cstClientID]);
    }

    mem_usage_check();

    // Terminate PHP execution
    exit;
}

// Returns the passed xml file
function RSReturnFileResults($filename, $compressed = true) {
    global $RSallowUncompressed;
    global $RStempPath;
	global $cstRSsendUncompressed;
	global $cstClientID;
	global $cstRS_POST;

    // Check compression required
    $compress = ((isset($GLOBALS[$cstRS_POST][$cstRSsendUncompressed]) || !$compressed) && ($RSallowUncompressed))? FALSE : TRUE;

    if (file_exists($filename) && filesize($filename) > 0) {
        header("Content-type: text/xml");
        if ($compress) {
            $comp_result = gzCompressFile($filename);
            if ($comp_result !== false) {
                // delete uncompressed temporary file
                removeTmpFile($filename);
                $filename = $comp_result;
                Header('Content-type: application/x-gzip');
            }
        }
        Header('Content-Length: ' . filesize($filename));
        readfile($filename);

    } else {
        // file not exists or is empty so construct and return empty result
        $theFile .= "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
        $theFile .= "<RSRecordset>";
        $theFile .= "<rows>";
        $theFile .= "</rows>";
        $theFile .= "</RSRecordset>";

        header("Content-type: text/xml");
        if ($compress) {
            $theFile = gzCompress($theFile, 9);
            Header('Content-type: application/x-gzip');
        }

        Header("Content-Length: " . strlen($theFile));
        echo $theFile;
    }

    // delete the returned file
    if ($filename) {
        removeTmpFile($filename);
    }

    if (isset($GLOBALS[$cstRS_POST][$cstClientID])) {
        checkTriggeredEvents($GLOBALS[$cstRS_POST][$cstClientID]);
    }

    mem_usage_check();

    // Terminate PHP execution
    exit;
}

// try to predict the number of fields of an array of arrays (just considering its first ten rows)
function _predictNumberOfFields($result) {

    if (!is_array($result)) {
        return 0;
    }

    $limit = count($result);

    if ($limit > 10) {
        $limit = 10;
    }

    $count = 0;
    $i = 0;
    foreach ($result as $resultLine) {
        // Count the number of fields in the current element and add it to the total count
        $count += count($resultLine);
        $i++;

        //If the limit has been reached, break
        if ($i >= $limit) {
            break;
        }
    }

    if ($i > 0) {
        return (count($result) * round($count / $i));
    }
    return 0;
}

function RSQuery($theQuery, $registerError = true) {
    global $RSallowDebug;
    global $queryCount;
    global $mysqli;
	global $cstRS_POST;

    // Look for a variable called RSdebug
    // If present, we will print in the error_log additional debug information
    // including time taken to execute the requests in the server
    // and the executed query statements
    $RSdebug = FALSE;

    if ($RSallowDebug && isset($GLOBALS[$cstRS_POST]['RSdebug'])) {
        $RSdebug = TRUE;

        // The RSdebug parameter is usually only sent by POST Master
        global $queryCount;
        $queryCount++;
        $start = microtime(TRUE);
    }

    try {
        $result = $mysqli->query($theQuery);

        if (($RSallowDebug && $RSdebug)) {
            usleep(1);
            error_log ($theQuery . "\n");
            error_log ("Total queries executed: " . $queryCount . "\n\n");
            error_log ("time elapsed(seconds): " . (microtime(TRUE) - $start) . "\n\n");
        }
        //return query result
        return ($result);

    } catch (mysqli_sql_exception $e) {
        if ($registerError) {
            RSerror("RSdatabase: failed query: " . $theQuery);
            RSerror("RSdatabase: failed query: error:" . $e);
        }
        return false;
    }
}

//store a new error in database
function RSError($message, $type = ""){
  global $mysqli;
  global $cstClientID;
  global $cstRS_POST;

  $query = "INSERT INTO `rs_error_log` (`RS_DATE`,`RS_URL`,`RS_POST`,`RS_RESULT`,`RS_TYPE`,`RS_CLIENT_ID`) VALUES (NOW(),'".
  $mysqli->real_escape_string("//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}").
  "','".$mysqli->real_escape_string(print_r($GLOBALS[$cstRS_POST],true))."','"
  .$mysqli->real_escape_string($message)."','".$type."',".$GLOBALS[$cstRS_POST][$cstClientID].")";

   // Query the database
   try {
        $result = $mysqli->query($query);
   } catch (mysqli_sql_exception $e) {
        error_log ("RSdatabase: failed query: " . $query);
   }
}

//
function removeTmpFile($fileName, $prefix = "RSR") {
    if(file_exists($fileName)){
        // Get the directory from file
        $dir = dirname($fileName);

        //Remove passed file
        unlink($fileName);

        // Cycle through all files in the directory
        foreach (glob($dir."/".$prefix."*") as $file) {
            // If file is 24 hours (86400 seconds) old then delete it
            if(time() - filectime($file) > 86400) {
                unlink($file);
            }
        }
    }
}

?>
