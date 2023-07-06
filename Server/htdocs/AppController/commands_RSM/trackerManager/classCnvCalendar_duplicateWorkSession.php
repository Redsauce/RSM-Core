<?php
// A little function that returns true if the day passed is cutted off the interval...
function isDayCuttedOff($dayName)
{
    global $daysToCutOff;

    foreach ($daysToCutOff as $dayCuttedOff) {
        if ($dayName == $dayCuttedOff) {
            return true;
        }
    }
    return false;
}

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID'     ]) ? $clientID = $GLOBALS['RS_POST']['clientID'     ] : dieWithError(400);
isset($GLOBALS['RS_POST']['worksessionID']) ? $wsID     = $GLOBALS['RS_POST']['worksessionID'] : dieWithError(400);
isset($GLOBALS['RS_POST']['endDate'      ]) ? $endDate  = $GLOBALS['RS_POST']['endDate'      ] : dieWithError(400);
isset($GLOBALS['RS_POST']['days'         ]) ? $days     = $GLOBALS['RS_POST']['days'         ] : dieWithError(400);

//new switch for updating parend dates if necessary
$updateTaskDates = 1;

//check valid days
if (preg_match("/^[01]{7}$/", $days) == 1) {

    //check valid date
    if (isValidSqlDate($endDate)) {

        //check valid worksession
        if ($wsID > 0) {

            // get worksessions item type
            $itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['worksessions'], $clientID);

            //check worksession exists
            if (count(getItems($itemTypeID, $clientID, true, $wsID)) > 0) {

                // get tasks item types
                $tasksItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);
                $tasksGroupItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasksGroup'], $clientID);

                // get properties
                $wsUserPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionUser'], $clientID);
                $wsStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionStartDate'], $clientID);
                $wsDurationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionDuration'], $clientID);
                $wsTaskPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionTask'], $clientID);
                $wsDescriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['worksessionDescription'], $clientID);
                $taskParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'], $clientID);
                $taskCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'], $clientID);
                $tasksStartDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskStartDate'], $clientID);
                $tasksEndDatePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskEndDate'], $clientID);
                $tasksGroupParentPropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.parentID'], $clientID);
                $tasksGroupCurrentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['tasksGroup.currentTime'], $clientID);

                // build an array containing the days to cut off
                $daysToCutOff = array();
                if ($days[0] == '0') {
                    $daysToCutOff[] = 'Monday';
                }
                if ($days[1] == '0') {
                    $daysToCutOff[] = 'Tuesday';
                }
                if ($days[2] == '0') {
                    $daysToCutOff[] = 'Wednesday';
                }
                if ($days[3] == '0') {
                    $daysToCutOff[] = 'Thursday';
                }
                if ($days[4] == '0') {
                    $daysToCutOff[] = 'Friday';
                }
                if ($days[5] == '0') {
                    $daysToCutOff[] = 'Saturday';
                }
                if ($days[6] == '0') {
                    $daysToCutOff[] = 'Sunday';
                }

                // get properties values
                $wsUser = getItemPropertyValue($wsID, $wsUserPropertyID, $clientID);
                $wsBegin = getItemPropertyValue($wsID, $wsStartDatePropertyID, $clientID);
                $wsDuration = getItemPropertyValue($wsID, $wsDurationPropertyID, $clientID);
                $wsTask = getItemPropertyValue($wsID, $wsTaskPropertyID, $clientID);
                $wsDescription = getItemPropertyValue($wsID, $wsDescriptionPropertyID, $clientID);

                // retrieve posted worksession start date
                $wsStartDateAndTime = splitDatetime($wsBegin);
                // posted worksession start date
                $wsStartDate = $wsStartDateAndTime['date'];
                // posted worksession start time
                $wsStartTime = $wsStartDateAndTime['time'];
                // set start date interval to the next day
                $startDate = getNextDay($wsStartDate);

                // retrieve posted worksession begin and end timestamps
                $wsBeginArr = parseDatetime($wsBegin);

                // get the worksession begin date timestamp
                $wsBeginTimestamp = mktime($wsBeginArr['hour'], $wsBeginArr['minute'], $wsBeginArr['second'], $wsBeginArr['month'], $wsBeginArr['day'], $wsBeginArr['year']);

                // convert the worksession duration in seconds
                $durationSeconds = $wsDuration * 3600;

                // get the worksession end date timestamp
                $wsEndTimestamp = $wsBeginTimestamp + $durationSeconds;

                // -- GET THE USER WORKSESSIONS CREATED BETWEEN THE START DATE AND THE END DATE
                // build filter properties array
                $filterProperties = array();
                $filterProperties[] = array('ID' => $wsUserPropertyID, 'value' => $wsUser);
                $filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $startDate, 'mode' => 'SAME_OR_AFTER');
                $filterProperties[] = array('ID' => $wsStartDatePropertyID, 'value' => $endDate, 'mode' => 'SAME_OR_BEFORE');

                // build return properties array
                $returnProperties = array();
                $returnProperties[] = array('ID' => $wsStartDatePropertyID, 'name' => 'startDate');
                $returnProperties[] = array('ID' => $wsDurationPropertyID, 'name' => 'duration');

                // get worksessions
                $worksessions = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties);

                // check conflicts
                while ($uws = $worksessions->fetch_assoc()) {

                    $uwsStartDateAndTime = splitDatetime($uws['startDate']);

                    if (isDayCuttedOff(getDayName($uwsStartDateAndTime['date']))) {
                        continue;
                    //  skip the rest and jump to the next worksession
                    }


                    // retrieve worksession start date timestamp
                    $uwsBeginArr = parseDatetime($uws['startDate']);

                    // get a timestamp formed by the worksession time and the posted worksession start date (it will be used to compare the times)
                    $uwsCompBeginTimestamp = mktime($uwsBeginArr['hour'], $uwsBeginArr['minute'], $uwsBeginArr['second'], $wsBeginArr['month'], $wsBeginArr['day'], $wsBeginArr['year']);

                    // convert the worksession duration in seconds
                    $uwsDurationSeconds = $uws['duration'] * 3600;

                    // get a timestamp formed by the worksession end time and the posted worksession start date (it will be used to compare the times)
                    $uwsCompEndTimestamp = $uwsCompBeginTimestamp + $uwsDurationSeconds;

                    if (!($wsBeginTimestamp >= $uwsCompEndTimestamp || $wsEndTimestamp <= $uwsCompBeginTimestamp)) {
                        // there is a conflict...
                        $results['result'] = 'NOK';
                        RSReturnArrayResults($results);
                        exit ;
                    }
                }

                // --- DUPLICATE POSTED WORKSESSION
                // there is no conflict! duplicate posted worksession along the posted interval
                $dates = array();

                // initialize current date to the worksession start date
                $currentDate = $wsStartDate;

                do {

                    // increment current date
                    $currentDate = getNextDay($currentDate);

                    // check if the date must be added to the array
                    if (!isDayCuttedOff(getDayName($currentDate))) {
                        $dates[] = $currentDate . ' ' . $wsStartTime;
                    }

                } while (isDateStrictlyBetween($currentDate, $wsStartDate, $endDate));

                // duplicate worksession
                $numCopies = count($dates);

                if ($numCopies == 1) {

                    $newWsID = duplicateItem($itemTypeID, $wsID, $clientID);

                    // adjust new worksession start date and creation date
                    setPropertyValueByID($wsStartDatePropertyID, $itemTypeID, $newWsID, $clientID, $dates[0], '', $RSuserID);
                    setItemPropertyValue($definitions['worksessionCreationDate'], $itemTypeID, $newWsID, $clientID, date("Y-m-d H:i:s"), '', $RSuserID);

                    $newWsDuration = $wsDuration;

                } else {

                    $newWsIDs = duplicateItem($itemTypeID, $wsID, $clientID, $numCopies);

                    $newWsDuration = 0;

                    for ($i = 0; $i < $numCopies; $i++) {

                        // adjust new worksessions start date and creation date
                        setPropertyValueByID($wsStartDatePropertyID, $itemTypeID, $newWsIDs[$i], $clientID, $dates[$i], '', $RSuserID);
                        setItemPropertyValue($definitions['worksessionCreationDate'], $itemTypeID, $newWsIDs[$i], $clientID, date("Y-m-d H:i:s"), $RSuserID);

                        // increase parent task current time
                        $newWsDuration = $newWsDuration + $wsDuration;
                    }
                }

                //first update parent task
                // get task current time
                $taskCurrentTime = getItemPropertyValue($wsTask, $taskCurrentTimePropertyID, $clientID);

                // update parent task current time
                setPropertyValueByID($taskCurrentTimePropertyID, $tasksItemTypeID, $wsTask, $clientID, $taskCurrentTime + $newWsDuration, '', $RSuserID);

                // get task parent
                $taskGroup = getItemPropertyValue($wsTask, $taskParentPropertyID, $clientID);

                //update all ancestor groups
                while ($taskGroup != '0') {
                    // get taskGroup current time
                    $taskGroupCurrentTime = getItemPropertyValue($taskGroup, $tasksGroupCurrentTimePropertyID, $clientID);

                    // update taskGroup current time
                    setPropertyValueByID($tasksGroupCurrentTimePropertyID, $tasksGroupItemTypeID, $taskGroup, $clientID, $taskGroupCurrentTime + $newWsDuration, '', $RSuserID);

                    // get taskGroup parent
                    $taskGroup = getItemPropertyValue($taskGroup, $tasksGroupParentPropertyID, $clientID);
                }

                //update parent task dates if required
                if ($updateTaskDates == 1) {
                    // get parent task start date and end date
                    $parentStartDate = getItemPropertyValue($wsTask, $tasksStartDatePropertyID, $clientID);
                    $parentEndDate = getItemPropertyValue($wsTask, $tasksEndDatePropertyID, $clientID);

                    //check fists worksession startDate
                    if (isBefore($dates[0], $parentStartDate)) {
                        // change the value into the database
                        setPropertyValueByID($tasksStartDatePropertyID, $tasksItemTypeID, $wsTask, $clientID, $dates[0], '', $RSuserID);
                    }

                    //calculate last worksession endDate
                    $endDateObj = date_create(end($dates));
                    date_modify($endDateObj, "+" . round($wsDuration * 60) . " minutes");
                    $endDate = date_format($endDateObj, 'Y-m-d H:i:s');

                    if (isAfter($endDate, $parentEndDate)) {
                        // change the value into the database
                        setPropertyValueByID($tasksEndDatePropertyID, $tasksItemTypeID, $wsTask, $clientID, $endDate, '', $RSuserID);
                    }
                }

                $results['result'] = 'OK';

            } else {
                $results['result'] = "NOK";
                $results['description'] = "WORKSESSION NOT EXISTS";
                $results['workSessionID'] = $wsID;
            }
        } else {
            $results['result'] = "NOK";
            $results['description'] = "INVALID WORKSESSION";
        }
    } else {
        $results['result'] = "NOK";
        $results['description'] = "INVALID END DATE";
    }
} else {
    $results['result'] = "NOK";
    $results['description'] = "INVALID DAYS";
}

// And write XML Response back to the application
RSReturnArrayResults($results);
