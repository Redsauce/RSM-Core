<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMbankCodes.php";

// definitions
$clientID     = $GLOBALS['RS_POST']['clientID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];

// get the item types
$itemTypeID           = getClientItemTypeIDRelatedWithByName($definitions['subAccounts'], $clientID);
$accountsItemTypeID   = getClientItemTypeIDRelatedWithByName($definitions['accounts'], $clientID);
$operationsItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['operations'], $clientID);
$conceptsItemTypeID   = getClientItemTypeIDRelatedWithByName($definitions['concepts'], $clientID);

// get the subaccount accountID
$accountID = getPropertyValue($definitions['subAccountAccountID'], $itemTypeID, $subAccountID, $clientID);

// get the account type
if (getPropertyValue($definitions['accountType'], $accountsItemTypeID, $accountID, $clientID) == '430') {
    // delete associated users
    $usersItemTypeID = getClientItemTypeIDRelatedWithByName($definitions['users'], $clientID);

    // build filter properties array
    $filterProperties = array();
    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['userSubAccountID'], $clientID), 'value' => $subAccountID);

    $useRSqueryResults = iqGetFilteredItemsIDs($usersItemTypeID, $clientID, $filterProperties, array());

    $users = array();
    while ($row = $useRSqueryResults->fetch_assoc()) {
        $users[] = $row['ID'];
    }

    if (!empty($users)) {
        // delete concepts
        deleteItems($usersItemTypeID, $clientID, implode(',', $users));
    }

    // also delete the relationships between the user and the modules
    RSquery('DELETE FROM rs_extranet_modules_users WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEM_ID IN (' . implode(',', $users) . ')');
}


// get subaccount operations
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['operationSubAccountID'], $clientID), 'value' => $subAccountID);

$operationsQueryResults = iqGetFilteredItemsIDs($operationsItemTypeID, $clientID, $filterProperties, array());

// get operations "related operations" property ID
$relatedOperationsPropertyID = getClientPropertyIDRelatedWithByName('operations.relatedOperations', $clientID);

$operations = array();
while ($row = $operationsQueryResults->fetch_assoc()) {
    $operations[] = $row['ID'];

    // get the operation related operations
    $filterProperties = array();
    $filterProperties[] = array('ID' => $relatedOperationsPropertyID, 'value' => $row['ID'], 'mode' => 'IN');

    $relatedOperations = iqGetFilteredItemsIDs($operationsItemTypeID, $clientID, $filterProperties, array());

    // delete the relationships
    while ($operation = $relatedOperations->fetch_assoc()) {
        removeIdentifier($row['ID'], $operationsItemTypeID, $operation['ID'], $relatedOperationsPropertyID, $clientID, $RSuserID);
    }
}

if (!empty($operations)) {
    // get operations concepts
    $filterProperties = array();
    $filterProperties[] = array('ID' => getClientPropertyIDRelatedWithByName($definitions['conceptOperationID'], $clientID), 'value' => implode(',', $operations), 'mode' => '<-IN');

    $conceptsQueryResults = iqGetFilteredItemsIDs($conceptsItemTypeID, $clientID, $filterProperties, array());

    $concepts = array();
    while ($row = $conceptsQueryResults->fetch_assoc()) {
        $concepts[] = $row['ID'];
    }

    if (!empty($concepts)) {
        // delete concepts
        deleteItems($conceptsItemTypeID, $clientID, implode(',', $concepts));
    }

    // delete operations
    deleteItems($operationsItemTypeID, $clientID, implode(',', $operations));
}

// finally delete the subaccount
deleteItem($itemTypeID, $subAccountID, $clientID);


$results['result'] = 'OK';

// Return results
RSreturnArrayResults($results);
