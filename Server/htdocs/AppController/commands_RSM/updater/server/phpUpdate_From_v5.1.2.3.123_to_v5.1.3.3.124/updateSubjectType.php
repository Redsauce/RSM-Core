<?php
//***************************************************
//Description:
//   Update the property subjec.studyID from identifier to identifiers
//   - Change de system property
//   - Change de associated client properties
//   - Move the related data from table rs_property_identifiers to rs_property_multiIdentifiers
//***************************************************
function start_update_subjects($clientsToUpdate){ 
    global $definitions;
    
    if(count($clientsToUpdate)==0){
        print ("No specific clients defined, processing all clients: \n");
        // get all clients
        $theQuery = $mysqli->query("SELECT `RS_ID` FROM `rs_clients`");
        
        while($client=$theQuery->fetch_assoc()) {
            $clientsToUpdate[]=$client['RS_ID'];
        }
    }
    
    print ("Selected clients to update: \n");
    print_r($clientsToUpdate);
    
    // Update the system property
    $subject_app_update = "UPDATE rs_property_app_definitions SET RS_TYPE = 'identifiers' WHERE `RS_NAME` = 'subject.studyID'";
    $result = $mysqli->query($subject_app_update);
        
    //Update for the defined clients
    for ($cliNum=0;$cliNum<count($clientsToUpdate);$cliNum++){
        
        //Get the clientID
        $clientID = $clientsToUpdate[$cliNum];
        print ("Starting process for client ".$clientID."\n");
        
        //get properties related with subject.studyID
        $theQuery = "SELECT RS_PROPERTY_ID FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = (SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'subject.studyID' LIMIT 1) AND RS_CLIENT_ID = ".$clientID;
        $propertiesResults = $mysqli->query($theQuery);
        
        if($propertiesResults->num_rows>0){
            $propertyResult = $propertiesResults->fetch_assoc();
                               
            // Convert related properties in multiIdentifiers
            $theNewPropertiesQuery = "UPDATE  `rs_item_properties` SET `RS_TYPE` = 'identifiers' WHERE `RS_CLIENT_ID` = ".$clientID." AND `RS_PROPERTY_ID` = ".$propertyResult['RS_PROPERTY_ID'];
            $result = $mysqli->query($theNewPropertiesQuery);  
            
            // Move related contents from the table rs_property_identifiers to rs_property_multiIdentifiers
            $moveIdentifiersToMulti = "INSERT INTO rs_property_multiIdentifiers (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID) SELECT RS_ITEMTYPE_ID, RS_ITEM_ID, RS_DATA, RS_PROPERTY_ID, RS_CLIENT_ID FROM rs_property_identifiers WHERE RS_PROPERTY_ID = ".$propertyResult['RS_PROPERTY_ID']." AND RS_CLIENT_ID = ".$clientID;
            $result = $mysqli->query($moveIdentifiersToMulti);
            
            // Delete contents from the table rs_property_identifiers
            $deleteIdentifiers = "DELETE FROM rs_property_identifiers WHERE RS_PROPERTY_ID = ".$propertyResult['RS_PROPERTY_ID']." AND RS_CLIENT_ID = ".$clientID;
            $result = $mysqli->query($deleteIdentifiers);

        }
        print ("Client with ID ".$clientID." updated property!\n");
    }

    //RETURN OK RESULT
    return "OK";
}
?>