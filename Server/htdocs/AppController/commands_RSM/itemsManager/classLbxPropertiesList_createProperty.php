<?php
//***************************************************
//Description:
//  Creates a new item property
//  ---> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID                                 = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['categoryID']) ? $categoryID                               = $GLOBALS['RS_POST']['categoryID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyName']) ? $newPropertyName            = base64_decode($GLOBALS['RS_POST']['propertyName']) : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyDescription']) ? $newPropertyDescription     = base64_decode($GLOBALS['RS_POST']['propertyDescription']) : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyAuditTrail']) ? $newPropertyAuditTrail                    = $GLOBALS['RS_POST']['propertyAuditTrail'] : dieWithError(400);
isset($GLOBALS['RS_POST']['propertyAuditTrailDescriptionRequired']) ? $newPropertyAuditTrailDescriptionRequired = $GLOBALS['RS_POST']['propertyAuditTrailDescriptionRequired'] : dieWithError(400);
isset($GLOBALS['RS_POST']['confirmDuplicated']) ? $confirmDuplicated                        = $GLOBALS['RS_POST']['confirmDuplicated'] : dieWithError(400);
isset($GLOBALS['RS_POST']['avoidDuplication']) ? $avoidDuplicateProperty                   = $GLOBALS['RS_POST']['avoidDuplication'] : dieWithError(400);
isset($GLOBALS['RS_POST']['searchable']) ? $isSearchableProperty                     = $GLOBALS['RS_POST']['searchable'] : $isSearchableProperty = '1';

$type = explode(';', $GLOBALS['RS_POST']['propertyType']);
$newPropertyType = $type[0];

// check default value match property type and set to type default value otherwise
$newPropertyDefaultValue = checkType(base64_decode($GLOBALS['RS_POST']['propertyDefaultVal']), $newPropertyType);

if ($clientID != '0' && $categoryID != '0') {
  // get the item type
  $itemTypeID = getClientCategoryItemType($categoryID, $clientID);
  if ($itemTypeID != '0') {

    //check property name exists
    if ($confirmDuplicated != "1") {
      $itemTypeProperties = getClientItemTypeProperties($itemTypeID, $clientID);
      foreach ($itemTypeProperties as $itemTypeProperty) {
        if ($itemTypeProperty['name'] == $newPropertyName) {

          $results['result'] = 'NOK';
          $results['description'] = 'NAME_ALREADY_EXISTS';
          // And write XML Response back to the application
          RSreturnArrayResults($results);
          // Terminate PHP execution
          exit;
        }
      }
    }

    $newPropertyID = getNextIdentification('rs_item_properties', 'RS_PROPERTY_ID', $GLOBALS['RS_POST']['clientID']);
    $newPropertyOrder = getGenericNext('rs_item_properties', 'RS_ORDER', array('RS_CLIENT_ID' => $GLOBALS['RS_POST']['clientID'], 'RS_CATEGORY_ID' => $GLOBALS['RS_POST']['categoryID']));

    if ((isSingleIdentifier($newPropertyType) || isMultiIdentifier($newPropertyType)) && (count($type) > 1)) {
      $referredItemType = $type[1];
    } else {
      $referredItemType = 'NULL';
    }

    // build create property query
    $theQuery = 'INSERT INTO rs_item_properties ' .
      '(RS_PROPERTY_ID, RS_CATEGORY_ID, RS_CLIENT_ID, RS_NAME, RS_TYPE, RS_DESCRIPTION, RS_ORDER, RS_DEFAULTVALUE, RS_REFERRED_ITEMTYPE, RS_AUDIT_TRAIL, RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED, RS_AVOID_DUPLICATION, RS_SEARCHABLE) ' .
      'VALUES ' .
      '(' . $newPropertyID . ',' . $categoryID . ',' . $clientID . ',"' . $newPropertyName . '","' . $newPropertyType . '","' . $newPropertyDescription . '",' . $newPropertyOrder . ',"' . $newPropertyDefaultValue . '",' . $referredItemType . ',' . $newPropertyAuditTrail . ',' . $newPropertyAuditTrailDescriptionRequired . ',' . $avoidDuplicateProperty . ',' . $isSearchableProperty . ')';


    // execute the query
    $result = RSquery($theQuery);

    if ($result) {

      // check if a list for the property was sent
      if ($GLOBALS['RS_POST']['propertyListID'] != '0') {

        // build the associate list query
        $theQuery = 'INSERT INTO rs_properties_lists ' .
          '(RS_PROPERTY_ID, RS_LIST_ID, RS_CLIENT_ID, RS_MULTIVALUES) ' .
          'VALUES ' .
          '(' . $newPropertyID . ',' . $GLOBALS['RS_POST']['propertyListID'] . ',' . $clientID . ',' . $GLOBALS['RS_POST']['propertyMultiVal'] . ')';

        // execute the query
        $result = RSquery($theQuery);
      }


      // get the main property
      $mainPropertyID = getMainPropertyID($itemTypeID, $clientID);

      if ($mainPropertyID == 0 && !isSingleIdentifier($newPropertyType) && !isMultiIdentifier($newPropertyType) && $newPropertyType != "image" && $newPropertyType != "file") {
        // set the property as main property
        $theQuery = 'UPDATE rs_item_types SET RS_MAIN_PROPERTY_ID = ' . $newPropertyID . ' WHERE RS_ITEMTYPE_ID = ' . $itemTypeID . ' AND RS_CLIENT_ID = ' . $clientID;

        // execute the query
        $result = RSquery($theQuery);
      }



      // insert the new property for the items that already exists
      $itemIDs = IQ_getItemIDs($itemTypeID, $clientID);

      if ($itemIDs && $itemIDs->num_rows > 0) {

        // Ensure property value match the defined property type and convert to default otherwise
        $newPropertyDefaultValue = enforcePropertyType($newPropertyDefaultValue, $clientID, $newPropertyID, $newPropertyType);

        $row = $itemIDs->fetch_assoc();

        // build the insert property query
        if ($newPropertyType == 'identifiers') {
          //for multiidentifiers insert also default order value
          $theQuery = 'INSERT INTO ' . $propertiesTables[$newPropertyType] . ' ' .
            '(RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_CLIENT_ID, RS_ORDER) ' .
            'VALUES ' .
            '(' . $itemTypeID . ',' . $row['ID'] . ',' . $newPropertyID . ',"' . $newPropertyDefaultValue . '",' . $clientID . ',"0")';

          while ($row = $itemIDs->fetch_assoc()) {
            $theQuery .= ',(' . $itemTypeID . ',' . $row['ID'] . ',' . $newPropertyID . ',"' . $newPropertyDefaultValue . '",' . $clientID . ',"0")';
          }
        } else {
          $theQuery = 'INSERT INTO ' . $propertiesTables[$newPropertyType] . ' ' .
            '(RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_DATA, RS_CLIENT_ID) ' .
            'VALUES ' .
            '(' . $itemTypeID . ',' . $row['ID'] . ',' . $newPropertyID . ',"' . $newPropertyDefaultValue . '",' . $clientID . ')';

          while ($row = $itemIDs->fetch_assoc()) {
            $theQuery .= ',(' . $itemTypeID . ',' . $row['ID'] . ',' . $newPropertyID . ',"' . $newPropertyDefaultValue . '",' . $clientID . ')';
          }
        }

        // execute the query
        $result = RSquery($theQuery);
      }

      $results['result'] = 'OK';
    } else {

      $results['result'] = 'NOK';
    }
  } else {

    $results['result'] = 'NOK';
  }
} else {

  $results['result'] = 'NOK';
}

// And write XML Response back to the application
RSreturnArrayResults($results);
