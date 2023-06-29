<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];

// Now we build the query
$theQuery = 'SELECT rs_actions_clients.RS_ID AS "actionID", rs_actions.RS_NAME AS "actionName", rs_actions.RS_CONFIGURATION_ITEMTYPE, rs_actions_clients.RS_CONFIGURATION_ITEM_ID FROM rs_actions INNER JOIN rs_actions_clients ON rs_actions.RS_ID = rs_actions_clients.RS_ACTION_ID WHERE rs_actions_clients.RS_CLIENT_ID = '.$clientID.' ORDER BY rs_actions.RS_NAME';

// Query the database
$result = RSQuery($theQuery);

$results=array();

while ($row=$result->fetch_assoc()) {
    //get client name for module
    $clientName = getPropertyValue($row['RS_CONFIGURATION_ITEMTYPE'].'.name', getClientItemTypeID_RelatedWith_byName($row['RS_CONFIGURATION_ITEMTYPE'], $clientID), $row['RS_CONFIGURATION_ITEM_ID'], $clientID);
    //if client name exists return it, otherwise return generic action name
    $results[]=array("actionID"=>$row["actionID"],"actionName"=>($clientName!=""?$clientName:$row["actionName"]));
}

// And write XML Response back to the application
RSReturnArrayQueryResults($results);
