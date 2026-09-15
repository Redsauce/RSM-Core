<?php
// POST {"itemTypeID":"98","itemID":"123"}: one copy, configured properties only.
require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
handleApiCorsPreflight('POST');
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('POST');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';

$body = getRequestBody();
if (!is_object($body) || !isset($body->itemTypeID, $body->itemID)
    || count(array_diff(array_keys(get_object_vars($body)), array('itemTypeID', 'itemID'))) > 0
    || (!is_string($body->itemTypeID) && !is_int($body->itemTypeID))
    || trim((string)$body->itemTypeID) === ''
    || (!is_string($body->itemID) && !is_int($body->itemID))
    || !ctype_digit((string)$body->itemID)
    || filter_var(ltrim((string)$body->itemID, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
    returnJsonMessage(400, $RSallowDebug ? 'Expected itemTypeID and one positive integer itemID' : '');
}

$RStoken = getRStoken();
$clientID = RSclientFromToken($RStoken);
$RSuserID = getRSuserID();
$resolvedItemTypeID = parseITID($body->itemTypeID, $clientID);
if (!ctype_digit((string)$resolvedItemTypeID)
    || filter_var(ltrim((string)$resolvedItemTypeID, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
    returnJsonMessage(400, $RSallowDebug ? 'Invalid itemTypeID' : '');
}
$itemTypeID = intval($resolvedItemTypeID);
$itemID = intval($body->itemID);
if ($itemTypeID <= 0) returnJsonMessage(400, $RSallowDebug ? 'Invalid itemTypeID' : '');
if (!verifyItemExists($itemID, $itemTypeID, $clientID)) {
    returnJsonMessage(404, $RSallowDebug ? 'Source item does not exist' : '');
}

$failureCode = 500;
$failureMessage = 'Unable to duplicate item';
$newItemID = null;
$transactionStarted = false;
try {
    if (!$mysqli->begin_transaction()) throw new RuntimeException('Unable to start duplication transaction');
    $transactionStarted = true;
    if (!RSlockItemTypeForDuplication($itemTypeID, $clientID)) throw new RuntimeException('Unable to lock item type');
    if (!verifyItemExists($itemID, $itemTypeID, $clientID)) {
        $failureCode = 404;
        throw new RuntimeException('Source item no longer exists');
    }

    // Pass exactly the authorized metadata to duplicateItem, including an empty list.
    $properties = getClientItemTypeProperties($itemTypeID, $clientID, 1, true);
    $permissionIDs = array_column($properties, 'id');
    if (count($permissionIDs) === 0) $permissionIDs = array(getMainPropertyID($itemTypeID, $clientID));
    foreach ($permissionIDs as $propertyID) {
        if (intval($propertyID) <= 0
            || !(RShasTokenPermission($RStoken, $propertyID, 'READ') || isPropertyVisible($RSuserID, $propertyID, $clientID))
            || !(RShasTokenPermission($RStoken, $propertyID, 'CREATE') || isPropertyVisible($RSuserID, $propertyID, $clientID))) {
            $failureCode = 403;
            $failureMessage = 'No permission to duplicate all eligible properties';
            throw new RuntimeException($failureMessage);
        }
    }
    if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $itemID)) {
        $failureCode = 403;
        $failureMessage = 'Source item is outside token customer scope';
        throw new RuntimeException($failureMessage);
    }
    if (RSisCustomerScopedToken($RStoken)) {
        $dependencyID = RSgetTokenCustomerDependencyPropertyID($RStoken, $itemTypeID, $clientID);
        if (!$dependencyID || !in_array($dependencyID, array_column($properties, 'id'))) {
            $failureCode = 403;
            $failureMessage = 'Customer dependency cannot be excluded from the copy';
            throw new RuntimeException($failureMessage);
        }
    }

    $copiedItems = array();
    $itemTypeProperties = array($itemTypeID => $properties);
    $newItemID = duplicateItem($itemTypeID, $itemID, $clientID, 1, array(), $copiedItems, $itemTypeProperties);
    if (!is_int($newItemID) || $newItemID <= 0) throw new RuntimeException('Copy failed');
    if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $newItemID)) {
        $failureCode = 403;
        $failureMessage = 'Copied item is outside token customer scope';
        throw new RuntimeException($failureMessage);
    }
    if (!$mysqli->commit()) throw new RuntimeException('Unable to commit copy');
    $transactionStarted = false;
} catch (Throwable $exception) {
    RSError('duplicate item: ' . $exception->getMessage());
    $newItemID = null;
} finally {
    if ($transactionStarted) $mysqli->rollback();
}

if ($newItemID === null) returnJsonMessage($failureCode, $RSallowDebug ? $failureMessage : '');
returnJsonResponse(json_encode(array('itemTypeID' => $itemTypeID, 'sourceItemID' => $itemID, 'newItemID' => $newItemID)));
