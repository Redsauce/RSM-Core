<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMuserPropertiesManagement.php";

// definitions
$itemTypeID = $GLOBALS[$cstRS_POST][$cstItemTypeID];
$clientID   = $GLOBALS[$cstRS_POST]['clientID'  ];
$userID     = $GLOBALS[$cstRS_POST]['userID'    ];

$results = getUserProperties($userID,$clientID,$itemTypeID);

// And return XML response back to application			
RSReturnArrayQueryResults($results);
?>