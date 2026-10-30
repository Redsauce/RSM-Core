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
handleApiCorsPreflight('POST');
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('POST');

require_once '../../../utilities/RSdatabase.php';
require_once __DIR__ . '/../../../utilities/RSMbinaryProperties.php';
require_once '../../../utilities/RSMitemsManagement.php';
$requestBody = getRequestBody();
verifyBodyContent($requestBody);

// Definitions
$RStoken  =  getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID =  getRSuserID();

$responseArray = array();

$propertiesToCreate = array();
foreach ($requestBody as $item) {
  $correctProperties = array();

  foreach ($item as $propertyID => $propertyValue) {
    $rawPropertyID = $propertyID;
    $propertyID = parsePID($rawPropertyID, $clientID);

    if ($propertyID <= 0) {
      $RSallowDebug ? returnJsonMessage(400, 'Invalid propertyID: ' . $rawPropertyID) : returnJsonMessage(400, '');
    }

    // Only prepare properties where user has CREATE permission
    if ((RShasTokenPermission($RStoken, $propertyID, 'CREATE')) || (isPropertyVisible($RSuserID, $propertyID, $clientID))) {
      $propertyType = getPropertyType($propertyID, $clientID);
      if ($propertyType === 'file' || $propertyType === 'image') {
        try {
          RSparseBinaryPropertyValue($propertyValue);
        } catch (LengthException $exception) {
          returnJsonMessage(413, 'File/image exceeds the configured size limit');
        } catch (InvalidArgumentException $exception) {
          returnJsonMessage(400, 'Invalid file/image value: expected [filename:]hexadecimal with an even length');
        }
        // Preserve the filename encoding used by existing clients and reads.
        $correctProperties[] = array('ID' => $propertyID, 'value' => replaceUtf8Characters($propertyValue));
      } else {
        $correctProperties[] = array('ID' => $propertyID, 'value' => replaceUtf8Characters($propertyValue));
      }
    } else {
      $RSallowDebug ? returnJsonMessage(400, 'Not created (At least 1 property has no WRITE permissions or its not visible)') : returnJsonMessage(400, '');
    }
  }
  $itemTypeID = getItemTypeIDFromProperties(array_column($correctProperties, 'ID'), $clientID);
  $correctProperties = RSapplyTokenCustomerScopeToCreatePayload($RStoken, $clientID, $itemTypeID, $correctProperties);
  if ($correctProperties === false) {
    $RSallowDebug ? returnJsonMessage(403, 'Not created (Token customer scope does not allow creating this item)') : returnJsonMessage(403, '');
  }
  $propertiesToCreate[] = $correctProperties;
}

$transactionStarted = false;
$createdBefore = $RSMcreatedItemIDs;
try {
  if (!$mysqli->begin_transaction()) throw new RuntimeException('Unable to start creation transaction');
  $transactionStarted = true;
  foreach ($propertiesToCreate as $properties) {
    $newID = RScreateItemInTransaction($clientID, $properties);
    if ($newID <= 0) throw new RuntimeException('Unable to create item');
    $responseArray[] = array('ID' => $newID);
  }
  if (!$mysqli->commit()) throw new RuntimeException('Unable to commit creation transaction');
} catch (Throwable $exception) {
  if ($transactionStarted) {
    try { $mysqli->rollback(); } catch (Throwable $rollbackError) { error_log('RSM: API item creation rollback failed'); }
  }
  $RSMcreatedItemIDs = $createdBefore;
  returnJsonMessage(500, $RSallowDebug ? 'Unable to create items; no changes were committed' : '');
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
