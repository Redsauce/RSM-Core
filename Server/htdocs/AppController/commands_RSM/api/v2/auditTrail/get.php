<?php
//***************************************************************************************
// Description:
//    Get the audittrail of of the specified item and specified property
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1: 
//   {
//     "ID": [471],
//     "propertyID": 821
//   }
//***************************************************************************************

require_once "../../../utilities/RStools.php";
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";
require_once "../../../utilities/RSMverifyBody.php";

// definitions and validations 
$requestBody = getRequestBody();
verifyBodyContent($requestBody);
$clientID = getClientID();
$RSuserID =  getRSuserID();

//Params
$propertyID = $requestBody->propertyID;
$ID = $requestBody->ID;

if ((!RShasREADTokenPermission(getRStoken(), $propertyID)) && (!isPropertyVisible($RSuserID, $propertyID, $clientID))) {
    if ($RSallowDebug) returnJsonMessage(403, "Token has no permissions to audit this item");
    else returnJsonMessage(403, "");
}

// Process response
$results = getAuditTrail($clientID, $propertyID, $ID);

// construct the first part using the common properties of the response
$responseArray = array(
    "propertyType" => $results[0]["propertyType"],
    "changes" => array()
);
// loop through the results and add the not common properties to the response (the changes)

foreach ($results as $item) {
    $change = array(
        "userName" => $item["userName"],
        "description" => $item["description"] ?? "",
        "changedDate" => $item["changedDate"],
        "initialValue" => $item["initialValue"],
        "finalValue" => $item["finalValue"]
    );
    $responseArray["changes"][] = $change;
}

// verify if there are no changes
if (empty($responseArray['changes'])) {
    if ($RSallowDebug) returnJsonMessage(200, "Requested item does not have an Audit trail registered");
    else returnJsonMessage(200, "");
}
// enconde response as json and return
$response = json_encode($responseArray);
returnJsonResponse($response);

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
    checkBodyIsJsonObject($body);
    checkBodyContainsID($body);
    checkBodyContainsPropertyID($body);
}