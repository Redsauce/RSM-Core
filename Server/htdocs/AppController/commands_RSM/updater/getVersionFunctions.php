<?php
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";
include_once "../utilities/RSMmodulesManagement.php";


//***************************************************
//DESCRIPTION:
//    This function returns an array with the description of bugs and affected modules only when the bugs are Fixed and Closed, between two given RSM-versions.
//    If there is no error but no registers were found, the PHP will return an empty recordset.
//    The RSM-versions must be given in the correct order.
//
//INPUT: we need the first and last version of RSM for searching between them.
//    - startVersion: lowest RSM-version.  For example: 5.1.9.3.130
//    - endVersion  : highest RSM-version. For example: 5.2.10.3.131
//
//OUTPUT:
//    Recordset with three columns:
//    - type: with the text 'bugFixing'
//    - description: the description of the bug in language ES
//    - Modules: the affected module
//    The answer will give us a row for each pair of description:module.
//    For example, if the same bug is related with 3 modules, the answer will be 3 different rows, one for each module, with the same columns 'description' and 'type'
//
//    If there where an error, the recordset will be only one row with two columns:
//    - result: column with the value 'NOK'
//    - description: description of the error
//***************************************************
function getFixedBugs($RSuserID, $clientID, $startVersion, $endVersion, $lang){
    // Hardcoded variables (clientID = 1)
    $redsauceClient     = 1;
    $bugItemTypeID      = 85;
    $versionPropertyID  = 774;
    $affectedPropertyID = 836;
    $bugStatus          = 730;
    $bugSolutionStatus  = 755;
    $revisionPropertyID = 759;

    switch(strtoupper($lang)){
        case "EN":
            $descPropertyID = 810;
            break;
        case "DE":
            $descPropertyID = 811;
            break;
       default:
            $descPropertyID = 809;
    }

    $buildItemTypeID        = getClientItemTypeID_RelatedWith_byName("productBuild"            , $redsauceClient);
    $productItemTypeID      = getClientItemTypeID_RelatedWith_byName("studies"                 , $redsauceClient);
    //$revisionPropertyID     = getClientPropertyID_RelatedWith_byName("revisionHistory.revision", $redsauceClient);
    $productPropertyID      = getClientPropertyID_RelatedWith_byName("productBuild.product"    , $redsauceClient);
    $studyNamePropertyID    = getClientPropertyID_RelatedWith_byName("studies.name"            , $redsauceClient);
    $revisionMainPropertyID = getMainPropertyID($bugItemTypeID  , $redsauceClient);
    $buildMainPropertyID    = getMainPropertyID($buildItemTypeID, $redsauceClient);
    $response=array();

    // build filter properties
    $filterProperties = array();
    $filterProperties[] = array('ID' => $studyNamePropertyID, 'value' => 'Redsauce Manager');

    // build return properties array
    $returnProperties = array();

    // get items
    $results = getFilteredItemsIDs($productItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

    if(count($results)==1){
        // build filter properties
        $filterProperties = array();
        $filterProperties[] = array('ID' => $productPropertyID, 'value' => $results[0]['ID']);

        if($startVersion<$endVersion){
              $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $startVersion, 'mode' => 'GT');
              $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $endVersion, 'mode' => 'LE');
        }else $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $startVersion);


        // build return properties array
        $returnProperties = array();

        // get items
        $results = getFilteredItemsIDs($buildItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

        if(count($results) > 0){
            $buildsList="";
            foreach($results as $res) $buildsList.=$res['ID'].",";
            $buildsList=rtrim($buildsList,",");

            // build filter properties
            $filterProperties = array();
            $filterProperties[] = array('ID' => $versionPropertyID , 'value' => $buildsList, 'mode' => '<-IN');
            $filterProperties[] = array('ID' => $descPropertyID    , 'value' => ""         , 'mode' => '<>'  );
            $filterProperties[] = array('ID' => $revisionPropertyID, 'value' => ""         , 'mode' => '<>'  );
            $filterProperties[] = array('ID' => $bugStatus         , 'value' => "Closed"   , 'mode' => '='  );
            $filterProperties[] = array('ID' => $bugSolutionStatus , 'value' => "Fixed"    , 'mode' => '='  );

            // build return properties array
            $returnProperties = array();
            $returnProperties[] = array('ID' => $descPropertyID    , 'name' => 'description');
            $returnProperties[] = array('ID' => $affectedPropertyID, 'name' => 'module');

            // get items
            $revisionResults = getFilteredItemsIDs($bugItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

            $result=getModulesTranslated($RSuserID, $clientID);

            if($result->num_rows>0){

                while($row = $result->fetch_assoc()){
                    $clientItemTypeID=getClientItemTypeID_RelatedWith_byName($row['RS_CONFIGURATION_ITEMTYPE'], $clientID);
                    $clientName = getPropertyValue($row['RS_CONFIGURATION_ITEMTYPE'].'.name', $clientItemTypeID, $row['RS_CONFIGURATION_ITEM_ID'], $clientID);

                    if($clientName!="") $moduleTranslations[]=array('appName'=>$row['RS_NAME'],'clientName'=>$clientName);
                    else $moduleTranslations[]=array('appName'=>$row['RS_NAME'],'clientName'=>$row['RS_APPLICATION_NAME']);
                }

                usort($moduleTranslations, makeComparer('clientName'));
                array_unshift($moduleTranslations,array("appName"=>"All Modules","clientName"=>"All Modules"));
            }

            //replace modules
            foreach($moduleTranslations as $translation) foreach($revisionResults as $revisionResult) if(strpos($revisionResult['module'],$translation['appName'])!==false) $response[]=array('type'=>'bugFixing','description'=>$revisionResult['description'],'module'=>$translation['clientName']);

        }else $response[]=array('result'=>"NOK",'description'=>"PRODUCT BUILD NOT FOUND");
    }else $response[]=array('result'=>"NOK",'description'=>"PRODUCT NOT FOUND");

    // And return array

    return $response;
}

//***************************************************
//DESCRIPTION:
//    This function returns an array with the description of 'change requests' done between two given RSM-versions.
//    If there is no error but no registers were found, the PHP will return an empty recordset.
//    The RSM-versions must be given in the correct order.
//
//INPUT: we need the first and last version of RSM for searching between them.
//    - startVersion: lowest RSM-version.  For example: 5.1.9.3.130
//    - endVersion  : highest RSM-version. For example: 5.2.10.3.131
//
//OUTPUT:
//    Recordset with three columns:
//    - type: with the text 'changeRequest'
//    - description: the description of the changeRequest in the language of the RSM application
//    - Modules: the affected module
//    The answer will give us a row for each pair of description:module.
//    For example, if the same changeRequest is related with 3 modules, the answer will be 3 different rows, one for each module, with the same columns 'description' and 'type'
//
//    If there where an error, the recordset will be only one row with two columns:
//    - result: column with the value 'NOK'
//    - description: description of the error
//***************************************************

function getChangeRequest($RSuserID, $clientID, $startVersion, $endVersion, $lang){
    // Hardcoded variables (clientID = 1)
    $clientID     = $GLOBALS['RS_POST']['clientID'];
    $startVersion = $GLOBALS['RS_POST']['startVersion'];
    $endVersion   = $GLOBALS['RS_POST']['endVersion'];
    $lang         = $GLOBALS['RS_POST']['RSlanguage'];

    // Hardcoded variables (clientID = 1)
    $redsauceClient          = 1;
    $changeRequestItemTypeID = 92;
    $versionPropertyID       = 793;
    $affectedPropertyID      = 788;
    $changeRequestStatus     = 791;
    $revisionPropertyID      = 792;

    switch(strtoupper($lang)){
        case "EN":
            $descPropertyID = 807;
            break;
        case "DE":
            $descPropertyID = 808;
            break;
        default:
            $descPropertyID = 806;
    }

    $buildItemTypeID        = getClientItemTypeID_RelatedWith_byName("productBuild"            , $redsauceClient);
    $productItemTypeID      = getClientItemTypeID_RelatedWith_byName("studies"                 , $redsauceClient);
    //$revisionPropertyID     = getClientPropertyID_RelatedWith_byName("revisionHistory.revision", $redsauceClient);
    $productPropertyID      = getClientPropertyID_RelatedWith_byName("productBuild.product"    , $redsauceClient);
    $studyNamePropertyID    = getClientPropertyID_RelatedWith_byName("studies.name"            , $redsauceClient);

    $revisionMainPropertyID = getMainPropertyID($changeRequestItemTypeID, $redsauceClient);
    $buildMainPropertyID    = getMainPropertyID($buildItemTypeID        , $redsauceClient);

    $response=array();



    // build filter properties
    $filterProperties = array();
    $filterProperties[] = array('ID' => $studyNamePropertyID, 'value' => 'Redsauce Manager');

    // build return properties array
    $returnProperties = array();

    // get items
    $results = getFilteredItemsIDs($productItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

    if(count($results)==1){
        // build filter properties
        $filterProperties = array();
        $filterProperties[] = array('ID' => $productPropertyID, 'value' => $results[0]['ID']);
        if($startVersion<$endVersion){
            $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $startVersion, 'mode' => 'GT');
            $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $endVersion  , 'mode' => 'LE');
        }else $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $startVersion);

        // build return properties array
        $returnProperties = array();

        // get items
        $results = getFilteredItemsIDs($buildItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

        if(count($results)>0){
            $buildsList="";
            foreach($results as $res) $buildsList.=$res['ID'].",";
            $buildsList=rtrim($buildsList,",");

            // build filter properties
            $filterProperties = array();
            $filterProperties[] = array('ID' => $versionPropertyID  , 'value' => $buildsList, 'mode' => '<-IN');
            $filterProperties[] = array('ID' => $descPropertyID     , 'value' => ""         , 'mode' => '<>'  );
            $filterProperties[] = array('ID' => $revisionPropertyID , 'value' => ""         , 'mode' => '<>'  );
            $filterProperties[] = array('ID' => $changeRequestStatus, 'value' => "Terminado", 'mode' => '='   );

            // build return properties array
            $returnProperties = array();
            $returnProperties[] = array('ID' => $descPropertyID    , 'name' => 'description');
            $returnProperties[] = array('ID' => $affectedPropertyID, 'name' => 'module');

            // get items
            $revisionResults = getFilteredItemsIDs($changeRequestItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

            $result=getModulesTranslated($RSuserID, $clientID);

            if($result->num_rows>0){

                while($row = $result->fetch_assoc()){
                    $clientItemTypeID=getClientItemTypeID_RelatedWith_byName($row['RS_CONFIGURATION_ITEMTYPE'], $clientID);
                    $clientName = getPropertyValue($row['RS_CONFIGURATION_ITEMTYPE'].'.name', $clientItemTypeID, $row['RS_CONFIGURATION_ITEM_ID'], $clientID);

                    if($clientName!="") $moduleTranslations[]=array('appName'=>$row['RS_NAME'],'clientName'=>$clientName);
                    else  $moduleTranslations[]=array('appName'=>$row['RS_NAME'],'clientName'=>$row['RS_APPLICATION_NAME']);
                }

                usort($moduleTranslations, makeComparer('clientName'));
                array_unshift($moduleTranslations,array("appName"=>"All Modules","clientName"=>"All Modules"));
            }

            //replace modules
            foreach($moduleTranslations as $translation) foreach($revisionResults as $revisionResult) if(strpos($revisionResult['module'],$translation['appName'])!==false) $response[]=array('type'=>'changeRequest','description'=>$revisionResult['description'],'module'=>$translation['clientName']);

        }else $response[]=array('result'=>"NOK",'description'=>"PRODUCT BUILD NOT FOUND");
    }else $response[]=array('result'=>"NOK",'description'=>"PRODUCT NOT FOUND");

    // And write XML Response back to the application
    return $response;
}

//***************************************************
//DESCRIPTION:
//    This function returns an array with the description of 'requirements' done between two given RSM-versions.
//    If there is no error but no registers were found, the PHP will return an empty recordset.
//    The RSM-versions must be given in the correct order.
//
//INPUT: we need the first and last version of RSM for searching between them.
//    - startVersion: lowest RSM-version.  For example: 5.1.9.3.130
//    - endVersion  : highest RSM-version. For example: 5.2.10.3.131
//
//OUTPUT:
//    Recordset with three columns:
//    - type: with the text 'requirement'
//    - description: the description of the requirement in the language of the RSM application
//    - Modules: the affected module
//    The answer will give us a row for each pair of description:module.
//    For example, if the same requirement is related with 3 modules, the answer will be 3 different rows, one for each module, with the same columns 'description' and 'type'
//
//    If there where an error, the recordset will be only one row with two columns:
//    - result: column with the value 'NOK'
//    - description: description of the error
//***************************************************

function getRequirements($RSuserID, $clientID, $startVersion, $endVersion, $lang){
    // Hardcoded variables (clientID = 1)
    $clientID     = $GLOBALS['RS_POST']['clientID'];
    $startVersion = $GLOBALS['RS_POST']['startVersion'];
    $endVersion   = $GLOBALS['RS_POST']['endVersion'];
    $lang         = $GLOBALS['RS_POST']['RSlanguage'];

    // Hardcoded variables (clientID = 1)
    $redsauceClient = 1;
    switch(strtoupper($lang)){
        case "EN":
            $descPropertyID = 472;
            break;
        case "DE":
            $descPropertyID = 473;
            break;
        default:
            $descPropertyID = 471;
    }
    $redsauceClient          = 1;
    $versionItemTypeID       = 53;
    $versionPropertyID       = 442;
    $affectedPropertyID      = 470;
    $revision                = 438;

    // get properties for Redsauce (clientID 1)
    $buildItemTypeID        = getClientItemTypeID_RelatedWith_byName("productBuild"            , $redsauceClient);
    $productItemTypeID      = getClientItemTypeID_RelatedWith_byName("studies"                 , $redsauceClient);
    $revisionPropertyID     = getClientPropertyID_RelatedWith_byName("revisionHistory.revision", $redsauceClient);
    $productPropertyID      = getClientPropertyID_RelatedWith_byName("productBuild.product"    , $redsauceClient);
    $studyNamePropertyID    = getClientPropertyID_RelatedWith_byName("studies.name"            , $redsauceClient);

    $revisionMainPropertyID = getMainPropertyID($versionItemTypeID, $redsauceClient);
    $buildMainPropertyID    = getMainPropertyID($buildItemTypeID  , $redsauceClient);
    $response=array();

    // build filter properties
    $filterProperties   = array();
    $filterProperties[] = array('ID' => $studyNamePropertyID, 'value' => 'Redsauce Manager');

    // build return properties array
    $returnProperties = array();

    // get items
    $results = getFilteredItemsIDs($productItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

    if(count($results)==1){
        // build filter properties
        $filterProperties = array();
        $filterProperties[] = array('ID' => $productPropertyID, 'value' => $results[0]['ID']);
        if($startVersion<$endVersion){
            $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $startVersion, 'mode' => 'GT');
            $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $endVersion  , 'mode' => 'LE');
        }else $filterProperties[] = array('ID' => $buildMainPropertyID, 'value' => $startVersion);

        // build return properties array
        $returnProperties = array();

        // get items
        $results = getFilteredItemsIDs($buildItemTypeID, $redsauceClient, $filterProperties, $returnProperties);

        if(count($results)>0){
            $buildsList="";
            foreach($results as $res) $buildsList.=$res['ID'].",";
            $buildsList=rtrim($buildsList,",");

            // build filter properties
            $filterProperties = array();
            $filterProperties[] = array('ID' => $versionPropertyID , 'value' => $buildsList, 'mode' => '<-IN');
            $filterProperties[] = array('ID' => $descPropertyID    , 'value' => ""         , 'mode' => '<>'  );
            $filterProperties[] = array('ID' => $revisionPropertyID, 'value' => ""         , 'mode' => '<>'  );

            // build return properties array
            $returnProperties = array();
            $returnProperties[] = array('ID' => $descPropertyID, 'name' => 'description');
            $returnProperties[] = array('ID' => $affectedPropertyID, 'name' => 'module');

            // get items
            $revisionResults = getFilteredItemsIDs($versionItemTypeID, $redsauceClient, $filterProperties, $returnProperties);
            $result=getModulesTranslated($RSuserID, $clientID);

            if($result->num_rows>0){

                while($row = $result->fetch_assoc()){
                    $clientItemTypeID=getClientItemTypeID_RelatedWith_byName($row['RS_CONFIGURATION_ITEMTYPE'], $clientID);
                    $clientName = getPropertyValue($row['RS_CONFIGURATION_ITEMTYPE'].'.name', $clientItemTypeID, $row['RS_CONFIGURATION_ITEM_ID'], $clientID);

                    if($clientName!="") $moduleTranslations[]=array('appName'=>$row['RS_NAME'],'clientName'=>$clientName);
                    else $moduleTranslations[]=array('appName'=>$row['RS_NAME'],'clientName'=>$row['RS_APPLICATION_NAME']);
                }

                usort($moduleTranslations, makeComparer('clientName'));
                array_unshift($moduleTranslations,array("appName"=>"All Modules","clientName"=>"All Modules"));
            }
            //replace modules
            foreach($moduleTranslations as $translation) foreach($revisionResults as $revisionResult) if(strpos($revisionResult['module'],$translation['appName'])!==false) $response[]=array('type'=>'requirement','description'=>$revisionResult['description'],'module'=>$translation['clientName']);

        }else $response[]=array('result'=>"NOK",'description'=>"PRODUCT BUILD NOT FOUND");
    }else $response[]=array('result'=>"NOK",'description'=>"PRODUCT NOT FOUND");

    // And returns the array
    return $response;
}
?>
