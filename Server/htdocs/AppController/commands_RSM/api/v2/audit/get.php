<?php
//***************************************************************************************
// Description:
//    Get the audittrail of the specified item and specified property
// REQUEST BODY (JSON OBJECT):
//  EXAMPLE 1:
//   {
//     "ID": [471, 472],
//     "propertyID": 821
//   }
//***************************************************************************************

require_once "../../../utilities/RStools.php";
require_once "../../../utilities/RSMverifyBody.php";

handleApiCorsPreflight('GET');
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('GET');

require_once "../../../utilities/RSdatabase.php";
require_once "../../../utilities/RSMitemsManagement.php";

// definitions and validations
$requestBody = getRequestBody();
verifyBodyContent($requestBody);

$RStoken  = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

//Params
$propertyID = $requestBody->propertyID;
$ID = $requestBody->ID;

if ((!RShasREADTokenPermission($RStoken, $propertyID)) && (!isPropertyVisible($RSuserID, $propertyID, $clientID))) {
    if ($RSallowDebug) {
        returnJsonMessage(403, "Token has no permissions to audit this item");
    } else {
        returnJsonMessage(403, "");
    }
}

$itemTypeID = getItemTypeIDFromProperties(array($propertyID), $clientID);
if (!RSitemsMatchTokenCustomerScope($RStoken, $clientID, $itemTypeID, $ID)) {
    $RSallowDebug ? returnJsonMessage(403, "Token customer scope does not allow access to this item") : returnJsonMessage(403, "");
}

$itemIDs = $ID;
$responseArray = array();

foreach ($itemIDs as $itemID) {
    $results = getAuditTrail($clientID, $propertyID, $itemID);
    if (!is_array($results)) {
        $results = array();
    }

    $itemResponse = array(
        "ID" => $itemID,
        "propertyType" => !empty($results) ? $results[0]["propertyType"] : getPropertyType($propertyID, $clientID),
        "changes" => array()
    );

    foreach ($results as $item) {
        $change = array(
            "userName" => $item["userName"],
            "description" => $item["description"] ?? "",
            "changedDate" => $item["changedDate"],
            "initialValue" => $item["initialValue"],
            "finalValue" => $item["finalValue"]
        );
        $itemResponse["changes"][] = $change;
    }

    $responseArray[] = $itemResponse;
}

// verify if there are no changes
if (emptyAuditTrailResponse($responseArray)) {
    if ($RSallowDebug) {
        returnJsonMessage(200, "Requested item does not have an Audit trail registered");
    } else {
        returnJsonMessage(200, "");
    }
}
// enconde response as json and return
$response = json_encode($responseArray);
returnJsonResponse($response);

// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
    checkIsJsonObject($body);
    checkBodyContains($body, "ID");
    checkBodyContains($body, "propertyID");
    checkStringIsInteger($body->propertyID);
    checkIsArray($body->ID);

    foreach ($body->ID as $itemID) {
        checkStringIsInteger($itemID);
    }
}

function emptyAuditTrailResponse($responseArray)
{
    foreach ($responseArray as $item) {
        if (!empty($item["changes"])) {
            return false;
        }
    }

    return true;
}
