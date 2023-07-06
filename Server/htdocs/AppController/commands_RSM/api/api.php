<?php
// *******************************************************
//Description:
//      Runs one or more triggers depending of the passed parameters
//
//  PARAMETERS:
//      data    : variable structure depending on the trigger that must be executed
//      trigger : the trigger to execute
// *******************************************************
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RStools.php";
require_once "../utilities/RSMeventsManagement.php";

isset($GLOBALS['RS_POST']['RSdata'   ]) ? $RSdata    = $GLOBALS['RS_POST']['RSdata'   ] : $RSdata = "";
isset($GLOBALS['RS_POST']['RStrigger']) ? $RStrigger = $GLOBALS['RS_POST']['RStrigger'] : dieWithError(400);
isset($GLOBALS['RS_POST']['RStoken'  ]) ? $RStoken   = $GLOBALS['RS_POST']['RStoken'  ] : dieWithError(400);

// Check for an empty trigger
if ($RStrigger == "") {
      dieWithError(400);
}

// Check for an empty token
if ($RStoken == "") {
      dieWithError(400);
}

// Pon en la cola los ScheduledEvents asociados al trigger
// Primero hemos de saber a que cliente pertenece el token proporcionado
$clientID = RSclientFromToken($RStoken);

// Obtenemos una lista de triggerIDs relacionados con el nombre del trigger
$actions = getActionsByURLTriggerName($RStrigger, $clientID);

foreach ($actions as $action) {
      $result = queueEvent($clientID, $action["ID"], $RSdata, $action["priority"], $action["avoidDuplication"]);
}
