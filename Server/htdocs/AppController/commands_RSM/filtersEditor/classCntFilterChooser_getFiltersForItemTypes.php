<?php
    // Database connection startup
    require_once "../utilities/RSdatabase.php";
    require_once "../utilities/RSMitemsManagement.php";
    require_once "../utilities/RSMfiltersManagement.php";

    // definitions
    isset($GLOBALS[$cstRS_POST]['itemTypeIDs']) ? $itemTypeIDs = $GLOBALS[$cstRS_POST]['itemTypeIDs'] : dieWithError(400);
    isset($GLOBALS[$cstRS_POST]['clientID'   ]) ? $clientID    = $GLOBALS[$cstRS_POST]['clientID'   ] : dieWithError(400);

    $returnArray = array();
    $itemTypeIDs = explode(",", $itemTypeIDs);

    foreach($itemTypeIDs as $itemTypeID){
    	$results = getFilters($clientID,$itemTypeID);

        if ($results) {
            $itemTypeName = getClientItemTypeName($itemTypeID, $clientID);

            while($result = $results->fetch_assoc()){
                $returnArray[] = array('filterID' => $result['filterID'],'filterName' => $result['filterName'],'itemTypeName' => $itemTypeName,'itemTypeID' => $itemTypeID);
            }
        }
    }

    // And return XML response back to application
    RSReturnArrayQueryResults($returnArray);
