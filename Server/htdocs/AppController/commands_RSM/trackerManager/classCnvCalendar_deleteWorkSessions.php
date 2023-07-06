<?php
//***************************************************
//classCnvCalendar_deleteWorkSessions.php
// --> updated for the v.3.10
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
$wsID     = $GLOBALS['RS_POST']['workSessionID'];
$clientID = $GLOBALS['RS_POST']['clientID'     ];

//new switch for updating parent dates if necessary
$updateTaskDates = 1;

//check valid worksession
if ($wsID > 0) {

    // get the item types
    $worksItemTypeID       = getClientItemTypeID_RelatedWith_byName($definitions['worksessions' ], $clientID);
    $tasksItemTypeID       = getClientItemTypeID_RelatedWith_byName($definitions['tasks'        ], $clientID);
    $tasksGroupItemTypeID  = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'   ], $clientID);

    // get properties
    $wsDurationPropertyID  = getClientPropertyID_RelatedWith_byName($definitions['worksessionDuration' ], $clientID);
    $wsStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionStartDate'], $clientID);
    $wsTaskPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['worksessionTask'     ], $clientID);

    //check worksession exists
    if (count(getItems($worksItemTypeID, $clientID, true, $wsID)) > 0) {

        // get the worksession duration
        $duration = getPropertyValue($definitions['worksessionDuration'], $worksItemTypeID, $wsID, $clientID);

        // get the worksession task
        $wsTask = getPropertyValue($definitions['worksessionTask'], $worksItemTypeID, $wsID, $clientID);

        // delete worksession
        deleteItem($worksItemTypeID, $wsID, $clientID);

        // get the sum and dates of all worksessions related with this task
        // build filter properties to get the worksessions related with task
        $filterPropertiesRelatedWS = array();
        $filterPropertiesRelatedWS[] = array('ID' => $wsTaskPropertyID, 'value' => $wsTask);

        // build return properties array
        $returnPropertiesRelatedWS = array();
        $returnPropertiesRelatedWS[] = array('ID' => $wsDurationPropertyID,  'name' => 'hours');
        $returnPropertiesRelatedWS[] = array('ID' => $wsStartDatePropertyID, 'name' => 'start');

        // get worksessions
        $resultRelatedWS = getFilteredItemsIDs($worksItemTypeID, $clientID, $filterPropertiesRelatedWS, $returnPropertiesRelatedWS, '', true);

        // get parent tasks related properties
        $taskParentPropertyID            = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'          ], $clientID);
        $taskCurrentTimePropertyID       = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'       ], $clientID);
        $tasksStartDatePropertyID        = getClientPropertyID_RelatedWith_byName($definitions['taskStartDate'         ], $clientID);
        $tasksEndDatePropertyID          = getClientPropertyID_RelatedWith_byName($definitions['taskEndDate'           ], $clientID);
        $tasksGroupParentPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID'   ], $clientID);
        $tasksGroupCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.currentTime'], $clientID);

        // Set initial values
        $sumHours = 0;
        $realStartDate = getItemPropertyValue($wsTask, $tasksStartDatePropertyID, $clientID);
        $realEndDate   = getItemPropertyValue($wsTask, $tasksEndDatePropertyID, $clientID);

        foreach ($resultRelatedWS as $RelatedWS) {
            // Acumulate the total time
            $sumHours = $sumHours + $RelatedWS['hours'];

            //recalculate parent task dates if required
            if ($updateTaskDates == 1) {
                $startDate = explode(' ', trim($RelatedWS['start']))[0];

                //Check if WS start date lower than stored
                if ($realStartDate == "" || ($realStartDate != "" && isBefore($startDate, $realStartDate))) {
                    $realStartDate = $startDate;
                }

                // Calculate WS end date
                $endDateObj = date_create($RelatedWS['start']);
                date_modify($endDateObj, "+" . round($RelatedWS['hours'] * 60) . " minutes");
                $endDate = date_format($endDateObj, 'Y-m-d');

                //Check if WS end date higher than stored
                if ($realEndDate == "" || ($realStartDate != "" && isAfter($endDate, $realEndDate))) {
                    $realEndDate = $endDate;
                }
            }
        }

        //first update parent task
        // update task current time
        setPropertyValueByID($taskCurrentTimePropertyID, $tasksItemTypeID, $wsTask, $clientID, $sumHours, '', $RSuserID);

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

        //update parent task dates if required
        if ($updateTaskDates == 1) {
            // change the value into the database
            setPropertyValueByID($tasksStartDatePropertyID, $tasksItemTypeID, $wsTask, $clientID, $realStartDate, '', $RSuserID);

            // change the value into the database
            setPropertyValueByID($tasksEndDatePropertyID, $tasksItemTypeID, $wsTask, $clientID, $realEndDate, '', $RSuserID);
        }

        $results['workSessionID'] = $wsID;

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
