<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMfiltersManagement.php";

// Retrieve POST variables
isset($GLOBALS['RS_POST']['filterProperties']) ? $filterProperties = $GLOBALS['RS_POST']['filterProperties'] : dieWithError(400);
isset($GLOBALS['RS_POST']['filterRules'     ]) ? $filterRules      = $GLOBALS['RS_POST']['filterRules'     ] : dieWithError(400);
isset($GLOBALS['RS_POST']['filterType'      ]) ? $filterType       = $GLOBALS['RS_POST']['filterType'      ] : dieWithError(400);
isset($GLOBALS['RS_POST']['clientID'        ]) ? $clientID         = $GLOBALS['RS_POST']['clientID'        ] : dieWithError(400);
isset($GLOBALS['RS_POST']['filterID'        ]) ? $filterID         = $GLOBALS['RS_POST']['filterID'        ] : dieWithError(400);

if ($filterID == "") $filterID = "0";

if($clientID != 0 && $clientID != ""){
    if($filterID !=0 && $filterID != ""){
        if($filterType != ""){
            //begin transaction
            $mysqli->begin_transaction();

            $allOK = true;

            //first delete all clauses
            if(deleteClauses($clientID,$filterID)==0){
                //error deleting
                $mysqli->rollback();
                $results['result'] = "NOK";
                $results['description'] = "ERROR DELETING OLD RULES";
                $allOK=false;
            }

            if($allOK){
                //add new clauses
                $filterRulesArray=explode(";",$filterRules);
                foreach($filterRulesArray as $filterRule){
                    $filterRuleData=explode(",",$filterRule);
                    if($filterRuleData[0]!=""&&$filterRuleData[0]!=0){
                        if(addClause($clientID,$filterID,$filterRuleData[0],$filterRuleData[1],base64_decode($filterRuleData[2]))==0){
                            //error adding
                            $mysqli->rollback();
                            $results['result'] = "NOK";
                            $results['description'] = "ERROR CREATING NEW RULES";
                            $allOK=false;
                            break;
                        }
                    }
                }
            }

            if($allOK){
                //first delete all filter properties
                if(deleteFilterProperties($clientID,$filterID)==0){
                    //error deleting
                    $mysqli->rollback();
                    $results['result'] = "NOK";
                    $results['description'] = "ERROR DELETING OLD PROPERTIES";
                    $allOK=false;
                }
            }

            if($allOK){
                //add new filter properties
                $filterPropertiesArray=explode(";",$filterProperties);
                foreach($filterPropertiesArray as $filterProperty){
                    if($filterProperty!=""&&$filterProperty!=0){
                        if(addFilterProperty($clientID,$filterID,$filterProperty)==0){
                            //error adding
                            $mysqli->rollback();
                            $results['result'] = "NOK";
                            $results['description'] = "ERROR CREATING NEW PROPERTIES";
                            $allOK=false;
                            break;
                        }
                    }
                }
            }

            if($allOK){
                $result = updateFilterOperator($clientID,$filterID,$filterType);

                if($result<0){
                    $mysqli->rollback();
                    $results['result']="NOK";
                    $results['description']="ERROR UPDATING FILTER";
                    $allOK=false;
                }
            }

            if($allOK){
                //all done, commit
                $mysqli->commit();
                $results['result'] = "OK";
            }
        } else {
            $results['result'] = "NOK";
            $results['description'] = "INVALID FILTER TYPE";
        }
    } else {
        $results['result'] = "NOK";
        $results['description'] = "INVALID FILTER";
    }
} else {
    $results['result'] = "NOK";
    $results['description'] = "INVALID CLIENT";
}

// And return XML response back to application
RSReturnArrayResults($results);
?>
