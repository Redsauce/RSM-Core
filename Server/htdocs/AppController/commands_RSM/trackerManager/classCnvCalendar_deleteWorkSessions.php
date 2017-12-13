<?php
//***************************************************
//classCnvCalendar_deleteWorkSessions.php
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$wsID = $GLOBALS['RS_POST']['workSessionID'];
$clientID = $GLOBALS['RS_POST']['clientID'];

//check valid worksession
if ($wsID > 0) {

    // get the item types
    $worksItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['worksessions'], $clientID);
    $tasksItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);
    $tasksGroupItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'], $clientID);

    //check worksession exists
    if (count(getItems($worksItemTypeID, $clientID, true, $wsID)) > 0) {

        // get the worksession duration
        $duration = getPropertyValue($definitions['worksessionDuration'], $worksItemTypeID, $wsID, $clientID);

        // get the worksession task
        $wsTask = getPropertyValue($definitions['worksessionTask'], $worksItemTypeID, $wsID, $clientID);

        // delete worksession
        deleteItem($worksItemTypeID, $wsID, $clientID);

        // update parent tasks current time
        $taskParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'], $clientID);
        $taskCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'], $clientID);
        $tasksGroupParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID'], $clientID);
        $tasksGroupCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.currentTime'], $clientID);

        //first update parent task
        // get task current time
        $taskCurrentTime = getItemPropertyValue($wsTask, $taskCurrentTimePropertyID, $clientID);
    
        // update task current time
        setPropertyValueByID($taskCurrentTimePropertyID, $tasksItemTypeID, $wsTask, $clientID, $taskCurrentTime - $duration, '', $RSuserID);
    
        // get task parent
        $taskGroup = getItemPropertyValue($wsTask, $taskParentPropertyID, $clientID);
        
        //update all ancestor groups
        while ($taskGroup != '0') {
            // get taskGroup current time
            $taskGroupCurrentTime = getItemPropertyValue($taskGroup, $tasksGroupCurrentTimePropertyID, $clientID);
    
            // update taskGroup current time
            setPropertyValueByID($tasksGroupCurrentTimePropertyID, $tasksGroupItemTypeID, $taskGroup, $clientID, $taskGroupCurrentTime - $duration, '', $RSuserID);
    
            // get taskGroup parent
            $taskGroup = getItemPropertyValue($taskGroup, $tasksGroupParentPropertyID, $clientID);
        }

        $results['workSessionID'] = $wsID;
        //$results['taskID'] = $wsTask;

    } else {
        $results['result'] = "NOK";
        $results['description'] = "WORKSESSION NOT EXISTS";
        $results['workSessionID'] = $wsID;
    }
} else {
    $results['result'] = "NOK";
    $results['description'] = "INVALID WORKSESSION";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>