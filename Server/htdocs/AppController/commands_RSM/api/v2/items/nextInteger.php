<?php
// ****************************************************************************************
// Description:
//   Atomically assign the next positive integer and today's UTC date.
//
// HOW THE SEQUENCE WORKS:
//   - propertyID is the integer property that receives the generated number.
//   - filterPropertyID is a date property on the same item type. The endpoint
//     writes gmdate('Y-m-d') to it; callers do not send a date or year.
//   - Within the current UTC year, the next number is MAX(propertyID) + 1.
//   - If the current year has no numbered items, the endpoint uses the maximum
//     from the newest earlier year that has data. If no year has data, it uses 1.
//   - Optional series and customer-token restrictions are applied before the
//     maximum is calculated.
//
// REQUEST BODY (JSON OBJECT):
// {
//   "itemID": "123",
//   "propertyID": "invoice.client.invoiceID",
//   "filterPropertyID": "invoice.client.invoiceDate",
//   "series": "A",                               // optional with seriesPropertyID
//   "seriesPropertyID": "invoice.client.serie"
// }
//
// EXAMPLES (assuming the current UTC year is 2029):
//   2029 values [10, 14, 12]              => assigns 15 and date 2029-MM-DD.
//   2029 empty; 2028 values [110, 111]    => assigns 112 and date 2029-MM-DD.
//   2029/2028 empty; 2027 maximum is 80   => assigns 81 and date 2029-MM-DD.
//   No current or earlier period has data => assigns 1 and today's UTC date.
//   2029 values [1, 2, 3, 4, 1]           => assigns 5 (maximum, not last item).
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

$hasSeries = property_exists($requestBody, 'series');

$filterPropertyID = ParsePID($requestBody->filterPropertyID, $clientID);
if (!is_numeric($filterPropertyID) || intval($filterPropertyID) <= 0) {
    $RSallowDebug ? returnJsonMessage(400, 'Invalid filterPropertyID') : returnJsonMessage(400, '');
}
$filterPropertyID = intval($filterPropertyID);
$filterPropertyType = getPropertyType($filterPropertyID, $clientID);
if ($filterPropertyType !== 'date') {
    $RSallowDebug ? returnJsonMessage(400, 'filterPropertyID must identify a date property') : returnJsonMessage(400, '');
}
if (intval(getClientPropertyItemType($filterPropertyID, $clientID)) !== $itemTypeID) {
    $RSallowDebug ? returnJsonMessage(400, 'filterPropertyID must belong to the target item type') : returnJsonMessage(400, '');
}
if (!(RShasTokenPermission($RStoken, $filterPropertyID, 'READ') || isPropertyVisible($RSuserID, $filterPropertyID, $clientID))) {
    returnJsonMessage(403, $RSallowDebug ? 'No READ permission or visibility for filter property ' . $filterPropertyID : 'No permissions to read the requested filter property');
}
if (!(RShasTokenPermission($RStoken, $filterPropertyID, 'WRITE') || isPropertyVisible($RSuserID, $filterPropertyID, $clientID))) {
    returnJsonMessage(403, $RSallowDebug ? 'No WRITE permission or visibility for filter property ' . $filterPropertyID : 'No permissions to write the requested filter property');
}

$periodLockScope = array(
    'propertyID' => $filterPropertyID,
    'type' => 'date',
    'year' => 0,
    'fallbackToPreviousPeriod' => true
);

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

$lockName = RSgetNextIntegerLockName($clientID, $propertyID, $periodLockScope, $seriesScope, $customerScope);
$lockAcquired = false;
$failureCode = 0;
$failureDebugMessage = '';
$assignedValue = null;
$transactionStarted = false;
$currentDate = null;
$yearScope = null;

try {
    $lockAcquired = RSacquireNextIntegerLock($lockName, 5);
    if (!$lockAcquired) {
        $failureCode = 503;
        $failureDebugMessage = 'The number sequence is busy; retry the request';
    } else {
        // Resolve the date after acquiring the year-independent rolling lock,
        // so a request crossing UTC midnight uses the actual assignment date.
        $currentDate = gmdate('Y-m-d');
        $yearScope = array(
            'propertyID' => $filterPropertyID,
            'type' => 'date',
            'year' => intval(substr($currentDate, 0, 4)),
            'fallbackToPreviousPeriod' => true
        );
        if (!$mysqli->begin_transaction()) {
            $failureCode = 500;
            $failureDebugMessage = 'Unable to start the next integer transaction';
        } else {
            $transactionStarted = true;

            // Recheck while holding the sequence lock and transaction so the
            // number, audit trail, and update bookkeeping succeed or roll back
            // together.
            $currentValue = getItemPropertyValue($itemID, $propertyID, $clientID, 'integer', $itemTypeID);
            $currentFilterDate = getItemPropertyValue($itemID, $filterPropertyID, $clientID, 'date', $itemTypeID);
            if ((is_numeric($currentValue) && intval($currentValue) > 0) || ($currentFilterDate !== '' && $currentFilterDate !== null)) {
                $failureCode = 409;
                $failureDebugMessage = 'The requested item already has a generated number or filter date';
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
                    } elseif (setPropertyValueByID($filterPropertyID, $itemTypeID, $itemID, $clientID, $currentDate, 'date', $RSuserID) !== 0) {
                        $failureCode = 500;
                        $failureDebugMessage = 'Unable to write the current UTC filter date';
                    } elseif (!$mysqli->commit()) {
                        $failureCode = 500;
                        $failureDebugMessage = 'Unable to commit the next integer value';
                    } else {
                        $transactionStarted = false;
                    }
                }
            }
        }
    }
} catch (Throwable $exception) {
    RSError('nextInteger: ' . $exception->getMessage());
    $failureCode = 500;
    $failureDebugMessage = 'Unable to assign the next integer value';
} finally {
    try {
        if ($transactionStarted) {
            $mysqli->rollback();
            $transactionStarted = false;
        }
    } catch (Throwable $rollbackException) {
        RSError('nextInteger: unable to roll back transaction: ' . $rollbackException->getMessage());
    }

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
    'value' => intval($assignedValue),
    'filterPropertyID' => $filterPropertyID,
    'date' => $currentDate
)));

function verifyNextIntegerBodyContent($body)
{
    checkIsJsonObject($body);
    checkBodyContains($body, 'itemID');
    checkBodyContains($body, 'propertyID');
    checkBodyContains($body, 'filterPropertyID');
    checkStringIsInteger($body->itemID);

    global $RSallowDebug;
    if (intval($body->itemID) <= 0) {
        $RSallowDebug ? returnJsonMessage(400, 'itemID must be greater than zero') : returnJsonMessage(400, '');
    }
    if (!is_scalar($body->propertyID) || is_bool($body->propertyID) || trim((string)$body->propertyID) === '') {
        $RSallowDebug ? returnJsonMessage(400, 'propertyID must not be empty') : returnJsonMessage(400, '');
    }
    if (!is_scalar($body->filterPropertyID) || is_bool($body->filterPropertyID) || trim((string)$body->filterPropertyID) === '') {
        $RSallowDebug ? returnJsonMessage(400, 'filterPropertyID must not be empty') : returnJsonMessage(400, '');
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
