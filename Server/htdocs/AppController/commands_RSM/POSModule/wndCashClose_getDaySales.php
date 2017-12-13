<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Retrieve the variables
$clientID          = $GLOBALS['RS_POST']['clientID'         ];
$userID            = $GLOBALS['RS_POST']['userID'           ];
$lastClose         = $GLOBALS['RS_POST']['lastClose'        ];
$salesSubAccountID = $GLOBALS['RS_POST']['salesSubAccountID'];
$cashPaymentMethod = $GLOBALS['RS_POST']['cashPaymentMethod'];

if($lastClose == "") $lastClose="1970-1-1";

// get the cashRegisters item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get the properties
$totalPropertyID         = getClientPropertyID_RelatedWith_byName($definitions['operationTotal'            ], $clientID);
$payMethodPropertyID     = getClientPropertyID_RelatedWith_byName($definitions['operationPayMethod'        ], $clientID);
$subAccountIDPropertyID  = getClientPropertyID_RelatedWith_byName($definitions['operationSubAccountID'     ], $clientID);
$operationIDPropertyID   = getClientPropertyID_RelatedWith_byName($definitions['operationOperationID'      ], $clientID);
$invoiceDatePropertyID   = getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'      ], $clientID);
$relOperationsPropertyID = getClientPropertyID_RelatedWith_byName('operations.relatedOperations', $clientID);

// build the filter properties array
$filterProperties   = array();
$filterProperties[] = array('ID' => $invoiceDatePropertyID  , 'value' => $lastClose, 'mode' => 'SAME_OR_AFTER');
$filterProperties[] = array('ID' => $subAccountIDPropertyID , 'value' => $salesSubAccountID                   );
$filterProperties[] = array('ID' => $operationIDPropertyID  , 'value' => "0",        'mode' => '<>'           );
$filterProperties[] = array('ID' => $relOperationsPropertyID, 'value' => ""                                   );

// build the return properties array
$returnProperties   = array();
$returnProperties[] = array('ID' => $totalPropertyID    , 'name' => 'total');
$returnProperties[] = array('ID' => $payMethodPropertyID, 'name' => 'method');

// get the subaccounts
$salesOperations = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

// Return results
RSReturnQueryResults($salesOperations);
?>