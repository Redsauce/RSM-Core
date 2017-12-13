<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

$clientID =               $GLOBALS['RS_POST']['clientID'] ;
$login    = base64_decode($GLOBALS['RS_POST']['login'   ]);
$password =               $GLOBALS['RS_POST']['password'] ;
$personID =               $GLOBALS['RS_POST']['personID'] ;

//First of all, we need to check if the variable clientID does not have the value 0
if (($clientID != 0) || ($clientID != "")) {
    //We check if the user already exists for the given client
    $theQuery_userAlreadyExists = 'SELECT RS_USER_ID FROM rs_users WHERE RS_LOGIN ="' . $login . '" AND RS_CLIENT_ID = ' . $clientID;
    $result = RSQuery($theQuery_userAlreadyExists);

    if ($result->num_rows > 0) {

        RSReturnError("USER ALREADY EXISTS", "1");
        exit ;
    } else {

        if ($personID == '0') {

            // get staff item type
            $staffItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['staff'], $clientID);

            // add new entry
            //$personID = createNewItem($staffItemTypeID, $clientID);
            $personID = createEmptyItem($staffItemTypeID, $clientID);

            // update main value with the login (TODO: the main value of the items "staff" may not be
            // the name or an identifier name... so we could add an application property called "name"
            // to the staff itemtype and use it for saving login into the item entry)
            setPropertyValueByID(getMainPropertyID($staffItemTypeID, $clientID), $staffItemTypeID, $personID, $clientID, $login, '', $RSuserID);
        }

        // Insert user into rs_users table
        $newID = getNextIdentification("rs_users", "RS_USER_ID", $clientID);
        $theQueryUser = 'INSERT INTO rs_users (RS_USER_ID, RS_CLIENT_ID, RS_LOGIN, RS_PASSWORD, RS_ITEM_ID) VALUES (' . $newID . ',' . $clientID . ',"' . $login . '","' . $password . '",' . $personID . ')';
    }

    // execute the query
    if ($result = RSQuery($theQueryUser)) {

        $results['userID'  ] = $newID;
        $results['login'   ] = $login;
        $results['personID'] = $personID;
    } else {

        $results['result'] = "NOK";
    }

} else $results['result'] = "NOK"; // Invalid clientID

// And write XML Response back to the application
RSReturnArrayResults($results);
?>
