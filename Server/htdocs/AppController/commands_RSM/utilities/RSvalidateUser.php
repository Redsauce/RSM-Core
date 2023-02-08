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
//    userLogin:    string: user's login
//    userPassword: string: user's password encrypted in MD5
//....userBadge: string user's badge
// Option 2: POST
//....userBadge: string user's badge
//***************************************************//
//Output: RSRecordset XML
//    id:
//      positive: access granted: user identificator in phpCollab (unique)
//            -1: access denied: wrong user and/or password
//  lastAllowedBuild: If included, indicates the build of the new version
//  lastAllowedLink: If included, indicates the link to the download page
//***************************************************//

// Database connection startup
require_once "RSdatabase.php";
require_once "RStools.php";


if (isset($GLOBALS['RS_POST']['Badge'])) {

    $userBadge = $GLOBALS['RS_POST']['Badge'];

    $query = 'SELECT rs_clients.RS_LOGO AS RS_CLIENT_LOGO, rs_clients.RS_NAME AS RS_CLIENT_NAME, rs_users.RS_USER_ID, rs_users.RS_CLIENT_ID, rs_users.RS_ITEM_ID FROM rs_users, rs_clients WHERE rs_users.RS_BADGE = "'.$userBadge.'" AND rs_clients.RS_ID = rs_users.RS_CLIENT_ID';

    // Query the database
    $users = RSQuery($query);

    // Analyze results
    if (!$users) {
        // Error validating user
        RSReturnError("ACCESS DENIED. INCORRECT USER BADGE.", -3);
        exit;
    }

    switch ($users->num_rows) {
    
        case 0:
            // No valid user was found
            RSReturnError("ACCESS DENIED. INCORRECT USER BADGE.", -3);
            break;
        case 1:
            $row = $users->fetch_assoc();
    
            $results['id'        ] = $row['RS_USER_ID'    ];
            $results['clientID'] = $row['RS_CLIENT_ID'];
            $results['itemID'  ] = $row['RS_ITEM_ID'    ];
    
            // Write XML Response back to the application
            RSReturnArrayResults($results);
            break;
        default:
            // Database error
            RSReturnError("DATABASE ERROR. MORE THAN ONE USER WITH THE SAME BADGE.", -3);
            exit;
    }

}


isset($GLOBALS['RS_POST']['Login'   ]) ? $userLogin  = $GLOBALS['RS_POST']['Login'   ] : dieWithError(400);
isset($GLOBALS['RS_POST']['Password']) ? $userPass   = $GLOBALS['RS_POST']['Password'] : dieWithError(400);

$query = 'SELECT rs_clients.RS_LOGO AS RS_CLIENT_LOGO, rs_clients.RS_NAME AS RS_CLIENT_NAME, rs_users.RS_USER_ID, rs_users.RS_CLIENT_ID, rs_users.RS_ITEM_ID FROM rs_users, rs_clients WHERE rs_users.RS_LOGIN = "'.$userLogin.'" AND rs_users.RS_PASSWORD = "'.$userPass.'" AND rs_clients.RS_ID = rs_users.RS_CLIENT_ID';

// Query the database
$users = RSQuery($query);

// Analyze results
if (!$users) {
    // Error validating user
    RSReturnError("ACCESS DENIED. INCORRECT USER OR PASSWORD.", -3);
    exit;
}


switch ($users->num_rows) {

    case 0:
        // No valid user was found
        RSReturnError("ACCESS DENIED. INCORRECT USER OR PASSWORD.", -3);
        break;

    case 1:
        $row = $users->fetch_assoc();

        $results['id'        ] = $row['RS_USER_ID'    ];
        $results['clientID'] = $row['RS_CLIENT_ID'];
        $results['itemID'  ] = $row['RS_ITEM_ID'    ];

        // Write XML Response back to the application
        RSReturnArrayResults($results);
        break;

    default:
        while ($row = $users->fetch_assoc()) {
            $results[] = array("id"=>$row['RS_USER_ID'], "userID"=>$row['RS_USER_ID'], "clientID"=>$row['RS_CLIENT_ID'], "itemID"=>$row['RS_ITEM_ID'], "clientName"=>$row['RS_CLIENT_NAME'], "clientLogo"=>bin2hex($row['RS_CLIENT_LOGO']));
        }

        // Write XML Response back to the application
        RSReturnArrayQueryResults($results);
        break;
}

?>