<?php
//***************************************************
// Description:
//  Get items
//
// parameters:
// clientID   => the client ID
// itemTypeID   => the item type ID
// properties => the list of properties to return, separated by coma; the first element is always the MODE, that can be:
//               ALL        -> returns all the visible properties for the user, so is not necessary specifying more
//               IDS        -> returns the specified properties (identified by internal ID) that follow
//
// filters    => each entry is formed by the property ID, the value (encoded in base64) and the filter mode, separated by semicolon; entries are separated by coma
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Retrieve the needed variables from the request
$clientID       = $GLOBALS['RS_POST']['clientID'  ];
$properties     = $GLOBALS['RS_POST']['properties'];
$filters        = $GLOBALS['RS_POST']['filters'   ];
$orderBy        = $GLOBALS['RS_POST']['orderBy'   ];
$limit          = $GLOBALS['RS_POST']['limit'     ];
$join           = $GLOBALS['RS_POST']['join'      ];
$IDs            = $GLOBALS['RS_POST']['IDs'       ];

$extFilterRules = isset($GLOBALS['RS_POST']['extFilterRules']) ? $GLOBALS['RS_POST']['extFilterRules'] : '';
$itemTypeID     = isset($GLOBALS['RS_POST']['itemTypeID'    ]) ? $GLOBALS['RS_POST']['itemTypeID'    ] : '';
isset($GLOBALS['RS_POST']['orderPropertyID']) ? $orderPropertyID = $GLOBALS['RS_POST']['orderPropertyID'] : $orderPropertyID = "";

// prepare return properties array
$returnProperties = array();
$visibleProperties = array();

if ($properties == '') {
  // Return only the visible properties
  $visibleProperties = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);
  foreach ($visibleProperties as $property) {
    // add the property to return
    $returnProperties[] = array('ID' => $property, 'name' => $property, 'trName' => $property . 'trs');
  }
} else {
  // Return the defined and visible properties
  $properties        = explode(',', $properties);
  $itemTypeID        = getItemTypeIDFromProperties($properties, $clientID);
  $visibleProperties = getUserVisiblePropertiesIDs($itemTypeID, $clientID, $RSuserID);

  for ($i = 0; $i < count($properties); $i++) {

    $property_parsed = parsePID($properties[$i], $clientID);
    if (in_array($property_parsed, $visibleProperties)) {
      // add the property to return
      $returnProperties[] = array('ID' => $property_parsed, 'name' => $properties[$i], 'trName' => $properties[$i] . 'trs');
    }
  }
}

if ($itemTypeID == '') {
  RSReturnError("COULD NOT DETERMINE ITEM TYPE ID TO RETURN", "NOK");
}

//creck if need to get the order from a property and add to returned properties in that case
$returnOrder = 0;
if ($orderPropertyID != "") {
  $returnOrder = 1;
  if ($orderPropertyID != "0") {
    $propertyType = getPropertyType($orderPropertyID, $clientID);
    if (isSingleIdentifier($propertyType) || isMultiIdentifier($propertyType)) {
      $orderPropertyID_parsed = parsePID($orderPropertyID, $clientID);
      if (in_array($property_parsed, $visibleProperties) && array_search_ID($orderPropertyID_parsed, $returnProperties) === false) {
        $returnProperties[] = array('ID' => $orderPropertyID_parsed, 'name' => $orderPropertyID, 'trName' => $orderPropertyID . 'trs');
      }
    } else {
      $response['result'] = "NOK";
      $response['description'] = "ORDER PROPERTY MUST BE 0 (DEFAULT ORDER) OR A VALID IDENTIFIER(S) TYPE PROPERTY";
      RSReturnArrayResults($response, false);
    }
  }
}

// Prepare the filter properties array
$filterProperties = array();

if ($filters != '') {
  $filters = explode(',', $filters);

  foreach ($filters as $filter) {
    // get property data
    $filterArr = explode(';', $filter);
    if (count($filterArr) >= 2) {
      $propertyID = parsePID($filterArr[0], $clientID);

      // add the filter
      $filterProperties[] = array('ID' => $propertyID, 'value' => base64_decode($filterArr[1]));
      if (count($filterArr) == 3) {
        $filterProperties[count($filterProperties) - 1]['mode'] = $filterArr[2];
      }
    }
  }
}

// Get the list of filtered items
$results = array();
$results = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy, true, $limit, $IDs, $join, $returnOrder, true, $extFilterRules);

// Return results
if (is_string($results)) {
  RSReturnFileResults($results);
} else {
  RSReturnArrayQueryResults($results);
}
