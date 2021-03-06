<?php
// Database connection startup
require_once '../utilities/RSdatabase.php';
require_once '../utilities/RSMitemsManagement.php';

// Definitions
isset($GLOBALS['RS_POST']['clientID'  ]) ? $clientID    = $GLOBALS['RS_POST']['clientID'  ] : dieWithError(400);
isset($GLOBALS['RS_POST']['invoiceID' ]) ? $invoiceIDs  = explode(",", $GLOBALS['RS_POST']['invoiceID' ]) : dieWithError(400);
$RSuserID   = RSCheckUserAccess();

$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['invoiceClient'], $clientID);

// get invoice.client invoiceID , invoiceDate and defaultInvoiceAddress properties
$invoiceIDPropertyID             = getClientPropertyID_RelatedWith_byName($definitions['invoiceClientInvoiceID'  ], $clientID);
$invoiceDatePropertyID           = getClientPropertyID_RelatedWith_byName($definitions['invoiceClientInvoiceDate'], $clientID);
$defaultInvoiceAddressPropertyID = getClientPropertyID_RelatedWith_byName($definitions['crmAccountsDefaultInvoiceAddress'], $clientID);

// get invoice.client clientID property
$invoiceClientIDPropertyID = getClientPropertyID_RelatedWith_byName($definitions['invoiceClientClientID'], $clientID);

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

  // build filter properties array
  $filterProperties = array();

  $row = $result->fetch_assoc();

  if (isset($row['value']) && ($row['value'] == '1')) {
    $filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y') - 1) . '-12-31', 'mode' => 'AFTER');
    $filterProperties[] = array('ID' => $invoiceDatePropertyID, 'value' => (date('Y') + 1) . '-01-01', 'mode' => 'BEFORE');
  }

  // build return properties array
  $returnProperties   = array();
  $returnProperties[] = array('ID' => $invoiceIDPropertyID, 'name' => 'invoiceID');

  // get current year's invoices
  $currentYearInvoices = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

  $maxID = 0;
    
  if ($currentYearInvoices) {
    while ($row = $currentYearInvoices->fetch_assoc()) if ($row['invoiceID'] > $maxID) $maxID = $row['invoiceID'];
  }

  // update invoiceID property
  setPropertyValueByID($invoiceIDPropertyID, $itemTypeID, $invoiceID, $clientID, $maxID + 1, '', $RSuserID);

  // update invoiceDate property
  setPropertyValueByID($invoiceDatePropertyID, $itemTypeID, $invoiceID, $clientID, date('Y-m-d'), '', $RSuserID);

  // get the clientID in the invoiceClient
  $invoiceClientID = getItemPropertyValue($invoiceID, $invoiceClientIDPropertyID, $clientID);

  // Get the itemID of the default invoice address
  $currentDefaultInvoiceAddressID = getItemPropertyValue($invoiceClientID, $defaultInvoiceAddressPropertyID, $clientID);

  // If the item 'default Invoice Address' exists
  if ($currentDefaultInvoiceAddressID <> 0) {
      // Set the invoice address, only if each App property is related with a user property    
      if (getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.address'],  $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName($definitions['invoiceClientBillingAddress' ],  $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.address'],  $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.city'],     $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName($definitions['invoiceClientBillingCity' ],     $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.city'],     $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.country'],  $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName($definitions['invoiceClientBillingCountry' ],  $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.country'],  $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.postcode'], $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName($definitions['invoiceClientBillingPostCode' ], $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.postcode'], $clientID), $clientID), '', $RSuserID);
      if (getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.province'], $clientID) <> 0) setPropertyValueByID(getClientPropertyID_RelatedWith_byName($definitions['invoiceClientBillingProvince' ], $clientID), $itemTypeID, $invoiceID, $clientID, getItemPropertyValue($currentDefaultInvoiceAddressID, getClientPropertyID_RelatedWith_byName($definitions['crmAdresses.province'], $clientID), $clientID), '', $RSuserID);
  }

  $results['result'      ] = 'OK';
  $results['ID'          ] = $maxID + 1;
  $results['date'        ] = date('Y-m-d');
  $results['invoiceIDpID'] = $invoiceIDPropertyID;
}

// Return results
RSReturnArrayResults($results);
?>
