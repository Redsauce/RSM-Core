<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
isset($GLOBALS[$cstRS_POST][$cstClientID ]) ? $clientID = $GLOBALS[$cstRS_POST][$cstClientID] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['ticketID' ]) ? $ticketID = $GLOBALS[$cstRS_POST]['ticketID'] : dieWithError(400);

$RSuserID   = RSCheckUserAccess();

$itemTypeID = getClientItemTypeID_RelatedWith_byName("ticket", $clientID);

// get invoice.client ticketID and invoiceDate properties
$ticketIDPropertyID   = getClientPropertyID_RelatedWith_byName("ticket.ID"  , $clientID);
$ticketDatePropertyID = getClientPropertyID_RelatedWith_byName("ticket.date", $clientID);

// get invoice.client clientID property
// $invoiceClientIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['invoiceClientClientID'], $clientID);

// check if the ticket has been already generated
$currentTicketID   = getItemPropertyValue($ticketID, $ticketIDPropertyID  , $clientID);
$currentTicketDate = getItemPropertyValue($ticketID, $ticketDatePropertyID, $clientID);

if ($currentTicketID > 0 || $currentTicketDate != '') {
    // ticketID or invoiceDate already generated
    $results['result'     ] = 'NOK';
    $results['description'] = 'TICKET ID OR TICKET DATE ALREADY GENERATED';

    // Write XML Response back to the application
    RSReturnArrayResults($results);
}

$date = date('Y-m-d');
$nextID = RSallocateNextIntegerPropertyValue($clientID, $itemTypeID, $ticketIDPropertyID,
    function ($next) use ($clientID, $itemTypeID, $ticketID, $ticketIDPropertyID, $ticketDatePropertyID, $date, $RSuserID) {
        // Keep this command's existing number/date rules inside the allocation lock.
        if (getItemPropertyValue($ticketID, $ticketIDPropertyID, $clientID) > 0
            || getItemPropertyValue($ticketID, $ticketDatePropertyID, $clientID) != '') return false;
        if (setPropertyValueByID($ticketIDPropertyID, $itemTypeID, $ticketID, $clientID, $next, '', $RSuserID) !== 0) return false;
        return setPropertyValueByID($ticketDatePropertyID, $itemTypeID, $ticketID, $clientID, $date, '', $RSuserID) === 0;
    });
if ($nextID === false) {
    $results['result'] = 'NOK';
    $results['description'] = 'ERROR ASSIGNING NUMBER AND DATE';
    RSReturnArrayResults($results);
    exit;
}

$results['result'     ] = 'OK';
$results['ID'         ] = $nextID;
$results['date'       ] = $date;
$results['ticketIDpID'] = $ticketIDPropertyID;

// Return results
RSReturnArrayResults($results);
