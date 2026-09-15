<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS[$cstRS_POST][$cstClientID];
$accountID = $GLOBALS[$cstRS_POST]['accountID'];


// get the subaccounts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['subAccounts'], $clientID);

// get the subaccounts accountID property ID
$accountPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountAccountID'], $clientID);
$personalIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['subAccountPersonalID'], $clientID);




// Allocate within the parent account and keep creation inside the sequence lock.
$seriesScope = array('propertyID' => $accountPropertyID, 'type' => getPropertyType($accountPropertyID, $clientID), 'value' => $accountID);
$newSubAccountID = null;
$nextID = RSallocateNextIntegerPropertyValue($clientID, $itemTypeID, $personalIDPropertyID,
    function ($next) use ($clientID, $itemTypeID, $accountPropertyID, $accountID, $personalIDPropertyID, &$newSubAccountID) {
        $values = array(
            array('ID' => $accountPropertyID, 'value' => $accountID),
            array('ID' => $personalIDPropertyID, 'value' => $next)
        );
        $newSubAccountID = createItem($clientID, $values, $itemTypeID);
        // createItem does not report individual property-write failures.
        return $newSubAccountID > 0
            && getItemPropertyValue($newSubAccountID, $personalIDPropertyID, $clientID) == $next
            && getItemPropertyValue($newSubAccountID, $accountPropertyID, $clientID) == $accountID;
    }, null, $seriesScope);
if ($nextID === false) {
    $results['result'] = 'NOK';
    $results['description'] = 'ERROR CREATING ITEM';
    RSReturnArrayResults($results);
    exit;
}

$results['ID'] = $newSubAccountID;
$results['mainValue'] = getClientItemMainPropertyValue($newSubAccountID, $itemTypeID, $clientID);
$results['personalID'] = getPropertyValue($definitions['subAccountPersonalID'], $newSubAccountID, $clientID);

// Return results
RSReturnArrayResults($results);
