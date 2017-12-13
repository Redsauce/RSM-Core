<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$userID = $GLOBALS['RS_POST']['userID'];



// get test cases item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['testcases'], $clientID);
/*
// get studies properties allowed
$propertiesAllowed = getVisibleProperties($itemTypeID, $clientID, $userID);

$testCasesNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesName'], $clientID);

$testCasesParentGroupPropertyID = getClientPropertyID_RelatedWith_byName($definitions['testcasesGroupID'], $clientID);


// get properties names (they will be assigned to the list columns)
if (in_array($testCasesNamePropertyID , $propertiesAllowed)) { $nameAllowed = '1'; } else { $nameAllowed = '0'; }
if (in_array($testCasesParentGroupPropertyID , $propertiesAllowed)) { $statusAllowed = '1'; } else { $parentGroupAllowed = '0'; }
*/
$results[0]['testcases'] = getClientItemTypeName($itemTypeID, $clientID);
/*
$results[0]['name'		] =getClientPropertyName($testCasesNamePropertyID 		, $clientID).'::'.$nameAllowed;  // fix me: separator used -> ::

$results[0]['parentGroup'	] = getClientPropertyName($testCasesParentGroupPropertyID		, $clientID).'::'.$parentGroupAllowed;
*/

// And return XML results
RSReturnArrayQueryResults($results);
?>