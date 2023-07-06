<?php
// ****************************************************************************************
// DESCRIPTION
//     Retrieves a list of itemtypes with the words SOME or ALL, depending on the number of permissions for all visible properties related to the token
// PARAMETERS:
//        RStoken: token for which the itemtypes must be retrieved
// RETURNS:
//   Array of itemtypes. Each position corresponds to an entry of the following:
//   (ID, check = SOME/ALL).
// ****************************************************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$RStoken  = $GLOBALS['RS_POST']['token'   ];

// First of all recover the tokenID pertaining to the passed token
$tokenID = RSgetTokenID($RStoken);

// Obtain the properties from table tokens_permissions and their number of assigned permissions
$properties = RSQuery("SELECT RS_PROPERTY_ID, COUNT(RS_PERMISSION) AS numPermissions FROM rs_token_permissions WHERE RS_CLIENT_ID = " . $clientID . " AND RS_TOKEN_ID = " . $tokenID . " GROUP BY RS_PROPERTY_ID");

// Check the query results
if (!$properties) {
    // There was a problem executing the query
    $response['result'] = "NOK";
    $response['description'] = "ERROR EXECUTING QUERY TO GATHER PROPERTIES";

    // And write XML Response back to the application
    RSReturnArrayResults($properties);
}

// Obtain the properties and number of permissions from the query results
$propertiesNum = array();
$itemTypesRelated = array();

while ($row = $properties->fetch_assoc()) {
    $propertiesNum[] = array('ID' => $row['RS_PROPERTY_ID'], 'numPermissions' => $row['numPermissions'], 'ITEMTYPE' => getClientPropertyItemType($row['RS_PROPERTY_ID'], $clientID));
    $itemTypesRelated[] = getClientPropertyItemType($row['RS_PROPERTY_ID'], $clientID);
}

// We ned the itemtypes with assigned permissions
$itemTypesRelated = array_values(array_unique($itemTypesRelated));

// For each itemtype we count the number of visible properties
$visibleProperties = array();
foreach ($itemTypesRelated as $itemTypeRelated) {
    $cuenta = 0;
    // and compare for each property
    foreach ($propertiesNum as $propertyNum) {
        if ($propertyNum['ITEMTYPE'] == $itemTypeRelated) {
            $cuenta = $cuenta + $propertyNum['numPermissions'];
        }
    }
    if (4 * count(getUserVisibleProperties($itemTypeRelated, $clientID, $RSuserID)) == $cuenta) {
        $check = 'ALL';
    } else {
        $check = 'SOME';
    }
    $visibleProperties[] = array('ID' => $itemTypeRelated, 'check' => $check);
}

RSReturnArrayQueryResults($visibleProperties);
