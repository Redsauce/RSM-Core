<?php
//***************************************************//
// RStools.php
//***************************************************//
// Description:
//	diverse utility functions.
//***************************************************//
// Version:
//	v1.0: getFinalDate and getNextWorkableDate functions
//  v2.0: isBefore, isAfter, isSameDate and isValidSqlDate functions
//  v3.0:
//		  splitDatetime, splitDate, splitTime functions,
//		  function to retrieve the datetime values,
//        getNextDay, sumTime and convertDurationToTime functions
//		  isTimeBefore, isTimeAfter and isSameTime functions
// 		  isDateBetween and isTimeBetween functions
// 		  isDateStrictlyBetween and isTimeStrictlyBetween functions
//		  getDayName, getMonthName functions

function checkType($data, $type) {

    switch ($type) {

        case 'text' :
            return $data;

        case 'longtext' :
            return $data;

        case 'date' :
            if (isValidSqlDate($data)) {
                return date('Y-m-d', strtotime($data));
            } else {
                return '0000-00-00';
            }

        case 'datetime' :
            if (isValidSqlDatetime($data)) {
                return date('Y-m-d H:i:s', strtotime($data));
            } else {
                return '0000-00-00 00:00:00';
            }

        case 'integer' :
            return intval($data);

        case 'float' :
            return floatval($data);

        case 'identifier' :
            return intval($data);

        case 'identifiers' :
            $arr = explode(',', $data);
            foreach ($arr as $i) {
                if (intval($i) < 1)
                    return 0;
            }
            return $data;

        case 'image' :
            return '';
            // TODO: implementar

        case 'file' :
            return '';
            // TODO: implementar

        case 'variant' :
            return '';
    }
}

function getFinalDate($startDate, $totalHours, $hoursPerDay) {
    $myTimeStamp = getNextWorkableDate(strtotime($startDate));
    while ($totalHours > $hoursPerDay) {
        $myTimeStamp = getNextWorkableDate($myTimeStamp + 86400);
        $totalHours -= $hoursPerDay;
    }
    return (date("Y-m-d", $myTimeStamp));
}

function getNextWorkableDate($myTimeStamp) {
    while (date("w", $myTimeStamp) <= 0 || date("w", $myTimeStamp) >= 6)
        $myTimeStamp += 86400;
    return ($myTimeStamp);
}

// Return true if $startDate is before $endDate (Sql dates)
function isBefore($startDate, $endDate) {

    if (!isValidSqlDate($startDate) || !isValidSqlDate($endDate)) {
        return false;
    }

    $sDate = explode('-', $startDate);
    $eDate = explode('-', $endDate);

    $startDateTimestamp = mktime(0, 0, 0, $sDate[1], $sDate[2], $sDate[0]);
    $endDateTimestamp = mktime(0, 0, 0, $eDate[1], $eDate[2], $eDate[0]);

    if ($startDateTimestamp < $endDateTimestamp) {
        return true;
    } else {
        return false;
    }
}

// Return true if $startDate is after $endDate (Sql dates)
function isAfter($startDate, $endDate) {

    if (!isValidSqlDate($startDate) || !isValidSqlDate($endDate)) {
        return false;
    }

    $sDate = explode('-', $startDate);
    $eDate = explode('-', $endDate);

    $startDateTimestamp = mktime(0, 0, 0, $sDate[1], $sDate[2], $sDate[0]);
    $endDateTimestamp = mktime(0, 0, 0, $eDate[1], $eDate[2], $eDate[0]);

    if ($startDateTimestamp > $endDateTimestamp) {
        return true;
    } else {
        return false;
    }
}

// Return true if the dates are the same (Sql dates)
function isSameDate($date1, $date2) {

    if ((!isValidSqlDate($date1)) || (!isValidSqlDate($date2)))
        return false;

    $sDate = explode('-', $date1);
    $eDate = explode('-', $date2);

    // check years, months and days
    if (($sDate[0] == $eDate[0]) && ($sDate[1] == $eDate[1]) && ($sDate[2] == $eDate[2]))
        return true;

    return false;
}

// Return true if date passed is between the start date and the end date passed (Sql dates)
function isDateBetween($date, $startDate, $endDate) {

    if ((isAfter($date, $startDate) && isBefore($date, $endDate)) || isSameDate($date, $startDate) || isSameDate($date, $endDate)) {
        return true;
    } else {
        return false;
    }
}

// Return true if date passed is strictly between the start date and the end date passed (Sql dates)
function isDateStrictlyBetween($date, $startDate, $endDate) {

    if (isAfter($date, $startDate) && isBefore($date, $endDate)) {
        return true;
    } else {
        return false;
    }
}

// Return true if $startTime is before $endTime
function isTimeBefore($startTime, $endTime) {

    $sTime = explode(':', $startTime);
    $eTime = explode(':', $endTime);

    // check hours
    if ($sTime[0] < $eTime[0]) {
        return true;
    } elseif ($sTime[0] > $eTime[0]) {
        return false;
    }
    // check minutes
    if ($sTime[1] < $eTime[1]) {
        return true;
    } elseif ($sTime[1] > $eTime[1]) {
        return false;
    }
    // check seconds
    if ($sTime[2] < $eTime[2]) {
        return true;
    } elseif ($sTime[2] > $eTime[2]) {
        return false;
    }

    return false;
}

// Return true if $startTime is after $endTime
function isTimeAfter($startTime, $endTime) {

    $sTime = explode(':', $startTime);
    $eTime = explode(':', $endTime);

    // check hours
    if ($sTime[0] > $eTime[0]) {
        return true;
    } elseif ($sTime[0] < $eTime[0]) {
        return false;
    }
    // check minutes
    if ($sTime[1] > $eTime[1]) {
        return true;
    } elseif ($sTime[1] < $eTime[1]) {
        return false;
    }
    // check seconds
    if ($sTime[2] > $eTime[2]) {
        return true;
    } elseif ($sTime[2] < $eTime[2]) {
        return false;
    }

    return false;
}

// Return true if the times are the same
function isSameTime($time1, $time2) {

    $sTime = explode(':', $time1);
    $eTime = explode(':', $time2);

    // check hours, minutes and seconds
    if (($sTime[0] == $eTime[0]) && ($sTime[1] == $eTime[1]) && ($sTime[2] == $eTime[2]))
        return true;

    return false;
}

// Return true if time passed is between the start time and the end time passed
function isTimeBetween($time, $startTime, $endTime) {

    if ((isTimeAfter($time, $startTime) && isTimeBefore($time, $endTime)) || isSameTime($time, $startTime) || isSameTime($time, $endTime)) {
        return true;
    } else {
        return false;
    }
}

// Return true if time passed is strictly between the start time and the end time passed
function isTimeStrictlyBetween($time, $startTime, $endTime) {

    if (isTimeAfter($time, $startTime) && isTimeBefore($time, $endTime)) {
        return true;
    } else {
        return false;
    }
}

// Return true if $date is a valid Sql date
function isValidSqlDate($date) {

    $dateArr = explode('-', $date);

    if (count($dateArr) != 3) {
      return false;
    }

    foreach ($dateArr as $component) {
      if (!is_numeric($component)) {
        return false;
      }
    }

    return checkdate($dateArr[1], $dateArr[2], $dateArr[0]);
}

// Return true if $time is a valid Sql time
function isValidSqlTime($time) {

    $timeArr = explode(':', $time);

    if (count($timeArr) != 3)
        return false;

    if ((intval($timeArr[0]) < 24 && intval($timeArr[0]) >= 0) && (intval($timeArr[1]) < 60 && intval($timeArr[1]) >= 0) && (intval($timeArr[2]) < 60 && intval($timeArr[2]) >= 0)) {
        return true;
    } else {
        return false;
    }
}

// Return true if $datetime is a valid Sql datetime
function isValidSqlDatetime($datetime) {

    $dateAndTime = explode(' ', $datetime);

    if (count($dateAndTime) != 2)
        return false;
    if (!isValidSqlDate($dateAndTime[0]))
        return false;
    if (!isValidSqlTime($dateAndTime[1]))
        return false;

    return true;
}

// Return an array containing separated date and time of the (date[ time]) datetime string passed
function splitDatetime($datetime) {

    $datetimeArr = explode(' ', $datetime);

    if (count($datetimeArr) != 2) {
        return null;
    } else {
        return array('date' => $datetimeArr[0], 'time' => $datetimeArr[1]);
    }
}

// Return an array containing year, month and day of the (yyyy[-mm][-dd]) date string passed (Sql date)
function splitDate($date) {

    $dateArr = explode('-', $date);

    if (count($dateArr) != 3) {
        return null;
    } else {
        return array('year' => $dateArr[0], 'month' => $dateArr[1], 'day' => $dateArr[2]);
    }
}

// Return an array containing hours, minutes and seconds of the (hours[-mins][-secs]) time string passed
function splitTime($time) {

    $timeArr = explode(':', $time);

    switch (count($timeArr)) {
        case 1 :
            return array('hours' => $timeArr[0]);
        case 2 :
            return array('hours' => $timeArr[0], 'mins' => $timeArr[1]);
        case 3 :
            return array('hours' => $timeArr[0], 'mins' => $timeArr[1], 'secs' => $timeArr[2]);

        default :
            return null;
    }
}

// Functions to retrieve the single values of the date and time passed
function getDateFromSplitDatetime($splitDatetime) {
    if (!isset($splitDatetime['date']))
        return null;
    return $splitDatetime['date'];
}

function getTimeFromSplitDatetime($splitDatetime) {
    if (!isset($splitDatetime['time']))
        return null;
    return $splitDatetime['time'];
}

function getYearFromSplitDate($splitDate) {
    if (!isset($splitDate['year']))
        return null;
    return $splitDate['year'];
}

function getMonthFromSplitDate($splitDate) {
    if (!isset($splitDate['month']))
        return null;
    return $splitDate['month'];
}

function getDayFromSplitDate($splitDate) {
    if (!isset($splitDate['day']))
        return null;
    return $splitDate['day'];
}

function getHoursFromSplitTime($splitTime) {
    if (!isset($splitTime['hours']))
        return null;
    return $splitTime['hours'];
}

function getMinsFromSplitTime($splitTime) {
    if (!isset($splitTime['mins']))
        return null;
    return $splitTime['mins'];
}

function getSecsFromSplitTime($splitTime) {
    if (!isset($splitTime['secs']))
        return null;
    return $splitTime['secs'];
}

// Return the date representing the next day of the 'yyyy-mm-dd' date passed (Sql date)
function getNextDay($date) {

    $splitDate = splitDate($date);

    return date('Y-m-d', mktime(0, 0, 0, $splitDate['month'], $splitDate['day'] + 1, $splitDate['year']));
}

// Return the name of the day of the 'yyyy-mm-dd' date passed (Sql date)
function getDayName($date) {

    $splitDate = splitDate($date);

    return date("l", mktime(0, 0, 0, $splitDate['month'], $splitDate['day'], $splitDate['year']));
}

// Return the name of the month of the 'yyyy-mm-dd' date passed (Sql date)
function getMonthName($date) {

    $splitDate = splitDate($date);

    return date("F", mktime(0, 0, 0, $splitDate['month'], $splitDate['day'], $splitDate['year']));
}

// Return an array containing hours, minutes and seconds obtained by the sum of the time strings passed
function sumTime($time1, $time2) {

    // retrieve hours, minutes and seconds of the time1
    $splitTime1 = splitTime($time1);
    if ($splitTime1 == null)
        return null;
    // retrieve hours, minutes and seconds of the time2
    $splitTime2 = splitTime($time2);
    if ($splitTime2 == null)
        return null;

    // now we have two valid time values.. sum them
    $time1_totalSeconds = 0;
    $t = 3600;
    foreach ($splitTime1 as $st) {
        $time1_totalSeconds = $time1_totalSeconds + ($st * $t);
        $t = $t / 60;
    }
    $time2_totalSeconds = 0;
    $t = 3600;
    foreach ($splitTime2 as $st) {
        $time2_totalSeconds = $time2_totalSeconds + ($st * $t);
        $t = $t / 60;
    }

    $sumTime_totalSeconds = $time1_totalSeconds + $time2_totalSeconds;

    $sumTimeHours = floor($sumTime_totalSeconds / 3600);
    $sumTimeMins = floor(floor(($sumTime_totalSeconds % 3600)) / 60);
    $sumTimeSecs = floor(floor(($sumTime_totalSeconds % 3600)) % 60);

    return ($sumTimeHours . ":" . $sumTimeMins . ":" . $sumTimeSecs);
}

// Convert a duration to time (for example: 1.75 - one hour and 3/4 - ---> 1:45 - one hour and 45 minutes)
function convertDurationToTime($duration) {

    if (strpos($duration, '.') > 0) {
        $durationArr = explode('.', round($duration, 2));
        return $durationArr[0] . ':' . round(($durationArr[1] * 60) / 100);
    } else {
        return $duration;
    }
}

// Return an associative array with the info about a Y-m-d H:i:s datetime
function parseDatetime($datetime) {

    $dateAndTime = explode(' ', $datetime);
    $dateInfo = explode('-', $dateAndTime[0]);
    $timeInfo = explode(':', $dateAndTime[1]);

    return array('year' => $dateInfo[0], 'month' => $dateInfo[1], 'day' => $dateInfo[2], 'hour' => $timeInfo[0], 'minute' => $timeInfo[1], 'second' => $timeInfo[2]);
}

function dieWithError($code, $message = null) {

    switch ($code) {

        case 400 :
            $errorString = "400 Bad Request";
            header("HTTP/1.1 " . $errorString, true, 400);
            break;

        case 401 :
            $errorString = "401 Unauthorized";
            header("HTTP/1.1 " . $errorString, true, 401);
            break;

        case 403 :
            $errorString = "403 Forbidden";
            header("HTTP/1.1 " . $errorString, true, 403);
            break;

        case 404 :
            $errorString = "404 Page not found";
            header("HTTP/1.1 " . $errorString, true, 404);
            break;

        case 500 :
            $errorString = "500 Internal Server Error";
            header("HTTP/1.1 " . $errorString, true, 500);
            break;

        default :
            $errorString = "400 Bad Request";
            header("HTTP/1.1 " . $errorString, true, 400);
            break;
    }

	// Si hay info extra la mostramos por la salida de error
	if($message != null) {
		RSError("dieWithError: " . $errorString . ". " . $message);
	}

    die($errorString);
}

function is_base64($s){
    // Check if there are valid base64 characters
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) return false;

    // Decode the string in strict mode and check the results
    $decoded = base64_decode($s, true);
    if(false === $decoded) return false;

    // Encode the string again
    if(base64_encode($decoded) != $s) return false;

    return true;
}
?>
