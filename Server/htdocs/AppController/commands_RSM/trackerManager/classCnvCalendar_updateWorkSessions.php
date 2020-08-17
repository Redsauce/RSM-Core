<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$wsID = $GLOBALS['RS_POST']['worksessionID'];
$startDate = $GLOBALS['RS_POST']['startDate'];
$duration = $GLOBALS['RS_POST']['duration'];
//new switch for updating parend dates if necessary
$updateTaskDates = 1;


//get start and end dates
$startDateObj = date_create($startDate);
$endDateObj = date_create($startDate);

if ($startDate == "" || $startDate == "0" || !$startDateObj || !$endDateObj) {
    //error creating time
    $results['result'] = "NOK";
    $results['description'] = "ERROR CREATING DATETIME";
    RSReturnArrayResults($results);
    exit();
}

if ($duration == "" || $duration == "0") {
    //empty duration
    $results['result'] = "NOK";
    $results['description'] = "INVALID DURATION";
    RSReturnArrayResults($results);
    exit();
}

date_modify($endDateObj, "+" . round($duration * 60) . " minutes");
$endDate = date_format($endDateObj, 'Y-m-d H:i:s');

// get worksessions,tasks and groups item types
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['worksessions'], $clientID);
$tasksItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);
$tasksGroupItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'], $clientID);

// get properties
$wsStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionStartDate'], $clientID);
$wsDurationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionDuration'], $clientID);
$wsUserPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionUser'], $clientID);
$wsTaskPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionTask'], $clientID);
$taskParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'], $clientID);
$taskCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'], $clientID);
$tasksStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskStartDate'], $clientID);
$tasksEndDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskEndDate'], $clientID);
$tasksGroupParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID'], $clientID);
$tasksGroupCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.currentTime'], $clientID);

// Get the user for the worksession
$user = getItemPropertyValue($wsID, $wsUserPropertyID, $clientID);

//check not existing worksessions beggining inside this time
// build filter properties
$filterProperties = array();
$filterProperties[] = array('ID' => $wsUserPropertyID, 'value' => $user);
$filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $startDate, 'mode' => 'TIME_SAME_OR_AFTER');
$filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $endDate, 'mode' => 'TIME_BEFORE');

// build return properties array
$returnProperties = array();

// get worksessions
$result = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);

if ((count($result) == 0) || ((count($result) == 1) && ($result[0]['ID'] == $wsID))) {// the only returned worksesssion could be the one we are updating
    // check that no existing worksessions beginning before this time and lasting until inside this time
    // build filter properties
    $filterProperties = array();
    $filterProperties[] = array('ID' => $wsUserPropertyID, 'value' => $user);
    $filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => date_format($endDateObj, 'Y-m-d') . " 00:00:00", 'mode' => 'TIME_SAME_OR_AFTER');
    $filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $startDate, 'mode' => 'TIME_BEFORE');

    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $wsStartDatePropertyID, 'name' => 'date');
    $returnProperties[] = array('ID' => $wsDurationPropertyID, 'name' => 'hours');

    // get worksessions
    $result = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);

    foreach ($result as $ws) {
        //check end date > new task start
        $wsEndDate = date_create($ws['date']);

        if (!$wsEndDate) {
            //error creating time
            $results['result'] = "NOK";
            $results['description'] = "ERROR CREATING DATETIME";
            RSReturnArrayResults($results);
            exit();
        }

        date_modify($wsEndDate, "+" . round($ws['hours'] * 60) . " minutes");

        //compare with new worksession start date
        if (($wsEndDate > $startDateObj) && ($ws['ID'] <> $wsID)) {

            $results['result'] = "NOK";
            $results['description'] = "WORKSESSION SLOT NOT AVAILABLE";
            RSReturnArrayResults($results);
            exit();
        }
    }

    // get the properties IDs
    $propertiesValues = array( array('ID' => $wsStartDatePropertyID, 'value' => $startDate), array('ID' => $wsDurationPropertyID, 'value' => $duration));

    // Set worksession creation date
    setItemPropertyValue($definitions['worksessionStartDate'], $itemTypeID, $wsID, $clientID, $startDate, $RSuserID);
    setItemPropertyValue($definitions['worksessionDuration'], $itemTypeID, $wsID, $clientID, $duration, $RSuserID);
    setItemPropertyValue($definitions['worksessionCreationDate'], $itemTypeID, $wsID, $clientID, date('Y-m-d H:i:s'), $RSuserID);

    //update parent task and groups current time
    //get worksession parent task and current duration
    $oldDuration = getItemPropertyValue($wsID, $wsDurationPropertyID, $clientID);
    $wsTask = getItemPropertyValue($wsID, $wsTaskPropertyID, $clientID);

    $wsDurationDiff = $duration - $oldDuration;

    // get task current time
    $taskCurrentTime = getItemPropertyValue($wsTask, $taskCurrentTimePropertyID, $clientID);

    // update parent task current time
    setPropertyValueByID($taskCurrentTimePropertyID, $tasksItemTypeID, $wsTask, $clientID, $taskCurrentTime + $wsDurationDiff, '', $RSuserID);

    // get task parent
    $taskGroup = getItemPropertyValue($wsTask, $taskParentPropertyID, $clientID);

    //update all ancestor groups
    while ($taskGroup != '0') {
        // get taskGroup current time
        $taskGroupCurrentTime = getItemPropertyValue($taskGroup, $tasksGroupCurrentTimePropertyID, $clientID);

        // update taskGroup current time
        setPropertyValueByID($tasksGroupCurrentTimePropertyID, $tasksGroupItemTypeID, $taskGroup, $clientID, $taskGroupCurrentTime + $wsDurationDiff, '', $RSuserID);

        // get taskGroup parent
        $taskGroup = getItemPropertyValue($taskGroup, $tasksGroupParentPropertyID, $clientID);

    }

    //update parent task dates if required
    if($updateTaskDates == 1){
        // get parent task start date and end date
        $parentStartDate = getItemPropertyValue($wsTask, $tasksStartDatePropertyID, $clientID);
        $parentEndDate = getItemPropertyValue($wsTask, $tasksEndDatePropertyID, $clientID);

        if (isBefore($startDate, $parentStartDate)) {
            // change the value into the database
            setPropertyValueByID($tasksStartDatePropertyID, $tasksItemTypeID, $wsTask, $clientID, $startDate, '', $RSuserID);
        }

        if (isAfter($endDate, $parentEndDate)) {
            // change the value into the database
            setPropertyValueByID($tasksEndDatePropertyID, $tasksItemTypeID, $wsTask, $clientID, $endDate, '', $RSuserID);
        }
    }

    // Build results array
    $results['result'] = "OK";

} else {
    //another workssesion occupies part of this worksession's time
    $results['result'] = "NOK";
    $results['description'] = "WORKSESSION SLOT NOT AVAILABLE";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
?>