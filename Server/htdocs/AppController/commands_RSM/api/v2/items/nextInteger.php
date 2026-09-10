<?php
// ****************************************************************************************
// Description:
//   Atomically calculate and assign the next positive value of an integer property.
//
// REQUEST BODY (JSON OBJECT):
// {
//   "itemID": "123",
//   "propertyID": "invoice.client.invoiceID",
//   "year": 2026,                                  // optional with yearPropertyID
//   "yearPropertyID": "invoice.client.invoiceDate",
//   "series": "A",                               // optional with seriesPropertyID
//   "seriesPropertyID": "invoice.client.serie"
// }
// ****************************************************************************************

require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
handleApiCorsPreflight('POST');
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('POST');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';

$requestBody = getRequestBody();
verifyNextIntegerBodyContent($requestBody);

$RStoken = getRStoken();
$clientID = RSclientFromToken(RStoken: $RStoken);
$RSuserID = getRSuserID();

$itemID = intval($requestBody->itemID);
$requestedPropertyID = $requestBody->propertyID;
$propertyID = ParsePID($requestedPropertyID, $clientID);
if (!is_numeric($propertyID) || intval($propertyID) <= 0) {
    $RSallowDebug ? returnJsonMessage(400, 'Invalid propertyID: ' . escapeNextIntegerMessageFragment($requestedPropertyID)) : returnJsonMessage(400, '');
}
$propertyID = intval($propertyID);

$propertyType = getPropertyType($propertyID, $clientID);
if ($propertyType !== 'integer') {
    $RSallowDebug ? returnJsonMessage(400, 'propertyID must identify an integer property') : returnJsonMessage(400, '');
}

$itemTypeID = intval(getClientPropertyItemType($propertyID, $clientID));
if ($itemTypeID <= 0) {
    $RSallowDebug ? returnJsonMessage(400, 'Unable to resolve the property item type') : returnJsonMessage(400, '');
}

if (!verifyItemExists($itemID, $itemTypeID, $clientID)) {
    $RSallowDebug ? returnJsonMessage(404, 'Item does not exist in the property item type') : returnJsonMessage(404, '');
}

if (!(RShasTokenPermission($RStoken, $propertyID, 'WRITE') || isPropertyVisible($RSuserID, $propertyID, $clientID))) {
    returnJsonMessage(403, $RSallowDebug ? 'No WRITE permission or visibility for property ' . $propertyID : 'No permissions to write the requested property');
}

if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $itemID)) {
    returnJsonMessage(403, $RSallowDebug ? 'Token customer scope does not allow access to item ' . $itemID : 'No permissions to write the requested item');
}

$hasYear = property_exists($requestBody, 'year');
$hasSeries = property_exists($requestBody, 'series');

$yearScope = null;
if ($hasYear) {
    $year = (string)$requestBody->year;
    if (!ctype_digit($year) || strlen($year) !== 4 || intval($year) < 1000 || intval($year) > 9998) {
        $RSallowDebug ? returnJsonMessage(400, 'year must be a four-digit year between 1000 and 9998') : returnJsonMessage(400, '');
    }

    $yearPropertyID = ParsePID($requestBody->yearPropertyID, $clientID);
    if (!is_numeric($yearPropertyID) || intval($yearPropertyID) <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid yearPropertyID') : returnJsonMessage(400, '');
    }
    $yearPropertyID = intval($yearPropertyID);
    $yearPropertyType = getPropertyType($yearPropertyID, $clientID);
    if (!in_array($yearPropertyType, array('date', 'datetime'), true)) {
        $RSallowDebug ? returnJsonMessage(400, 'yearPropertyID must identify a date or datetime property') : returnJsonMessage(400, '');
    }
    if (intval(getClientPropertyItemType($yearPropertyID, $clientID)) !== $itemTypeID) {
        $RSallowDebug ? returnJsonMessage(400, 'yearPropertyID must belong to the target item type') : returnJsonMessage(400, '');
    }
    if (!(RShasTokenPermission($RStoken, $yearPropertyID, 'READ') || isPropertyVisible($RSuserID, $yearPropertyID, $clientID))) {
        returnJsonMessage(403, $RSallowDebug ? 'No READ permission or visibility for year property ' . $yearPropertyID : 'No permissions to read all the scope properties requested');
    }

    $yearScope = array(
        'propertyID' => $yearPropertyID,
        'type' => $yearPropertyType,
        'year' => intval($year)
    );
}

$seriesScope = null;
if ($hasSeries) {
    if (is_array($requestBody->series) || is_object($requestBody->series) || is_bool($requestBody->series) || is_null($requestBody->series)) {
        $RSallowDebug ? returnJsonMessage(400, 'series must be a scalar value') : returnJsonMessage(400, '');
    }

    $seriesPropertyID = ParsePID($requestBody->seriesPropertyID, $clientID);
    if (!is_numeric($seriesPropertyID) || intval($seriesPropertyID) <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid seriesPropertyID') : returnJsonMessage(400, '');
    }
    $seriesPropertyID = intval($seriesPropertyID);
    $seriesPropertyType = getPropertyType($seriesPropertyID, $clientID);
    if (!isset($propertiesTables[$seriesPropertyType]) || in_array($seriesPropertyType, array('file', 'image'), true)) {
        $RSallowDebug ? returnJsonMessage(400, 'seriesPropertyID must identify an equality-filterable property') : returnJsonMessage(400, '');
    }
    if (intval(getClientPropertyItemType($seriesPropertyID, $clientID)) !== $itemTypeID) {
        $RSallowDebug ? returnJsonMessage(400, 'seriesPropertyID must belong to the target item type') : returnJsonMessage(400, '');
    }
    if (!(RShasTokenPermission($RStoken, $seriesPropertyID, 'READ') || isPropertyVisible($RSuserID, $seriesPropertyID, $clientID))) {
        returnJsonMessage(403, $RSallowDebug ? 'No READ permission or visibility for series property ' . $seriesPropertyID : 'No permissions to read all the scope properties requested');
    }

    $seriesScope = array(
        'propertyID' => $seriesPropertyID,
        'type' => $seriesPropertyType,
        'value' => replaceUtf8Characters((string)$requestBody->series)
    );
}

$customerScope = null;
if (RSisCustomerScopedToken($RStoken)) {
    $customerDependency = RSgetTokenCustomerDependencyProperty($RStoken, $itemTypeID, $clientID);
    if (!is_array($customerDependency)) {
        returnJsonMessage(403, $RSallowDebug ? 'Unable to resolve token customer scope for this item type' : 'No permissions to write the requested item');
    }
    $customerScope = array(
        'propertyID' => intval($customerDependency['ID']),
        'type' => $customerDependency['type'],
        'itemID' => intval(RSgetTokenCustomerItemID($RStoken))
    );
}

$lockName = RSgetNextIntegerLockName($clientID, $propertyID, $yearScope, $seriesScope, $customerScope);
$lockAcquired = false;
$failureCode = 0;
$failureDebugMessage = '';
$assignedValue = null;

try {
    $lockAcquired = RSacquireNextIntegerLock($lockName, 5);
    if (!$lockAcquired) {
        $failureCode = 503;
        $failureDebugMessage = 'The number sequence is busy; retry the request';
    } else {
        // Recheck while holding the sequence lock so the same target cannot be
        // assigned twice by concurrent requests.
        $currentValue = getItemPropertyValue($itemID, $propertyID, $clientID, 'integer', $itemTypeID);
        if (is_numeric($currentValue) && intval($currentValue) > 0) {
            $failureCode = 409;
            $failureDebugMessage = 'The requested item already has a positive value for this property';
        } else {
            $assignedValue = RSgetNextIntegerPropertyValue($clientID, $itemTypeID, $propertyID, $yearScope, $seriesScope, $customerScope);
            if ($assignedValue === false) {
                $failureCode = 500;
                $failureDebugMessage = 'Unable to calculate the next integer value';
            } else {
                $writeResult = setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $assignedValue, 'integer', $RSuserID);
                if ($writeResult !== 0) {
                    $failureCode = 500;
                    $failureDebugMessage = 'Unable to write the next integer value (code ' . intval($writeResult) . ')';
                }
            }
        }
    }
} catch (Throwable $exception) {
    RSError('nextInteger: ' . $exception->getMessage());
    $failureCode = 500;
    $failureDebugMessage = 'Unable to assign the next integer value';
} finally {
    if ($lockAcquired) {
        try {
            RSreleaseNextIntegerLock($lockName);
        } catch (Throwable $releaseException) {
            RSError('nextInteger: unable to release sequence lock: ' . $releaseException->getMessage());
        }
    }
}

if ($failureCode !== 0) {
    returnJsonMessage($failureCode, $RSallowDebug ? $failureDebugMessage : '');
}

returnJsonResponse(json_encode(array(
    'itemID' => $itemID,
    'propertyID' => $propertyID,
    'value' => intval($assignedValue)
)));

function verifyNextIntegerBodyContent($body)
{
    checkIsJsonObject($body);
    checkBodyContains($body, 'itemID');
    checkBodyContains($body, 'propertyID');
    checkStringIsInteger($body->itemID);

    global $RSallowDebug;
    if (intval($body->itemID) <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'itemID must be greater than zero') : returnJsonMessage(400, '');
    }
    if (!is_scalar($body->propertyID) || is_bool($body->propertyID) || trim((string)$body->propertyID) === '') {
        $RSallowDebug ? returnJsonMessage(400, 'propertyID must not be empty') : returnJsonMessage(400, '');
    }

    $hasYear = property_exists($body, 'year');
    $hasYearProperty = property_exists($body, 'yearPropertyID');
    if ($hasYear !== $hasYearProperty) {
        $RSallowDebug ? returnJsonMessage(400, 'year and yearPropertyID must be supplied together') : returnJsonMessage(400, '');
    }
    if ($hasYear && (
        !is_scalar($body->year) || is_bool($body->year)
        || !is_scalar($body->yearPropertyID) || is_bool($body->yearPropertyID)
        || trim((string)$body->yearPropertyID) === ''
    )) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid year scope') : returnJsonMessage(400, '');
    }

    $hasSeries = property_exists($body, 'series');
    $hasSeriesProperty = property_exists($body, 'seriesPropertyID');
    if ($hasSeries !== $hasSeriesProperty) {
        $RSallowDebug ? returnJsonMessage(400, 'series and seriesPropertyID must be supplied together') : returnJsonMessage(400, '');
    }
    if ($hasSeries && (
        !is_scalar($body->series) || is_bool($body->series)
        || !is_scalar($body->seriesPropertyID) || is_bool($body->seriesPropertyID)
        || trim((string)$body->seriesPropertyID) === ''
    )) {
        $RSallowDebug ? returnJsonMessage(400, 'Invalid series scope') : returnJsonMessage(400, '');
    }
}

function escapeNextIntegerMessageFragment($value)
{
    $encoded = json_encode((string)$value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $encoded === false ? '' : substr($encoded, 1, -1);
}
