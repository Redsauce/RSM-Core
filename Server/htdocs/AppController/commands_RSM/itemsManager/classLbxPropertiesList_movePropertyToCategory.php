<?php
//***************************************************
//Description:
//	Moves a property to the given category
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Variables
$propertyID = $GLOBALS['RS_POST']['propertyID' ];
$categoryID = $GLOBALS['RS_POST']['categoryID' ];
$clientID   = $GLOBALS['RS_POST']['clientID'   ];

// We need to check if the user has permissions for accessing to the property
if (isPropertyVisible($RSuserID, $propertyID, $clientID)){
    
    // The actual category and the final category must belong to the same itemtype  
    if (getClientCategoryItemType($categoryID, $clientID) == getClientCategoryItemType(getClientPropertyCategory($propertyID, $clientID), $clientID)){
        // Make the movement
        $theQuery = 'UPDATE rs_item_properties SET RS_CATEGORY_ID = '.$categoryID.' WHERE RS_CLIENT_ID = '.$clientID.' AND RS_PROPERTY_ID = '.$propertyID;

        // execute query
        $result = RSQuery($theQuery);

        // Query error?
        if ($result){
            $results['result'] = "OK";
        }else{
            $results['result'     ] = "NOK";
            $results['description'] = "QUERY ERROR";   
        }
           
    }else{
        $results['result'     ] = "NOK";
        $results['description'] = "CATEGORIES BELONGING TO DIFFERENT ITEMTYPES";
    }
    
}else{
    $results['result'     ] = "NOK";
    $results['description'] = "USER " . $RSuserID . " HAS NOT ACCESS TO THE PROPERTY";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>