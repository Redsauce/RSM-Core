<?php
//***************************************************//
//validateUser.php
//***************************************************//
//Description:
//    validates passed login and password against DB
//***************************************************//
//Version:
//    v1.0: validates passed login and password against DB
//  v1.1: validates passed login and password against DB but password is passed already encrypted in MD5
//  v1.2: no need of passed database connection parameters
//        because they are included from an external file
//  v1.3: ACCESS DENIED error code has been changed to -3
//  v1.4: Includes information about new versions of the app
//  v1.5: Uses the generic rs_users table instead of a custom one
//  v1.6: Added a badge for each user/client
//***************************************************//
//Input:
// Option 1: POST
//    Login:    string: user's login
//    Password: string: user's password encrypted in MD5
// Option 2: POST
//    Login   : string user's badge
//***************************************************//
//Output: RSRecordset XML
//    id:
//      positive     : access granted: user identificator in phpCollab (unique)
//      [code number]: access denied : wrong user and/or password
//***************************************************//

// Database connection startup
require_once "RSdatabase.php";
require_once "RStools.php";

// The "login" is required
isset($GLOBALS['RS_POST']['Login'   ]) ? $login    = $GLOBALS['RS_POST']['Login'   ] : dieWithError(400);
isset($GLOBALS['RS_POST']['Password']) ? $password = $GLOBALS['RS_POST']['Password'] : $password = "";

if ($password == "") {
    // Verification with a badge
    $query = 'SELECT rs_clients.RS_LOGO AS RS_CLIENT_LOGO, rs_clients.RS_NAME AS RS_CLIENT_NAME, rs_users.RS_USER_ID, rs_users.RS_CLIENT_ID, rs_users.RS_ITEM_ID FROM rs_users, rs_clients WHERE rs_users.RS_BADGE = "'.$login.'" AND rs_clients.RS_ID = rs_users.RS_CLIENT_ID';

} else {
    // Verification with a username and password.
    $query = 'SELECT rs_clients.RS_LOGO AS RS_CLIENT_LOGO, rs_clients.RS_NAME AS RS_CLIENT_NAME, rs_users.RS_USER_ID, rs_users.RS_CLIENT_ID, rs_users.RS_ITEM_ID FROM rs_users, rs_clients WHERE rs_users.RS_LOGIN = "'.$login.'" AND rs_users.RS_PASSWORD = "'.$password.'" AND rs_clients.RS_ID = rs_users.RS_CLIENT_ID';

}

// Query the database
$users = RSQuery($query);

// Analyze results
if (!$users) {
    RSReturnError("QUERY EXECUTION ERROR.", 1);
}

switch ($users->num_rows) {
    case 0:
        
        if ($password == "") {
            RSReturnError("ACCESS DENIED. BADGE NOT FOUND.", 2);
        } else {
            RSReturnError("ACCESS DENIED. USERNAME & PASSWORD NOT FOUND.", 3);
        }

    default:

        while ($row = $users->fetch_assoc()) {
            $results[] = array("id"=>$row['RS_USER_ID'], "userID"=>$row['RS_USER_ID'], "clientID"=>$row['RS_CLIENT_ID'], "itemID"=>$row['RS_ITEM_ID'], "clientName"=>$row['RS_CLIENT_NAME'], "clientLogo"=>bin2hex($row['RS_CLIENT_LOGO']));
        }

        // Write XML Response back to the application
        RSReturnArrayQueryResults($results);
    }

?>
