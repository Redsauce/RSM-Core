<?php
// ****************************************************************************************
//Description:
//  Creates one or more items (even from different types)
//
//  REQUEST BODY (JSON)
//  Array with object/s inside, each object must contain at least
//          - one propertyID and its value
//  EXAMPLE:
//      [{
//          "85": "Mesa",
//          "86": "individual"
//        },{
//          "45": "Avengers",
//          "46": "vose"
//       }]
// ****************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('POST');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';
require_once '../../api_headers.php';

$requestBody = getRequestBody();
verifyBodyContent($requestBody);

// Definitions
$clientID = getClientID();
$RStoken =  getRStoken();
$RSuserID =  getRSuserID();

$responseArray = array();

$propertiesToCreate = array();
foreach ($requestBody as $item) {
  $correctProperties = array();

  foreach ($item as $propertyID => $propertyValue) {
    // Only prepare properties where user has CREATE permission
    if ((RShasTokenPermission($RStoken, $propertyID, 'CREATE')) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
      $correctProperties[] = array('ID' => $propertyID, 'value' => replaceUtf8Characters($propertyValue));
    } else {
      $RSallowDebug ? returnJsonMessage(400, 'Not created (At least 1 property has no WRITE permissions or its not visible)') : returnJsonMessage(400, '');
    }
  }
  $propertiesToCreate[] = $correctProperties;
}

foreach ($propertiesToCreate as $properties) {
  $values = array();
  // create the item
  $newID = createItem($clientID, $properties);
  $values['ID'] = $newID;
  $responseArray[] = $values;
}

if (!empty($responseArray)) {
  returnJsonResponse(json_encode($responseArray));
} else {
  $RSallowDebug ? returnJsonMessage(400, 'Not created') : returnJsonMessage(400, '');
}


// Verify if body contents are the ones expected
function verifyBodyContent($body)
{
  checkIsArray($body);
  foreach ($body as $item) {
    checkIsJsonObject($item);
  }
}
