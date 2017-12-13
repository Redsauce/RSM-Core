<?php
//***************************************************
// Description:
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Parameters validation
isset($GLOBALS['RS_POST']['clientID'          ]) ? $clientID          =               $GLOBALS['RS_POST']['clientID'          ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['itemTypeID'        ]) ? $itemTypeID        =               $GLOBALS['RS_POST']['itemTypeID'        ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['parentID'          ]) ? $parentID          =               $GLOBALS['RS_POST']['parentID'          ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['parentItemTypeID'  ]) ? $parentItemTypeID  =               $GLOBALS['RS_POST']['parentItemTypeID'  ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyID'        ]) ? $propertyID        =               $GLOBALS['RS_POST']['propertyID'        ]  : dieWithError(400);

// prepare arrays
$filterProperties   = array();

$returnProperties   = array();
$returnProperties[] = array('ID' => getMainPropertyID($itemTypeID, $clientID), 'name' => 'name');

$descendants = getDescendantsLevel($clientID, $itemTypeID, array($itemTypeID));

// check if is recursive itemtype and get recursive parent in this case
$recursivePropertyPos = array_search_ID($itemTypeID, $descendants, "itemTypeID");
if ($recursivePropertyPos !== false) $returnProperties[] = array('ID' => $descendants[$recursivePropertyPos]['propertyID'], 'name' => 'recursiveProperty');

if ($propertyID != '0') {
    //not base itemtype so add parent filter and return property order
    $filterProperties[] = array('ID' => $propertyID, 'value' => $parentID, 'mode' => 'IN');
    $returnProperties[] = array('ID' => $propertyID, 'name' => 'parentID');
}

// get items
$auxArr = array();
$result = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties,'','','',"AND",$auxArr,'1');

$results = array();

if ($result) {
    while ($item = $result->fetch_assoc()) {
        // check if it is a recursive itemtype and has a parent of its own itemtype in another branch (don't add in that case)
        if (!isset($item['recursiveProperty'])) $item['recursiveProperty'] = '';

        // We must explode $item['recursiveProperty'] because it could be multiidentifier
        $recursiveProperties = explode(",", $item['recursiveProperty']);
        foreach($recursiveProperties as $recursiveProperty) {
            if ($recursiveProperty == '' || $recursiveProperty == "0" || ($recursiveProperty == $parentID && $itemTypeID == $parentItemTypeID)) {
                //for mutiple value multi identifiers, take only relevant order value
                if ($propertyID != '0' && strpos($item['parentID'], ',') !== false) {
                    $orders = explode(',', $item['parentID_ord']);
                    $item['parentID_ord'] = $orders[array_search($parentID, explode(',', $item['parentID']))];
                }
                if (!is_numeric($item['parentID_ord'])) $item['parentID_ord'] = "0";
                $results[] = $item;
            }
        }
    }
}

// Return data
RSReturnArrayQueryResults($results);

?>
