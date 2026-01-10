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

isset($GLOBALS[$cstRS_POST]['RSdata'   ]) ? $RSdata    = $GLOBALS[$cstRS_POST]['RSdata'   ] : $RSdata = "";
isset($GLOBALS[$cstRS_POST]['RStrigger']) ? $RStrigger = $GLOBALS[$cstRS_POST]['RStrigger'] : dieWithError(400);
isset($GLOBALS[$cstRS_POST][$cstRStoken]) ? $RStoken   = $GLOBALS[$cstRS_POST][$cstRStoken] : dieWithError(400);

// Check for an empty trigger
if ($RStrigger == "") dieWithError(400);

// Check for an empty token
if ($RStoken == "") dieWithError(400);

// Pon en la cola los ScheduledEvents asociados al trigger
// Primero hemos de saber a que cliente pertenece el token proporcionado
$clientID = RSclientFromToken($RStoken);

// Obtenemos una lista de triggerIDs relacionados con el nombre del trigger
$actions = getActionsByURLTriggerName($RStrigger, $clientID);

foreach ($actions as $action)
      $result = queueEvent($clientID, $action["ID"], $RSdata, $action["priority"], $action["avoidDuplication"]);

