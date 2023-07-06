<?php
//***************************************************
//DESCRIPTION:
//     Replace the items of a property (propertyIDend) based on another (propertyIDstart) of the same itemType.
//     The script tries to adapt data types during replacement.
//     The user must check, for example by passing text to dates, that the final format is correct.
//     In the case of multiIdentifiers to Identifiers: only multiIdentifiers composed by one element will be replicated.

//INPUT: the IDs of the origin and destiny itemProperties.
//     propertyIDstart: This is the propertyID with the replacement data
//     propertyIDend  : This is the propertyID with the items that will be replaced

//OUTPUT:
//     It returns a recordset with and element called 'result' that can be 'OK' or 'NOK'.
//     If result is 'NOK' also the property 'description' exists and describes the error.
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMmediaManagement.php";

$propertyIDstart    = $GLOBALS['RS_POST']['propertyIDstart'];
$propertyIDend      = $GLOBALS['RS_POST']['propertyIDend'];
$clientID           = $GLOBALS['RS_POST']['clientID'];
$userID             = RSCheckUserAccess();

// The user must to have permission to access to both properties
if (isPropertyVisible($userID, $propertyIDstart, $clientID) && isPropertyVisible($userID, $propertyIDend, $clientID)) {
    // The two properties must belong to the same property
    if (getClientPropertyItemType($propertyIDstart, $clientID) == getClientPropertyItemType($propertyIDend, $clientID)) {
        // Get itemTypes
        $propertyTypeStart          = getPropertyType($propertyIDstart, $clientID);
        $propertyTypeEnd            = getPropertyType($propertyIDend, $clientID);
        $equivalentTypeMysqlStart   = typeMySQL($propertyTypeStart);
        $equivalentTypeMysqlDestiny = typeMySQL($propertyTypeEnd);

        // CONVERSION CASES:
        // Any permutation between: text, longtext, integer, float, date, datetime
        if (($propertyTypeStart == 'integer' || $propertyTypeStart == 'text' || $propertyTypeStart == 'longtext' || $propertyTypeStart == 'float' || $propertyTypeStart == 'date' || $propertyTypeStart == 'datetime') && ($propertyTypeEnd == 'integer' || $propertyTypeEnd == 'text' || $propertyTypeEnd == 'longtext' || $propertyTypeEnd == 'float' || $propertyTypeEnd == 'date' || $propertyTypeEnd == 'datetime')) {
            $theQuery = 'REPLACE INTO  ' . RSMtable($propertyTypeEnd) . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID) SELECT ' . getClientPropertyItemType($propertyIDend, $clientID) . ', RS_ITEM_ID, CAST(RS_DATA AS ' . $equivalentTypeMysqlDestiny . '), ' . $propertyIDend . ', RS_CLIENT_ID FROM ' . RSMtable($propertyTypeStart) . ' WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . getClientPropertyItemType($propertyIDstart, $clientID) . ' AND RS_PROPERTY_ID = ' . $propertyIDstart;
        }
        // Any permutation between: images, files
        if (($propertyTypeStart == 'image' || $propertyTypeStart == 'file') && ($propertyTypeEnd == 'image' || $propertyTypeEnd == 'file')) {
            $theQuery = 'REPLACE INTO  ' . RSMtable($propertyTypeEnd) . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_NAME, RS_SIZE, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID) SELECT ' . getClientPropertyItemType($propertyIDend, $clientID) . ', RS_ITEM_ID, RS_NAME, RS_SIZE, RS_DATA, ' . $propertyIDend . ', RS_CLIENT_ID FROM ' . RSMtable($propertyTypeStart) . ' WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . getClientPropertyItemType($propertyIDstart, $clientID) . ' AND RS_PROPERTY_ID = ' . $propertyIDstart;
        }

        // From: identifier --> identifier/identifiers OR identifiers --> identifiers
        if (($propertyTypeStart == 'identifier' && ($propertyTypeEnd == 'identifier' || $propertyTypeEnd == 'identifiers')) || ($propertyTypeStart == 'identifiers' && $propertyTypeEnd == 'identifiers')) {
            $theQuery = 'REPLACE INTO  ' . RSMtable($propertyTypeEnd) . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID, RS_ORDER) SELECT ' . getClientPropertyItemType($propertyIDend, $clientID) . ', RS_ITEM_ID, CAST(RS_DATA AS ' . $equivalentTypeMysqlDestiny . '), ' . $propertyIDend . ', RS_CLIENT_ID, RS_ORDER FROM ' . RSMtable($propertyTypeStart) . ' WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . getClientPropertyItemType($propertyIDstart, $clientID) . ' AND RS_PROPERTY_ID = ' . $propertyIDstart;
        }

        // From: identifiers --> identifier. Only identifiers that can be considered as integers will replace values on destiny table, in other case no replacement will be done.
        if ($propertyTypeStart == 'identifiers' && $propertyTypeEnd == 'identifier') {
            $theQuery = 'REPLACE INTO  ' . RSMtable($propertyTypeEnd) . ' (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID, RS_ORDER) SELECT ' . getClientPropertyItemType($propertyIDend, $clientID) . ', RS_ITEM_ID, CAST(RS_DATA AS ' . $equivalentTypeMysqlDestiny . '), ' . $propertyIDend . ', RS_CLIENT_ID, RS_ORDER FROM ' . RSMtable($propertyTypeStart) . ' WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . getClientPropertyItemType($propertyIDstart, $clientID) . ' AND RS_PROPERTY_ID = ' . $propertyIDstart . " AND RS_DATA REGEXP '^[0-9]+$'";
        }

        // execute query
        $result = RSquery($theQuery);

        // Return true/false
        if ($result) {
            $results['result'] = "OK";

            // Check for properties in media server
            if (($propertyTypeStart == 'image' || $propertyTypeStart == 'file') && ($propertyTypeEnd == 'image' || $propertyTypeEnd == 'file')) {
                $results = duplicateMediaProperty($clientID, $propertyIDstart, $propertyIDend);
            }
        } else {
            $results['result'] = "NOK";
            $results['description'] = "ERROR DUPLICATING PROPERTY";
        }
    } else {
        $results['result'] = "NOK";
        $results['description'] = "PROPERTIES BELONGING TO DIFFERENT ITEMTYPES";
    }
} else {
    // The user does not have sufficient permissions
    $results['result'] = "NOK";
    $results['description'] = "USER HAS NOT PERMISSIONS";
}

// Return results
RSreturnArrayResults($results);

// This function returns the closest mysql itemtype for a given RSM property
function typeMySQL($type)
{

    switch ($type) {
        case "text":
            $mysqlType = "char";
            break;
        case "longtext":
            $mysqlType = "char";
            break;
        case "integer":
            $mysqlType = "signed";
            break;
        case "float":
            $mysqlType = "decimal";
            break;
        case "date":
            $mysqlType = "date";
            break;
        case "datetime":
            $mysqlType = "datetime";
            break;
        case "identifier":
            $mysqlType = "signed";
            break;
        case "identifier2itemtype":
            $mysqlType = "signed";
            break;
        case "identifier2property":
            $mysqlType = "signed";
            break;
        case "identifiers":
            $mysqlType = "char";
            break;
        case "image":
            $mysqlType = "binary";
            break;
        case "file":
            $mysqlType = "binary";
            break;
        case "password":
            $mysqlType = "char";
            break;
        case "variant":
            $mysqlType = "char";
            break;
        default:
            $mysqlType = "unknown";
    }
    return $mysqlType;
}

// This function returns the table where RSM saves the data depending on the type
function RSMtable($type)
{
    switch ($type) {
        case "integer":
            $tableName = "rs_property_integers";
            break;
        case "float":
            $tableName = "rs_property_floats";
            break;
        case "date":
            $tableName = "rs_property_dates";
            break;
        case "identifier":
            $tableName = "rs_property_identifiers";
            break;
        case "identifier2itemtype":
            $tableName = "rs_property_identifiers_to_itemtypes";
            break;
        case "identifier2property":
            $tableName = "rs_property_identifiers_to_properties";
            break;
        case "identifiers":
            $tableName = "rs_property_multiIdentifiers";
            break;
        case "image":
            $tableName = "rs_property_images";
            break;
        case "file":
            $tableName = "rs_property_files";
            break;
        case "password":
            $tableName = "rs_property_passwords";
            break;
        default:
            $tableName = "rs_property_" . $type;
    }
    return $tableName;
}
