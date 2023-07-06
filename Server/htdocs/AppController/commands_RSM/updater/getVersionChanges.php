<?php
//***************************************************
//This file returns a recordSet with the requirements, bugFixings and chageRequests done between two given RSM-versions.
//    If there is no error but no registers were found, the PHP will return an empty recordset.
//    The RSM-versions must be given in the correct order.
//
//INPUT: we need the first and last version of RSM for searching between them.
//    - startVersion: lowest RSM-version.  For example: 5.1.9.3.130
//    - endVersion  : highest RSM-version. For example: 5.2.10.3.131
//OUTPUT:
//    Recordset with three columns:
//    - type: with the text 'changeRequest' or 'bugFixing' or 'requirement'
//    - description: the description of the item in the language of the RSM-application
//    - Modules: the affected modules
//    The answer will give us a row for each pair of description:module.
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMmodulesManagement.php";
require_once "../utilities/RStools.php";
require_once "getVersionFunctions.php";

isset($GLOBALS['RS_POST']['clientID']) ? $clientID     = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['startVersion']) ? $startVersion = $GLOBALS['RS_POST']['startVersion'] : dieWithError(400);
isset($GLOBALS['RS_POST']['endVersion']) ? $endVersion   = $GLOBALS['RS_POST']['endVersion'] : dieWithError(400);
isset($GLOBALS['RS_POST']['RSlanguage']) ? $lang         = $GLOBALS['RS_POST']['RSlanguage'] : dieWithError(400);

$result       = array();

$fixedBugs      = getFixedBugs($RSuserID, $clientID, $startVersion, $endVersion, $lang);
$changeRequests = getChangeRequest($RSuserID, $clientID, $startVersion, $endVersion, $lang);
$requirements   = getRequirements($RSuserID, $clientID, $startVersion, $endVersion, $lang);

// Save all different modules in one array
$differentModules = array();
foreach ($fixedBugs as $fixedBug) {
    $differentModules[] = $fixedBug['module'];
}

foreach ($changeRequests as $changeRequest) {
    $differentModules[] = $changeRequest['module'];
}

foreach ($requirements as $requirement) {
    $differentModules[] = $requirement['module'];
}

$differentModules = array_unique($differentModules);

foreach ($differentModules as $module) {
    foreach ($requirements as $requirement) {
        if ($requirement['module'] == $module) {
            $result[] = array('type' => $requirement['type'], 'description' => $requirement['description'], 'module' => $module);
        }
    }

    foreach ($changeRequests as $changeRequest) {
        if ($changeRequest['module'] == $module) {
            $result[] = array('type' => $changeRequest['type'], 'description' => $changeRequest['description'], 'module' => $module);
        }
    }

    foreach ($fixedBugs as $fixedBug) {
        if ($fixedBug['module'] == $module) {
            $result[] = array('type' => $fixedBug['type'], 'description' => $fixedBug['description'], 'module' => $module);
        }
    }
}

// And write XML Response back to the application
RSreturnArrayQueryResults($result);
