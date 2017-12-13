<?php
//***************************************************
//classCnvCalendar_addWorkSessions.php
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['userID'      ]) ? $user      = $GLOBALS['RS_POST']['userID'      ] : dieWithError(400);
isset($GLOBALS['RS_POST']['startDate'   ]) ? $startDate = $GLOBALS['RS_POST']['startDate'   ] : dieWithError(400);
isset($GLOBALS['RS_POST']['duration'    ]) ? $duration  = $GLOBALS['RS_POST']['duration'    ] : dieWithError(400);
isset($GLOBALS['RS_POST']['parentTaskID']) ? $task      = $GLOBALS['RS_POST']['parentTaskID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['clientID'    ]) ? $clientID  = $GLOBALS['RS_POST']['clientID'    ] : dieWithError(400);

//new switch for updating parent dates if necessary
$updateTaskDates = 1;

//get start and end dates
$startDateObj = date_create($startDate);

if ($startDate == "" || $startDate == "0" || !$startDateObj) {
    //error creating time
    $results['result'] = "NOK";
    $results['description'] = "ERROR CREATING START DATETIME";
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

$endDateObj = date_create($startDate);
date_modify($endDateObj, "+" . ($duration * 60) . " minutes");
$endDate = date_format($endDateObj, 'Y-m-d H:i:s');

// get worksessions item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['worksessions'], $clientID);

// get properties
$wsUserPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['worksessionUser'     ], $clientID);
$wsStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionStartDate'], $clientID);
$wsDurationPropertyID  = getClientPropertyID_RelatedWith_byName($definitions['worksessionDuration' ], $clientID);
$wsTaskPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['worksessionTask'     ], $clientID);

// check that there are not existing worksessions beggining inside this time
// build filter properties
$filterProperties = array();
$filterProperties[] = array('ID' => $wsUserPropertyID     , 'value' => $user);
$filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $startDate, 'mode' => 'TIME_SAME_OR_AFTER');
$filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $endDate  , 'mode' => 'TIME_BEFORE');

// build return properties array
$returnProperties = array();

// get worksessions
$result = getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, '', true);

if (count($result) > 0) {
  //another workssesion occupies part of this worksession's time
  $results['result'] = "NOK";
  $results['description'] = "WORKSESSION SLOT NOT AVAILABLE";
  RSReturnArrayResults($results);
}

//check not existing worksessions beginning before this time and lasting until inside this time
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
        $results['description'] = "ERROR CREATING END DATETIME";
        RSReturnArrayResults($results);
    }

    date_modify($wsEndDate, "+" . ($ws['hours'] * 60) . " minutes");

    //compare with new worksession start date
    if ($wsEndDate > $startDateObj) {
        $results['result'] = "NOK";
        $results['description'] = "WORKSESSION SLOT NOT AVAILABLE";
        RSReturnArrayResults($results);
    }
}

// At this time the incoming worksession has valid start and end times
// so we can create it

// get the properties IDs
$propertiesValues = array( array('ID' => $wsStartDatePropertyID, 'value' => $startDate), array('ID' => $wsDurationPropertyID, 'value' => $duration), array('ID' => $wsTaskPropertyID, 'value' => $task), array('ID' => $wsUserPropertyID, 'value' => $user));

// create new worksession
$workSessionID = createItem($clientID, $propertiesValues);

// Set worksession creation date
setItemPropertyValue($definitions['worksessionCreationDate'], $itemTypeID, $workSessionID, $clientID, date('Y-m-d H:i:s'), $RSuserID);

// get tasks item types
$tasksItemTypeID      = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);
$tasksGroupItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'], $clientID);


// get the sum of all worksessions related with this task and user

// build filter properties to get the worksessions related with task and user
$filterPropertiesRelatedWS = array();
$filterPropertiesRelatedWS[] = array('ID' => $wsUserPropertyID, 'value' => $user);
$filterPropertiesRelatedWS[] = array('ID' => $wsTaskPropertyID, 'value' => $task);

// build return properties array
$returnPropertiesRelatedWS = array();
$returnPropertiesRelatedWS[] = array('ID' => $wsDurationPropertyID, 'name' => 'hours');

// get worksessions
$resultRelatedWS = getFilteredItemsIDs($itemTypeID, $clientID, $filterPropertiesRelatedWS, $returnPropertiesRelatedWS, '', true);

$sumHours = 0;

foreach ($resultRelatedWS as $RelatedWS) {
    // Acumulate the total time
    $sumHours = $sumHours + $RelatedWS['hours'];
}


// update parent tasks total time
$taskParentPropertyID            = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'          ], $clientID);
$taskCurrentTimePropertyID       = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'       ], $clientID);
$tasksStartDatePropertyID        = getClientPropertyID_RelatedWith_byName($definitions['taskStartDate'         ], $clientID);
$tasksEndDatePropertyID          = getClientPropertyID_RelatedWith_byName($definitions['taskEndDate'           ], $clientID);
$tasksGroupParentPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID'   ], $clientID);
$tasksGroupCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.currentTime'], $clientID);

//first update parent task
// get task current time
$taskCurrentTime = getItemPropertyValue($task, $taskCurrentTimePropertyID, $clientID);

// update task current time
setPropertyValueByID($taskCurrentTimePropertyID, $tasksItemTypeID, $task, $clientID, $sumHours, '', $RSuserID);

// get task parent
$taskGroup = getItemPropertyValue($task, $taskParentPropertyID, $clientID);

//update all ancestor groups
while ($taskGroup != '0') {
    // get taskGroup current time
    $taskGroupCurrentTime = getItemPropertyValue($taskGroup, $tasksGroupCurrentTimePropertyID, $clientID);

    // update taskGroup current time
    setPropertyValueByID($tasksGroupCurrentTimePropertyID, $tasksGroupItemTypeID, $taskGroup, $clientID, $taskGroupCurrentTime + $duration, '', $RSuserID);

    // get taskGroup parent
    $taskGroup = getItemPropertyValue($taskGroup, $tasksGroupParentPropertyID, $clientID);
}

//update parent task dates if required
if ($updateTaskDates == 1) {
    // get parent task start date and end date
    $parentStartDate = getItemPropertyValue($task, $tasksStartDatePropertyID, $clientID);
    $parentEndDate = getItemPropertyValue($task, $tasksEndDatePropertyID, $clientID);

    if (isBefore($startDate, $parentStartDate)) {
        // change the value into the database
        setPropertyValueByID($tasksStartDatePropertyID, $tasksItemTypeID, $task, $clientID, $startDate, '', $RSuserID);
    }

    if (isAfter($endDate, $parentEndDate)) {
        // change the value into the database
        setPropertyValueByID($tasksEndDatePropertyID, $tasksItemTypeID, $task, $clientID, $endDate, '', $RSuserID);
    }
}

// Build results array
$results['result'       ] = "OK";
$results['workSessionID'] = $workSessionID;
$results['internalID'   ] = $GLOBALS['RS_POST']['internalID'];
//$results['taskID'] = $task;

// And write XML Response back to the application
RSReturnArrayResults($results);
?>