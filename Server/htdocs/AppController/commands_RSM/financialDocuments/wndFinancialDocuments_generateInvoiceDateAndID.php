<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
isset($GLOBALS[$cstRS_POST]['clientID'  ]) ? $clientID    = $GLOBALS[$cstRS_POST]['clientID'  ] : dieWithError(400);
isset($GLOBALS[$cstRS_POST]['invoiceID' ]) ? $invoiceIDs  = explode(",", $GLOBALS[$cstRS_POST]['invoiceID' ]) : dieWithError(400);
$RSuserID   = RSCheckUserAccess();

$itemTypeID = getClientItemTypeID_RelatedWith_byName("invoice.client", $clientID);

// get invoice.client invoiceID , invoiceDate, serie and defaultInvoiceAddress properties
$invoiceIDPropertyID             = getClientPropertyID_RelatedWith_byName("invoice.client.invoiceID", $clientID);
$invoiceDatePropertyID           = getClientPropertyID_RelatedWith_byName("invoice.client.invoiceDate", $clientID);
$invoiceSeriePropertyID          = getClientPropertyID_RelatedWith_byName("invoice.client.serie", $clientID);
$defaultInvoiceAddressPropertyID = getClientPropertyID_RelatedWith_byName("crmAccounts.default.invoice.address", $clientID);

// get invoice.client clientID property
$invoiceClientIDPropertyID = getClientPropertyID_RelatedWith_byName("invoice.client.clientID", $clientID);

foreach ($invoiceIDs as $invoiceID) {
  // check if the invoice ID was already generated
  $currentInvoiceID   = getItemPropertyValue($invoiceID, $invoiceIDPropertyID  , $clientID);
  $currentInvoiceDate = getItemPropertyValue($invoiceID, $invoiceDatePropertyID, $clientID);

  if ($currentInvoiceID > 0 || $currentInvoiceDate != '') {
    // invoiceID or invoiceDate already generated
    $results['result'     ] = 'NOK';
    $results['description'] = 'INVOICE ID OR INVOICE DATE ALREADY GENERATED';

    // Write XML Response back to the application
    RSReturnArrayResults($results);
  }

  // get resetIDwithNewYear global variable value
  $theQuery = 'SELECT RS_VALUE AS "value" FROM rs_globals WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_NAME = "invoices.resetIDwithNewYear"';

  // execute query
  $result = RSQuery($theQuery);

  $row = $result->fetch_assoc();
  $date = date('Y-m-d');
  $yearScope = null;
  if (isset($row['value']) && $row['value'] == '1') {
    $yearScope = array('propertyID' => $invoiceDatePropertyID, 'type' => getPropertyType($invoiceDatePropertyID, $clientID), 'year' => intval(substr($date, 0, 4)));
  }

  // Preserve the legacy rule: an empty series does not restrict the maximum.
  $currentInvoiceSerie = getItemPropertyValue($invoiceID, $invoiceSeriePropertyID, $clientID);
  $seriesScope = $currentInvoiceSerie !== ''
    ? array('propertyID' => $invoiceSeriePropertyID, 'type' => getPropertyType($invoiceSeriePropertyID, $clientID), 'value' => $currentInvoiceSerie)
    : null;
  $nextID = RSallocateNextIntegerPropertyValue($clientID, $itemTypeID, $invoiceIDPropertyID,
      function ($next) use ($clientID, $itemTypeID, $invoiceID, $invoiceIDPropertyID, $invoiceDatePropertyID, $date, $RSuserID) {
          // Keep this command's existing number/date rules inside the allocation lock.
          if (getItemPropertyValue($invoiceID, $invoiceIDPropertyID, $clientID) > 0
              || getItemPropertyValue($invoiceID, $invoiceDatePropertyID, $clientID) != '') return false;
          if (setPropertyValueByID($invoiceIDPropertyID, $itemTypeID, $invoiceID, $clientID, $next, '', $RSuserID) !== 0) return false;
          return setPropertyValueByID($invoiceDatePropertyID, $itemTypeID, $invoiceID, $clientID, $date, '', $RSuserID) === 0;
      }, $yearScope, $seriesScope);
  if ($nextID === false) {
      $results['result'] = 'NOK';
      $results['description'] = 'ERROR ASSIGNING NUMBER AND DATE';
      RSReturnArrayResults($results);
      exit;
  }

  // get the clientID in the invoiceClient
  $invoiceClientID = getItemPropertyValue($invoiceID, $invoiceClientIDPropertyID, $clientID);

  // Get the itemID of the default invoice address
  $currentDefaultInvoiceAddressID = getItemPropertyValue($invoiceClientID, $defaultInvoiceAddressPropertyID, $clientID);

  // If the item 'default Invoice Address' exists
  if ($currentDefaultInvoiceAddressID <> 0) {
      // Set the invoice address, only if each App property is related with a user property    
      if (getClientPropertyID_RelatedWith_byName("crmAdresses.address",  $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName("invoice.client.billingAddress",  $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName("crmAdresses.address",  $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName("crmAdresses.city",     $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName("invoice.client.billingCity",     $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName("crmAdresses.city",     $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName("crmAdresses.country",  $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName("invoice.client.billingCountry",  $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName("crmAdresses.country",  $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName("crmAdresses.postcode", $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName("invoice.client.billingPostCode", $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName("crmAdresses.postcode", $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName("crmAdresses.province", $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName("invoice.client.billingProvince", $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName("crmAdresses.province", $clientID), $clientID), '', $RSuserID);
  }

  $results['result'      ] = 'OK';
  $results['ID'          ] = $nextID;
  $results['date'        ] = $date;
  $results['invoiceIDpID'] = $invoiceIDPropertyID;
}

// Return results
RSReturnArrayResults($results);
