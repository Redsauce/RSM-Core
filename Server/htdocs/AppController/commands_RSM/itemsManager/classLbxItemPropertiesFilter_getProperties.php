<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMuserPropertiesManagement.php";

// definitions
$requestData = $GLOBALS[$cstRS_POST] ?? array();
$itemTypeID = resolveItemPropertiesFilterItemTypeID($requestData, $cstItemTypeID);
$clientID   = $requestData[$cstClientID] ?? 0;
$userID     = $requestData[$cstUserID] ?? 0;

if (!ctype_digit((string)$itemTypeID) || intval($itemTypeID) <= 0
    || !ctype_digit((string)$clientID) || intval($clientID) <= 0
    || !ctype_digit((string)$userID) || intval($userID) <= 0) {
    RSReturnArrayQueryResults(array(array('result' => 'NOK')));
}

$itemTypeID = intval($itemTypeID);
$clientID = intval($clientID);
$userID = intval($userID);

$results = getUserProperties($userID,$clientID,$itemTypeID);

// And return XML response back to application			
RSReturnArrayQueryResults($results);

function resolveItemPropertiesFilterItemTypeID($requestData, $canonicalKey)
{
    if (array_key_exists($canonicalKey, $requestData)) {
        return $requestData[$canonicalKey];
    }

    // Compatibility with legacy clients that still send "itemtypeID".
    // TODO: Remove the "itemtypeID" fallback after RSM 7.
    return $requestData['itemtypeID'] ?? 0;
}
