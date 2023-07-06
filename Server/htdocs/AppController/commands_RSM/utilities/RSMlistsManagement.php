<?php
require_once "RSMlistsDefinitions.php";

// -----------------------------------------------
// ---------------- CLIENT LISTS -----------------
// -----------------------------------------------

// Return the name of the list passed
function getListName($listID, $clientID) {

    $result = RSquery("SELECT RS_NAME FROM rs_lists WHERE RS_LIST_ID = " . $listID . " AND RS_CLIENT_ID = " . $clientID);
    if ($result) {
        $list = $result->fetch_assoc();

        return $list['RS_NAME'];
    } else {
        return '';
    }
}

// Return the client lists
function getLists($clientID) {

    $theQuery = "SELECT RS_LIST_ID as 'ID', RS_NAME as 'name' FROM rs_lists WHERE RS_CLIENT_ID = " . $clientID . " ORDER BY RS_NAME";

    $results = RSquery($theQuery);

    return $results;
}

// Return the name of the client value passed
function getValue($valueID, $clientID) {

    $result = RSquery("SELECT RS_VALUE FROM rs_property_values WHERE RS_VALUE_ID = " . $valueID . " AND RS_CLIENT_ID = " . $clientID);
    if ($result) {
        $value = $result->fetch_assoc();

        return $value['RS_VALUE'];
    } else {
        return '';
    }
}

// Return the client list values of the list passed
function getListValues($listID, $clientID) {
    $theQuery = "SELECT RS_VALUE_ID AS 'valueID', RS_VALUE AS 'value' FROM rs_property_values WHERE RS_LIST_ID = " . $listID . " AND RS_CLIENT_ID = " . $clientID . " ORDER BY RS_ORDER";

    $results = RSquery($theQuery);

    $listValues = array();
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $listValues[] = $row;
        }
    }
    return $listValues;
}

// Return the client properties that use list values
function getPropertiesUsingLists($listID, $clientID) {
    $results = RSquery("SELECT RS_PROPERTY_ID FROM rs_properties_lists WHERE RS_LIST_ID = " . $listID . " AND RS_CLIENT_ID = " . $clientID);

    $propertiesList = array();
    if ($results) {
        while ($row = $results->fetch_assoc())
            $propertiesList[] = $row['RS_PROPERTY_ID'];
    }
    return $propertiesList;
}

// Return the list and the associates with the property passed (with the mode 'multiValues')
function getPropertyList($propertyID, $clientID) {
    $result = RSquery('SELECT RS_LIST_ID AS "listID", RS_MULTIVALUES AS "multiValues" FROM rs_properties_lists WHERE RS_PROPERTY_ID = ' . $propertyID . ' AND RS_CLIENT_ID = ' . $clientID);
    if ($result) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}

//Return the ID of a list selected by their name
function getListID($listName, $clientID) {
    $result = RSquery('SELECT RS_LIST_ID AS "listID" FROM rs_lists WHERE RS_NAME="' . $listName . '" AND RS_CLIENT_ID=' . $clientID);

    $ids = array();

    if ($result) {
        while ($id = $result->fetch_assoc())
            $ids[] = $id['listID'];
    }

    return $ids;
}

// -----------------------------------------------
// ---------------- APP LISTS --------------------
// -----------------------------------------------

// Return the name of the application list passed
function getAppListName($appListID) {

    $result = RSquery("SELECT RS_NAME FROM rs_lists_app WHERE RS_ID = " . $appListID);

    if ($result) {
        $appList = $result->fetch_assoc();
        return $appList['RS_NAME'];
    } else {
        return '';
    }
}

// Return the ID of the application list passed by definition
function getAppListID($appListDef) {
    global $listDefs;

    $result = RSquery("SELECT RS_ID FROM rs_lists_app WHERE RS_NAME = '" . $listDefs[$appListDef] . "'");

    if ($result) {
        $appList = $result->fetch_assoc();
        return $appList['RS_ID'];
    } else {
        return '';
    }
}

// Return the application lists
function getAppLists() {

    $results = RSquery("SELECT RS_ID AS 'ID', RS_NAME AS 'name' FROM rs_lists_app ORDER BY RS_NAME");

    $appLists = array();
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $appLists[] = $row;
        }
    }
    return $appLists;
}

// Return the name of the application value passed
function getAppValue($appValueID) {

    $result = RSquery("SELECT RS_VALUE FROM rs_lists_values_app WHERE RS_ID = " . $appValueID);

    if ($result) {
        $appValue = $result->fetch_assoc();
        return $appValue['RS_VALUE'];
    } else {
        return '';
    }
}

// Return the application list values of the list passed
function getAppListValues($appListID) {

    $results = RSquery("SELECT RS_ID, RS_VALUE FROM rs_lists_values_app WHERE RS_LIST_APP_ID = " . $appListID . " ORDER BY RS_VALUE");

    $appListValues = array();
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $appListValues[] = array('valueID' => $row['RS_ID'], 'value' => $row['RS_VALUE']);
        }
    }

    return $appListValues;
}

// Return the ID of the application list value passed by definition
function getAppListValueID($appListValueDef) {
    global $listDefs;

    isset($listDefs[$appListValueDef]) ? $value = $listDefs[$appListValueDef] : $value = $appListValueDef;

    $result = RSquery("SELECT RS_ID FROM rs_lists_values_app WHERE RS_VALUE = '" . $value . "'");
    if ($result) {
        $appListValue = $result->fetch_assoc();

        return $appListValue['RS_ID'];
    } else {
        return '';
    }
}

// -----------------------------------------------
// ----------- LISTS RELATIONSHIPS ---------------
// -----------------------------------------------

// Return the ID of the client list related with the application list passed
function getClientListID_RelatedWith($appListID, $clientID) {

    $result = RSquery("SELECT RS_LIST_ID FROM rs_lists_relations WHERE RS_LIST_APP_ID = " . $appListID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $clientList = $result->fetch_assoc()) {
        return $clientList['RS_LIST_ID'];
    } else {
        return '0';
    }
}

// Return the ID of the application list related with the client list passed
function getAppListID_RelatedWith($clientListID, $clientID) {

    $result = RSquery("SELECT RS_LIST_APP_ID FROM rs_lists_relations WHERE RS_LIST_ID = " . $clientListID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $appList = $result->fetch_assoc())
        return $appList['RS_LIST_APP_ID'];

    return '0';
}

// Delete list relationship (client side)
function deleteListRelationship_clientSide($clientListID, $clientID) {
    RSquery("DELETE FROM rs_lists_relations WHERE RS_LIST_ID = " . $clientListID . " AND RS_CLIENT_ID = " . $clientID);
    // Delete values relationships
    $listValues = getListValues($clientListID, $clientID);
    foreach ($listValues as $value) {
        deleteListValueRelationship_clientSide($value['valueID'], $clientID);
    }
}

// Delete list relationship (app side)
function deleteListRelationship_appSide($appListID, $clientID) {
    RSquery("DELETE FROM rs_lists_relations WHERE RS_LIST_APP_ID = " . $appListID . " AND RS_CLIENT_ID = " . $clientID);
    // Delete values relationships
    $listValues = getAppListValues($appListID);
    foreach ($listValues as $value) {
        deleteListValueRelationship_appSide($value['valueID'], $clientID);
    }
}

// Create a lists relationship
function createListsRelationship($clientListID, $appListID, $clientID) {
    // delete previous client list relationship if any
    deleteListRelationship_clientSide($clientListID, $clientID);
    // delete previous application list relationship if any
    deleteListRelationship_appSide($appListID, $clientID);
    // create new relationship
    RSquery("INSERT INTO rs_lists_relations VALUES (" . $appListID . "," . $clientID . "," . $clientListID . ",NOW())");
}

// -----------------------------------------------
// ---------- LISTS VALUES RELATIONSHIPS ---------
// -----------------------------------------------

// Return the ID of the client list value related with the application list value passed
function getClientListValueID_RelatedWith($appListValueID, $clientID) {
    $result = RSquery("SELECT RS_VALUE_ID FROM rs_lists_values_relations WHERE RS_VALUE_APP_ID = " . $appListValueID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $clientListValue = $result->fetch_assoc())
        return $clientListValue['RS_VALUE_ID'];

    return '0';
}

// Return the ID of the application list value related with the client list value passed
function getAppListValueID_RelatedWith($clientListValueID, $clientID) {
    $result = RSquery("SELECT RS_VALUE_APP_ID FROM rs_lists_values_relations WHERE RS_VALUE_ID = " . $clientListValueID . " AND RS_CLIENT_ID = " . $clientID);

    if ($result && $appListValue = $result->fetch_assoc())
        return $appListValue['RS_VALUE_APP_ID'];

    return '0';
}

// Delete list value relationship (client side)
function deleteListValueRelationship_clientSide($clientListValueID, $clientID) {
    RSquery("DELETE FROM rs_lists_values_relations WHERE RS_VALUE_ID = " . $clientListValueID . " AND RS_CLIENT_ID = " . $clientID);
}

// Delete list value relationship (app side)
function deleteListValueRelationship_appSide($appListValueID, $clientID) {
    RSquery("DELETE FROM rs_lists_values_relations WHERE RS_VALUE_APP_ID = " . $appListValueID . " AND RS_CLIENT_ID = " . $clientID);
}

// Create a lists values relationship
function createListsValuesRelationship($clientListValueID, $appListValueID, $clientID) {
    // delete previous client list value relationship if any
    deleteListValueRelationship_clientSide($clientListValueID, $clientID);
    // delete previous application list value relationship if any
    deleteListValueRelationship_appSide($appListValueID, $clientID);
    // create new relationship
    RSquery("INSERT INTO rs_lists_values_relations VALUES (" . $appListValueID . "," . $clientID . "," . $clientListValueID . ",NOW())");
}
