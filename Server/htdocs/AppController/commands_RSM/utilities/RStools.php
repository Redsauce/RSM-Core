<?php
//***************************************************//
// RStools.php
//***************************************************//
// Description:
//  diverse utility functions.
//***************************************************//
// Version:
//  v1.0: getFinalDate and getNextWorkableDate functions
//  v2.0: isBefore, isAfter, isSameDate and isValidSqlDate functions
//  v3.0:
//        splitDatetime, splitDate, splitTime functions,
//        function to retrieve the datetime values,
//        getNextDay, sumTime and convertDurationToTime functions
//        isTimeBefore, isTimeAfter and isSameTime functions
//        isDateBetween and isTimeBetween functions
//        isDateStrictlyBetween and isTimeStrictlyBetween functions
//        getDayName, getMonthName functions

function checkType($data, $type)
{

    $result = "";

    switch ($type) {

        case 'text':
        case 'longtext':
            $result = $data;
            break;

        case 'date':
            if (isValidSqlDate($data)) {
                $result = date('Y-m-d', strtotime($data));
            } else {
                $result = '0000-00-00';
            }
            break;

        case 'datetime':
            if (isValidSqlDatetime($data)) {
                $result = date('Y-m-d H:i:s', strtotime($data));
            } else {
                $result = '0000-00-00 00:00:00';
            }
            break;

        case 'integer':
            $result = intval($data);
            break;

        case 'float':
            $result = floatval($data);
            break;

        case 'identifier':
            $result = intval($data);
            break;

        case 'identifiers':
            $arr = explode(',', $data);
            foreach ($arr as $i) {
                if (intval($i) < 1) {
                    return 0;
                }
            }
            $result = $data;
            break;

        case 'image':
            $result = $data;
            // TODO: implementar
            break;

        case 'file':
            $result = $data;
            // TODO: implementar
            break;

        case 'variant':
            $result = $data;
            // TODO: implementar
            break;

        default:
            $result = $data;
            break;
    }

    return $result;
}

function getFinalDate($startDate, $totalHours, $hoursPerDay)
{
    $myTimeStamp = getNextWorkableDate(strtotime($startDate));
    while ($totalHours > $hoursPerDay) {
        $myTimeStamp = getNextWorkableDate($myTimeStamp + 86400);
        $totalHours -= $hoursPerDay;
    }
    return (date("Y-m-d", $myTimeStamp));
}

function getNextWorkableDate($myTimeStamp)
{
    while (date("w", $myTimeStamp) <= 0 || date("w", $myTimeStamp) >= 6) {
        $myTimeStamp += 86400;
    }
    return ($myTimeStamp);
}

// Return true if $startDate is before $endDate (Sql dates)
function isBefore($startDate, $endDate)
{

    if (!isValidSqlDate($startDate) || !isValidSqlDate($endDate)) {
        return false;
    }

    $sDate = explode('-', $startDate);
    $eDate = explode('-', $endDate);

    $startDateTimestamp = mktime(0, 0, 0, $sDate[1], $sDate[2], $sDate[0]);
    $endDateTimestamp = mktime(0, 0, 0, $eDate[1], $eDate[2], $eDate[0]);

    return $startDateTimestamp < $endDateTimestamp;
}

// Return true if $startDate is after $endDate (Sql dates)
function isAfter($startDate, $endDate)
{

    if (!isValidSqlDate($startDate) || !isValidSqlDate($endDate)) {
        return false;
    }

    $sDate = explode('-', $startDate);
    $eDate = explode('-', $endDate);

    $startDateTimestamp = mktime(0, 0, 0, $sDate[1], $sDate[2], $sDate[0]);
    $endDateTimestamp = mktime(0, 0, 0, $eDate[1], $eDate[2], $eDate[0]);

    return $startDateTimestamp > $endDateTimestamp;
}

// Return true if the dates are the same (Sql dates)
function isSameDate($date1, $date2)
{

    if ((!isValidSqlDate($date1)) || (!isValidSqlDate($date2))) {
        return false;
    }

    $sDate = explode('-', $date1);
    $eDate = explode('-', $date2);

    // check years, months and days
    return ($sDate[0] == $eDate[0]) && ($sDate[1] == $eDate[1]) && ($sDate[2] == $eDate[2]);
}

// Return true if date passed is between the start date and the end date passed (Sql dates)
function isDateBetween($date, $startDate, $endDate)
{

    return (isAfter($date, $startDate) && isBefore($date, $endDate)) || isSameDate($date, $startDate)
        || isSameDate($date, $endDate);
}

// Return true if date passed is strictly between the start date and the end date passed (Sql dates)
function isDateStrictlyBetween($date, $startDate, $endDate)
{

    return isAfter($date, $startDate) && isBefore($date, $endDate);
}

// Return true if $startTime is before $endTime
function isTimeBefore($startTime, $endTime)
{

    $sTime = explode(':', $startTime);
    $eTime = explode(':', $endTime);

    $isBefore = false;

    // check hours
    if ($sTime[0] < $eTime[0]) {
        $isBefore = true;
    } elseif ($sTime[0] > $eTime[0]) {
        $isBefore = false;
    }
    // check minutes
    if ($sTime[1] < $eTime[1]) {
        $isBefore = true;
    } elseif ($sTime[1] > $eTime[1]) {
        $isBefore = false;
    }
    // check seconds
    if ($sTime[2] < $eTime[2]) {
        $isBefore = true;
    } elseif ($sTime[2] > $eTime[2]) {
        $isBefore = false;
    }

    return $isBefore;
}

// Return true if $startTime is after $endTime
function isTimeAfter($startTime, $endTime)
{

    $sTime = explode(':', $startTime);
    $eTime = explode(':', $endTime);

    $isAfter = false;

    // check hours
    if ($sTime[0] > $eTime[0]) {
        $isAfter = true;
    } elseif ($sTime[0] < $eTime[0]) {
        $isAfter = false;
    }
    // check minutes
    if ($sTime[1] > $eTime[1]) {
        $isAfter = true;
    } elseif ($sTime[1] < $eTime[1]) {
        $isAfter = false;
    }
    // check seconds
    if ($sTime[2] > $eTime[2]) {
        $isAfter = true;
    } elseif ($sTime[2] < $eTime[2]) {
        $isAfter = false;
    }

    return $isAfter;
}

// Return true if the times are the same
function isSameTime($time1, $time2)
{

    $sTime = explode(':', $time1);
    $eTime = explode(':', $time2);

    // check hours, minutes and seconds
    return ($sTime[0] == $eTime[0]) && ($sTime[1] == $eTime[1]) && ($sTime[2] == $eTime[2]);
}

// Return true if time passed is between the start time and the end time passed
function isTimeBetween($time, $startTime, $endTime)
{

    return (isTimeAfter($time, $startTime) && isTimeBefore($time, $endTime)) || isSameTime($time, $startTime)
        || isSameTime($time, $endTime);
}

// Return true if time passed is strictly between the start time and the end time passed
function isTimeStrictlyBetween($time, $startTime, $endTime)
{

    return isTimeAfter($time, $startTime) && isTimeBefore($time, $endTime);
}

// Return true if $date is a valid Sql date
function isValidSqlDate($date)
{

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
function isValidSqlTime($time)
{

    $timeArr = explode(':', $time);

    if (count($timeArr) != 3) {
        return false;
    }

    return (intval($timeArr[0]) < 24 && intval($timeArr[0]) >= 0) && (intval($timeArr[1]) < 60
        && intval($timeArr[1]) >= 0) && (intval($timeArr[2]) < 60 && intval($timeArr[2]) >= 0);
}

// Return true if $datetime is a valid Sql datetime
function isValidSqlDatetime($datetime)
{
    $dateAndTime = explode(' ', $datetime);

    if (count($dateAndTime) == 2 && isValidSqlDate($dateAndTime[0]) && isValidSqlTime($dateAndTime[1])) {
        return true;
    }

    return false;
}

// Return an array containing separated date and time of the (date[ time]) datetime string passed
function splitDatetime($datetime)
{

    $datetimeArr = explode(' ', $datetime);

    if (count($datetimeArr) != 2) {
        return null;
    } else {
        return array('date' => $datetimeArr[0], 'time' => $datetimeArr[1]);
    }
}

// Return an array containing year, month and day of the (yyyy[-mm][-dd]) date string passed (Sql date)
function splitDate($date)
{

    $dateArr = explode('-', $date);

    if (count($dateArr) != 3) {
        return null;
    } else {
        return array('year' => $dateArr[0], 'month' => $dateArr[1], 'day' => $dateArr[2]);
    }
}

// Return an array containing hours, minutes and seconds of the (hours[-mins][-secs]) time string passed
function splitTime($time)
{

    $timeArr = explode(':', $time);
    $result = array();

    switch (count($timeArr)) {
        case 1:
            $result = array('hours' => $timeArr[0]);
            break;
        case 2:
            $result = array('hours' => $timeArr[0], 'mins' => $timeArr[1]);
            break;
        case 3:
            $result = array('hours' => $timeArr[0], 'mins' => $timeArr[1], 'secs' => $timeArr[2]);
            break;

        default:
            return null;
    }
    return $result;
}

// Functions to retrieve the single values of the date and time passed
function getDateFromSplitDatetime($splitDatetime)
{
    if (!isset($splitDatetime['date'])) {
        return null;
    }
    return $splitDatetime['date'];
}

function getTimeFromSplitDatetime($splitDatetime)
{
    if (!isset($splitDatetime['time'])) {
        return null;
    }
    return $splitDatetime['time'];
}

function getYearFromSplitDate($splitDate)
{
    if (!isset($splitDate['year'])) {
        return null;
    }
    return $splitDate['year'];
}

function getMonthFromSplitDate($splitDate)
{
    if (!isset($splitDate['month'])) {
        return null;
    }
    return $splitDate['month'];
}

function getDayFromSplitDate($splitDate)
{
    if (!isset($splitDate['day'])) {
        return null;
    }
    return $splitDate['day'];
}

function getHoursFromSplitTime($splitTime)
{
    if (!isset($splitTime['hours'])) {
        return null;
    }
    return $splitTime['hours'];
}

function getMinsFromSplitTime($splitTime)
{
    if (!isset($splitTime['mins'])) {
        return null;
    }
    return $splitTime['mins'];
}

function getSecsFromSplitTime($splitTime)
{
    if (!isset($splitTime['secs'])) {
        return null;
    }
    return $splitTime['secs'];
}

// Return the date representing the next day of the 'yyyy-mm-dd' date passed (Sql date)
function getNextDay($date)
{

    $splitDate = splitDate($date);

    return date('Y-m-d', mktime(0, 0, 0, $splitDate['month'], $splitDate['day'] + 1, $splitDate['year']));
}

// Return the name of the day of the 'yyyy-mm-dd' date passed (Sql date)
function getDayName($date)
{

    $splitDate = splitDate($date);

    return date("l", mktime(0, 0, 0, $splitDate['month'], $splitDate['day'], $splitDate['year']));
}

// Return the name of the month of the 'yyyy-mm-dd' date passed (Sql date)
function getMonthName($date)
{

    $splitDate = splitDate($date);

    return date("F", mktime(0, 0, 0, $splitDate['month'], $splitDate['day'], $splitDate['year']));
}

// Return an array containing hours, minutes and seconds obtained by the sum of the time strings passed
function sumTime($time1, $time2)
{

    // retrieve hours, minutes and seconds of the time1
    $splitTime1 = splitTime($time1);
    if ($splitTime1 == null) {
        return null;
    }
    // retrieve hours, minutes and seconds of the time2
    $splitTime2 = splitTime($time2);
    if ($splitTime2 == null) {
        return null;
    }

    // now we have two valid time values.. sum them
    $time1TotalSeconds = 0;
    $t = 3600;
    foreach ($splitTime1 as $st) {
        $time1TotalSeconds = $time1TotalSeconds + ($st * $t);
        $t = $t / 60;
    }
    $time2TotalSeconds = 0;
    $t = 3600;
    foreach ($splitTime2 as $st) {
        $time2TotalSeconds = $time2TotalSeconds + ($st * $t);
        $t = $t / 60;
    }

    $sumTimeTotalSeconds = $time1TotalSeconds + $time2TotalSeconds;

    $sumTimeHours = floor($sumTimeTotalSeconds / 3600);
    $sumTimeMins = floor(floor(($sumTimeTotalSeconds % 3600)) / 60);
    $sumTimeSecs = floor(floor(($sumTimeTotalSeconds % 3600)) % 60);

    return ($sumTimeHours . ":" . $sumTimeMins . ":" . $sumTimeSecs);
}

// Convert a duration to time (for example: 1.75 - one hour and 3/4 - ---> 1:45 - one hour and 45 minutes)
function convertDurationToTime($duration)
{

    if (strpos($duration, '.') > 0) {
        $durationArr = explode('.', round($duration, 2));
        return $durationArr[0] . ':' . round(($durationArr[1] * 60) / 100);
    } else {
        return $duration;
    }
}

// Return an associative array with the info about a Y-m-d H:i:s datetime
function parseDatetime($datetime)
{

    $dateAndTime = explode(' ', $datetime);
    $dateInfo = explode('-', $dateAndTime[0]);
    $timeInfo = explode(':', $dateAndTime[1]);

    return array(
        'year' => $dateInfo[0], 'month' => $dateInfo[1], 'day' => $dateInfo[2],
        'hour' => $timeInfo[0], 'minute' => $timeInfo[1], 'second' => $timeInfo[2]
    );
}

function dieWithError($code, $message = null)
{
    $httpMessage = "HTTP/1.1 ";

    switch ($code) {

        case 401:
            $errorString = "401 Unauthorized";
            header($httpMessage . $errorString, true, 401);
            break;

        case 403:
            $errorString = "403 Forbidden";
            header($httpMessage . $errorString, true, 403);
            break;

        case 404:
            $errorString = "404 Page not found";
            header($httpMessage . $errorString, true, 404);
            break;

        case 500:
            $errorString = "500 Internal Server Error";
            header($httpMessage . $errorString, true, 500);
            break;

        default:
            $errorString = "400 Bad Request";
            header($httpMessage . $errorString, true, 400);
            break;
    }

    // Si hay info extra la mostramos por la salida de error
    if ($message != null) {
        RSError("dieWithError: " . $errorString . ". " . $message);
    }

    die($errorString);
}

// Write the error message with Json format
function dieWithErrorJson($code, $errorText)
{
    // Obtain error message as json
    $error = array("errorMessage" => $errorText);
    $jsonError = json_encode($error);

    header('Content-Type: application/json', true, $code);
    header("Content-Length: " . strlen($jsonError));

    die($jsonError);
}

function isBase64($s)
{
    // Check if there are valid base64 characters
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) {
        return false;
    }
    // Decode the string in strict mode
    $decoded = base64_decode($s, true);

    // Encode the string again
    return base64_encode($decoded) === $s;
}

// Set the Authorization token read on the header and puts it in the $GLOBALS variable
function setAuthorizationTokenOnGlobals()
{
    //We need this variable to exists in order to make RSdatabase work propertly.

    if (isset(getallheaders()["authorization"])) {
        $GLOBALS['RS_POST']['RStoken'] = getallheaders()["authorization"];
    }
}

// Returns the property value with the "'" and "&" characters escaped
function replaceUtf8Characters($propertyValue)
{
    $propertyValue = str_replace("&amp;", "&", htmlentities($propertyValue, ENT_COMPAT, "UTF-8"));
    return str_replace("'", "&#39;", $propertyValue);
}

// Returns request body sent through petition, transformed into php object (json)
function getRequestBody()
{
    global $RSallowDebug;
    $body = json_decode(stripslashes(file_get_contents('php://input')));
    if ($body == "") {
        if ($RSallowDebug) {
            returnJsonMessage(400, "Invalid JSON body");
        } else {
            RSError("getRequestBody: Invalid JSON body");
            returnJsonMessage(400, "");
        }
    }
    return $body;
}

function returnJsonMessage($code, $message)
{
    $json = "";
    if ($message != "") {
        $json = '{"message": "' . $message . '"}';
    }
    header('Content-Type: application/json', true, $code);
    header("Content-Length: " . strlen($json));
    echo $json;
    die();
}

// returns api response in json
function returnJsonResponse($response)
{
    header('Content-Type: application/json', true, 200);
    header("Content-Length: " . strlen($response));
    echo $response;
    die();
}

//Gets the clientID from $GLOBALS. Returns an error if it's not found.
function getClientID()
{
    global $RSallowDebug;

    if (isset($GLOBALS['RS_POST']['clientID'])) {
        return $GLOBALS['RS_POST']['clientID'];
    } else {
        if ($RSallowDebug) {
            returnJsonMessage(400, "clientID could not be retrieved");
        } else {
            RSError("getClientID: clientID could not be retrieved");
            returnJsonMessage(400, "");
        }
    }
}

//Gets the RStoken from $GLOBALS. Returns an error if it's not found.
function getRStoken()
{
    global $RSallowDebug;

    if (isset($GLOBALS['RS_POST']['RStoken'])) {
        return $GLOBALS['RS_POST']['RStoken'];
    } else {
        if ($RSallowDebug) {
            returnJsonMessage(400, "RStoken could not be retrieved");
        } else {
            RSError("getRStoken: RStoken could not be retrieved");
            returnJsonMessage(400, "");
        }
    }
}

//Gets the RSuserID from $GLOBALS. Returns an error if it's not found.
function getRSuserID()
{
    global $RSallowDebug;

    if (isset($GLOBALS['RSuserID'])) {
        return $GLOBALS['RSuserID'];
    } else {
        if ($RSallowDebug) {
            returnJsonMessage(400, "RSuserID could not be retrieved");
        } else {
            RSError("getRSuserID: RSuserID could not be retrieved");
            returnJsonMessage(400, "");
        }
    }
}

// Cleans and returns the request params sent (through get)
function getRequestParams()
{

    // Clean GET data in order to avoid SQL injections
    $search = array("'", "\"");
    $replace = array("", "");
    $params = array();
    foreach ($_GET as $key => $value) {
        $params[$key] = str_replace($search, $replace, $value);
    }

    return $params;
}
// The api calls are made directly to the files, so in order to verify that the correct
// request method is used, we need to call this function to verify it.
function checkCorrectRequestMethod($requestMethod)
{
    global $RSallowDebug;

    if ($requestMethod != $_SERVER["REQUEST_METHOD"]) {
        if ($RSallowDebug) {
            returnJsonMessage(400, "Wrong request method");
        } else {
            RSError("checkCorrectRequestMethod: Wrong request method");
            returnJsonMessage(400, "");
        }
    }
}
