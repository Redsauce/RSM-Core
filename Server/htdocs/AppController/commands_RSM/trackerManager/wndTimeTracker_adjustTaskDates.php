<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID' ]) ? $clientID  = $GLOBALS['RS_POST']['clientID' ] : dieWithError(400);
isset($GLOBALS['RS_POST']['taskID'   ]) ? $taskID    = $GLOBALS['RS_POST']['taskID'   ] : dieWithError(400);

// get item types
$tasksItemTypeID      = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);
$tasksGroupItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'], $clientID);

// get properties
$tasksParentPropertyID    = getClientPropertyID_RelatedWith_byName($definitions['taskParentID' ], $clientID);
$tasksStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskStartDate'], $clientID);
$tasksEndDatePropertyID   = getClientPropertyID_RelatedWith_byName($definitions['taskEndDate'  ], $clientID);

$tasksGroupParentPropertyID    = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID' ], $clientID);
$tasksGroupStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.startDate'], $clientID);
$tasksGroupEndDatePropertyID   = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.endDate'  ], $clientID);

// get task start date and end date
$taskStartDate = getItemPropertyValue($taskID, $tasksGroupStartDatePropertyID, $clientID);
$taskEndDate   = getItemPropertyValue($taskID, $tasksGroupEndDatePropertyID, $clientID);

$finalDates = array('startDate' => $taskStartDate, 'endDate' => $taskEndDate);

// build extra filter properties array
$extraFilters   = array();

// build extra properties array
$extraProperties   = array();
$extraProperties[] = array('ID' => $tasksGroupStartDatePropertyID, 'name' => 'startDate');
$extraProperties[] = array('ID' => $tasksGroupEndDatePropertyID  , 'name' => 'endDate'  );

// get tasks tree
$subTasksTree = getItemsTree($tasksGroupItemTypeID, $clientID, $tasksGroupParentPropertyID, $taskID, $extraFilters, $extraProperties);

$finalDates = adjustTasksDates($subTasksTree, $taskID, $taskStartDate, $taskEndDate); // adjust tasks dates

//check ancestor dates until root
$parentID = getItemPropertyValue($taskID, $tasksGroupParentPropertyID, $clientID);

if (strtotime($finalDates['startDate']) == false) {
    RSError("La fecha final no es una fecha");
}

while($parentID > 0){
    // get parent start date and end date
    $parentStartDate = getItemPropertyValue($parentID, $tasksGroupStartDatePropertyID, $clientID);
    $parentEndDate   = getItemPropertyValue($parentID, $tasksGroupEndDatePropertyID, $clientID);
        
    if (isBefore($finalDates['startDate'], $parentStartDate)) {
        // change the value into the database
        setPropertyValueByID($tasksGroupStartDatePropertyID, $tasksGroupItemTypeID, $parentID, $clientID, $finalDates['startDate'], '', $RSuserID);
    }
    
    if (isAfter($finalDates['endDate'], $parentEndDate)) {
        // change the value into the database
        setPropertyValueByID($tasksGroupEndDatePropertyID, $tasksGroupItemTypeID, $parentID, $clientID, $finalDates['endDate'], '', $RSuserID);
    }
    
    $parentID = getItemPropertyValue($parentID, $tasksGroupParentPropertyID, $clientID);
}

$results['result'] = 'OK';

// And write XML Response back to the application
RSReturnArrayResults($results);

// A function to adjust the tasksGroup dates of a tasksGroup tree
function adjustTasksDates($tree, $taskID, $startDate, $endDate) {
	global $RSuserID, $tasksItemTypeID, $tasksStartDatePropertyID, $tasksEndDatePropertyID, $tasksParentPropertyID, $tasksProjectPropertyID, $tasksGroupItemTypeID, $tasksGroupStartDatePropertyID, $tasksGroupEndDatePropertyID, $clientID;
	
    $initStartDate = $startDate;
    $initEndDate = $endDate;
	if (isset($tree[$taskID])) {
		// the taskGroup has childs
		foreach ($tree[$taskID] as $child) {
			// explore the childs
			$dateInterval = adjustTasksDates($tree, $child['ID'], $child['startDate'], $child['endDate']);
            
            // if the $startDate or $endDate are not datetime, assign the first value
            if (strtotime($startDate) == false) $startDate = $dateInterval['startDate'];
            if (strtotime($endDate  ) == false) $endDate   = $dateInterval['endDate'];
            
			if (isBefore($dateInterval['startDate'], $startDate)) {
				// update start date
				$startDate = $dateInterval['startDate'];
			}
				
			if (isAfter($dateInterval['endDate'], $endDate)) {
				// update end date
				$endDate = $dateInterval['endDate'];
			}	
		}
    }
    
    //get child tasks
    // build filter properties array
    $filterProperties = array();
    $filterProperties[] = array('ID' => $tasksParentPropertyID, 'value' => $taskID);
    
    // build return properties array
    $returnProperties = array();
    $returnProperties[] = array('ID' => $tasksStartDatePropertyID, 'name' => 'startDate');
    $returnProperties[] = array('ID' => $tasksEndDatePropertyID, 'name' => 'endDate');
    
    // get tasks
    $subTasks = getFilteredItemsIDs($tasksItemTypeID, $clientID, $filterProperties, $returnProperties);
    
    //check all child tasks
    foreach ($subTasks as $subTask) {
        if (isBefore($subTask['startDate'], $startDate) || !isValidSqlDate($startDate)) {
            // update start date
            $startDate = $subTask['startDate'];
        }
            
        if (isAfter($subTask['endDate'], $endDate) || !isValidSqlDate($endDate)) {
            // update end date
            $endDate = $subTask['endDate'];
        }   
    }
    
    //update dates if necessary
	if ($startDate != $initStartDate) {
		// change the value into the database
		setPropertyValueByID($tasksGroupStartDatePropertyID, $tasksGroupItemTypeID, $taskID, $clientID, $startDate, '', $RSuserID);
	}
	
	if ($endDate != $initEndDate) {
		// change the value into the database
		setPropertyValueByID($tasksGroupEndDatePropertyID, $tasksGroupItemTypeID, $taskID, $clientID, $endDate, '', $RSuserID);
	}
	
	return array('startDate' => $startDate, 'endDate' => $endDate);
}
?>