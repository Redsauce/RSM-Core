<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
isset($GLOBALS['RS_POST']['clientID' ]) ? $clientID = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['ticketID' ]) ? $ticketID = $GLOBALS['RS_POST']['ticketID'] : dieWithError(400);

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

// build return properties array
$returnProperties   = array();
$returnProperties[] = array('ID' => $ticketIDPropertyID, 'name' => 'ticketID');

// get current year's invoices
$currentYearTickets = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

$maxID = 0;

if ($currentYearTickets) {
    while ($row = $currentYearTickets->fetch_assoc()) {
        if ($row['ticketID'] > $maxID) {
            $maxID = $row['ticketID'];
        }
    }
}

// update ticketID property
setPropertyValueByID($ticketIDPropertyID, $itemTypeID, $ticketID, $clientID, $maxID + 1, '', $RSuserID);

// update invoiceDate property
setPropertyValueByID($ticketDatePropertyID, $itemTypeID, $ticketID, $clientID, date('Y-m-d'), '', $RSuserID);

$results['result'     ] = 'OK';
$results['ID'         ] = $maxID + 1;
$results['date'       ] = date('Y-m-d');
$results['ticketIDpID'] = $ticketIDPropertyID;

// Return results
RSReturnArrayResults($results);
