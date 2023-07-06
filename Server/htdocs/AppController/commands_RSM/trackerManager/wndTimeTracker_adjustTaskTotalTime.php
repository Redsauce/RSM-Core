<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID']) ? $clientID  = $GLOBALS['RS_POST']['clientID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['taskID']) ? $taskID    = $GLOBALS['RS_POST']['taskID'] : dieWithError(400);

// get item types
$tasksItemTypeID      = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);
$tasksGroupItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'], $clientID);

// get properties
$tasksParentPropertyID    = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'], $clientID);
$tasksTotalTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'], $clientID);

$tasksGroupParentPropertyID    = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID'], $clientID);
$tasksGroupTotalTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.currentTime'], $clientID);

// get task total time
$taskTotalTime = getItemPropertyValue($taskID, $tasksGroupTotalTimePropertyID, $clientID);

// build extra filter properties array
$extraFilters   = array();

// build extra properties array
$extraProperties   = array();
$extraProperties[] = array('ID' => $tasksGroupTotalTimePropertyID, 'name' => 'totalTime');

// get tasks tree
$subTasksTree = getItemsTree($tasksGroupItemTypeID, $clientID, $tasksGroupParentPropertyID, $taskID, $extraFilters, $extraProperties);

$totalTimeDiff = adjustTasksTotalTime($subTasksTree, $taskID, $taskTotalTime) - $taskTotalTime; // adjust tasks total time and get difference from previous total time

//apply time difference to ancestors until root
if ($totalTimeDiff != 0) {
    $parentID = getItemPropertyValue($taskID, $tasksGroupParentPropertyID, $clientID);

    while ($parentID > 0) {
        // add total time difference to parent
        setPropertyValueByID($tasksGroupTotalTimePropertyID, $tasksGroupItemTypeID, $parentID, $clientID, getItemPropertyValue($parentID, $tasksGroupTotalTimePropertyID, $clientID) + $totalTimeDiff, '', $RSuserID);

        $parentID = getItemPropertyValue($parentID, $tasksGroupParentPropertyID, $clientID);
    }
}

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);

// A function to adjust the tasks total time of a tasks tree
function adjustTasksTotalTime($tree, $taskID, $totalTime)
{
    global $RSuserID, $tasksItemTypeID, $tasksTotalTimePropertyID, $tasksParentPropertyID, $tasksProjectPropertyID, $tasksGroupItemTypeID, $tasksGroupTotalTimePropertyID, $clientID;

    $oldTotalTime = $totalTime;
    $totalTime = 0;

    if (isset($tree[$taskID])) {
        // the task has childs
        foreach ($tree[$taskID] as $child) {
            // explore the childs
            $totalTime += adjustTasksTotalTime($tree, $child['ID'], $child['totalTime']);
        }
    }

    // get child tasks
    // build filter properties array
    $filterProperties   = array();
    $filterProperties[] = array('ID' => $tasksParentPropertyID,  'value' => $taskID);

    // build return properties array
    $returnProperties   = array();
    $returnProperties[] = array('ID' => $tasksTotalTimePropertyID, 'name' => 'totalTime');

    // get tasks
    $subTasks = getFilteredItemsIDs($tasksItemTypeID, $clientID, $filterProperties, $returnProperties);

    //add total time from all child tasks
    foreach ($subTasks as $subTask) {
        $totalTime += $subTask['totalTime'];
    }

    if ($totalTime != $oldTotalTime) {
        // change the value into the database
        setPropertyValueByID($tasksGroupTotalTimePropertyID, $tasksGroupItemTypeID, $taskID, $clientID, $totalTime, '', $RSuserID);
    }

    return $totalTime;
}
