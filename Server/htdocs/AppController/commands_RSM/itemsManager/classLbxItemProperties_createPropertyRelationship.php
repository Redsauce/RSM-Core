<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Now we build the query

// Check types compatibility
$clientPropertyType = getPropertyType($GLOBALS['RS_POST']['propertyClientID'], $GLOBALS['RS_POST']['clientID']);
$appPropertyType = getAppPropertyType($GLOBALS['RS_POST']['propertyAppID']);


if ($clientPropertyType == $appPropertyType) {

    if (isSingleIdentifier($clientPropertyType) || isMultiIdentifier($clientPropertyType)) {
        // check if the identifier property points to some itemtype
        $query = RSquery('SELECT RS_REFERRED_ITEMTYPE FROM rs_item_properties WHERE RS_CLIENT_ID = ' . $GLOBALS['RS_POST']['clientID'] . ' AND RS_PROPERTY_ID = ' . $GLOBALS['RS_POST']['propertyClientID']);

        $result = $query->fetch_assoc();

        if ($result['RS_REFERRED_ITEMTYPE'] != '0' && $result['RS_REFERRED_ITEMTYPE'] != null) {
            $response['compatible'] = 'false';
            $response['reason'] = 'identifier already defined';

            // Write XML Response back to the application
            RSreturnArrayResults($response);
            exit;
        }
    }

    // Looking for previous system item relationship
    $startQuery1 = RSquery("SELECT RS_PROPERTY_ID     AS 'oldPropertyClientID' FROM rs_property_app_relations WHERE RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "' AND RS_PROPERTY_APP_ID = '" . $GLOBALS['RS_POST']['propertyAppID'] . "'");
    $startQuery2 = RSquery("SELECT RS_PROPERTY_APP_ID AS 'oldPropertyAppID'    FROM rs_property_app_relations WHERE RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "' AND RS_PROPERTY_ID = '"    . $GLOBALS['RS_POST']['propertyClientID'] . "'");

    if ($startQuery1 && $startQuery1->num_rows > 0) {
        $row = $startQuery1->fetch_assoc();
        $oldPropertyClientID = $row['oldPropertyClientID'];
    } else {
        $oldPropertyClientID = '0';
    }

    if ($startQuery2 && $startQuery2->num_rows > 0) {
        $row = $startQuery2->fetch_assoc();
        $oldPropertyAppID = $row['oldPropertyAppID'];
    } else {
        $oldPropertyAppID = '0';
    }

    // Delete previous relationships
    $theQuery = RSquery("DELETE FROM rs_property_app_relations WHERE RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "' AND RS_PROPERTY_APP_ID = '" . $GLOBALS['RS_POST']['propertyAppID'] . "'");

    $theQuery = RSquery("DELETE FROM rs_property_app_relations WHERE RS_CLIENT_ID = '" . $GLOBALS['RS_POST']['clientID'] . "' AND RS_PROPERTY_ID = '" . $GLOBALS['RS_POST']['propertyClientID'] . "'");

    // Insert new relationship
    $theQuery = "INSERT INTO rs_property_app_relations (RS_PROPERTY_ID, RS_CLIENT_ID, RS_PROPERTY_APP_ID, RS_MODIFIED_DATE) VALUES ('" . $GLOBALS['RS_POST']['propertyClientID'] . "', '" . $GLOBALS['RS_POST']['clientID'] . "', '" . $GLOBALS['RS_POST']['propertyAppID'] . "', NOW())";

    // Query the database
    $results = RSquery($theQuery);

    $response['compatible'] = 'true';
    $response['oldPropertyClientID'] = $oldPropertyClientID;
    $response['oldPropertyAppID'] = $oldPropertyAppID;
} else {
    // incompatible types
    $response['compatible'] = 'false';
}

// Write XML Response back to the application
RSreturnArrayResults($response);
