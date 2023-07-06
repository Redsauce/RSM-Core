<?php
// Database connection startup
require_once "RSMitemsManagement.php";
require_once "RSMlistsManagement.php";
require_once "RSMglobalVariables.php";
require_once "RSMtokensManagement.php";

function checkTriggeredEvents($clientID)
{
  global $RSMcreatedItemIDs;
  global $RSMupdatedItemIDs;
  global $RSMdeletedItemIDs;
  global $RSMsplitTriggers;

  $eventsHandlerToken = getGlobalVariableValue("eventsHandler.token", $clientID);

  // This token must be defined in the global variables in order for the triggers to be enabled
  if ($eventsHandlerToken == "") {
    $RSMsplitTriggers = false;
    return;
  }

  // Check if there are events associated to the items creation
  if (count($RSMcreatedItemIDs) > 0) {
    $createdItemTypeIDs = array();

    foreach ($RSMcreatedItemIDs as $group) {
      $elements = explode(",", $group);
      if (!in_array($elements[0], $createdItemTypeIDs)) {
        $createdItemTypeIDs[] = $elements[0];
      }
    }

    $triggerIDs = getTriggerIDs($createdItemTypeIDs, $clientID, "triggerTypeCreateItem");


    if (count($triggerIDs) > 0) {

      // When we have RSMsplitTriggers enabled, we queue an action for each item
      if ($RSMsplitTriggers) {
        foreach ($RSMcreatedItemIDs as $oneCreatedItemID) {
          $affectedItemID = array();
          array_push($affectedItemID, $oneCreatedItemID);
          queueActions($affectedItemID, $triggerIDs, "itemsCreated", $eventsHandlerToken);
        }
      } else {
        queueActions($RSMcreatedItemIDs, $triggerIDs, "itemsCreated", $eventsHandlerToken);
      }
    }
  }

  // Check if there are events associated to the items update
  if (count($RSMupdatedItemIDs) > 0) {
    $updatedItemTypeIDs = array();

    foreach ($RSMupdatedItemIDs as $group) {
      $elements = explode(",", $group);
      if (!in_array($elements[0], $updatedItemTypeIDs)) {
        $updatedItemTypeIDs[] = $elements[0];
      }
    }

    $triggerIDs = getTriggerIDs($updatedItemTypeIDs, $clientID, "triggerTypeUpdateItem");

    if (count($triggerIDs) > 0) {

      // When we have RSMsplitTriggers enabled, we queue an action for each item
      if ($RSMsplitTriggers) {
        foreach ($RSMupdatedItemIDs as $oneUpdatedItemID) {
          $affectedItemID = array();
          array_push($affectedItemID, $oneUpdatedItemID);
          queueActions($affectedItemID, $triggerIDs, "itemsUpdated", $eventsHandlerToken);
        }
      } else {
        queueActions($RSMupdatedItemIDs, $triggerIDs, "itemsUpdated", $eventsHandlerToken);
      }
    }
  }

  // Check if there are events associated to the items deletion
  if (count($RSMdeletedItemIDs) > 0) {
    $deletedItemTypeIDs = array();

    foreach ($RSMdeletedItemIDs as $group) {
      $elements = explode(",", $group);
      if (!in_array($elements[0], $deletedItemTypeIDs)) {
        $deletedItemTypeIDs[] = $elements[0];
      }
    }

    $triggerIDs = getTriggerIDs($deletedItemTypeIDs, $clientID, "triggerTypeDeleteItem");

    if (count($triggerIDs) > 0) {
      queueActions($RSMdeletedItemIDs, $triggerIDs, "itemsDeleted", $eventsHandlerToken);
    }
  }

  $RSMsplitTriggers = false;
}

function getTriggerIDs($itIDs, $clientID, $mode)
{

  // This function returns an array of triggers to trigger for the array of created / updated / deleted itemTypeIDs
  $triggerIDs = array();

  // Recover the IDs and values needed to create the filter
  $triggerTypePV = getValue(getClientListValueIDRelatedWith(getAppListValueID($mode), $clientID), $clientID);
  $triggerITID           = getClientItemTypeIDRelatedWithByName('eventTrigger', $clientID);
  $triggerTypePID        = getClientPropertyIDRelatedWithByName('eventTrigger.type', $clientID);
  $triggerItemTypesPID   = getClientPropertyIDRelatedWithByName('eventTrigger.data', $clientID);

  // If someone of the earlies variables is wrong, we notify as a trigger error
  if ($triggerTypePV == '' || $triggerITID == 0 || $triggerTypePID == 0 || $triggerItemTypesPID == 0) {
    RSerror("Error returning the related trigger IDs with this parameters ItemTypeIDs: " . print_r($itIDs, true) .
      "clientID: " . $clientID . chr(13) .
      "mode: " . $mode . chr(13) . chr(13) .
      "RESULTS" . chr(13) .
      "triggerTypePV: " . $triggerTypePV . chr(13) .
      "triggerITID: " . $triggerITID . chr(13) .
      "triggerTypePID: " . $triggerTypePID . chr(13) .
      "triggerItemTypesPID: " . $triggerItemTypesPID, "Trigger");
  } else {
    // Build filter properties array
    $filterProperties   = array();
    $filterProperties[] = array('ID' => $triggerTypePID,      'value' => $triggerTypePV, 'mode' => "=");
    $filterProperties[] = array('ID' => $triggerItemTypesPID, 'value' => implode(",", $itIDs), 'mode' => "IN");

    // Build return properties array
    $returnProperties = array();

    // Filter triggers
    $results = getFilteredItemsIDs($triggerITID, $clientID, $filterProperties, $returnProperties);


    foreach ($results as $result) {
      $triggerIDs[] = $result["ID"];
    }
  }

  return $triggerIDs;
}

function getActionIDsByItemTypeIDs($itIDs, $clientID, $mode)
{
  $triggerIDs = getTriggerIDs($itIDs, $clientID, $mode);
  return getActionIDsFromTriggerIDs($triggerIDs, $clientID);
}

// This function returns an array with the IDs of the actions, that should be executed by this URL trigger
function getActionsByURLTriggerName($trigger, $clientID)
{

  // Recover typeID and propertiesID from triggers
  $propertyURL                  = getValue(getClientListValueIDRelatedWith(getAppListValueID('triggerTypeUrl'), $clientID), $clientID);
  $clientTriggerTypeID          = getClientItemTypeIDRelatedWithByName('eventTrigger', $clientID);
  $clientTriggerTypePropertyID  = getClientPropertyIDRelatedWithByName('eventTrigger.type', $clientID);
  $clientTriggerDataPropertyID  = getClientPropertyIDRelatedWithByName('eventTrigger.data', $clientID);

  // Build filter properties array
  $filterProperties   = array();
  $filterProperties[] = array('ID' => $clientTriggerTypePropertyID, 'value' => $propertyURL);
  $filterProperties[] = array('ID' => $clientTriggerDataPropertyID, 'value' => $trigger);

  // Build return properties array
  $returnProperties = array();

  // Filter triggers
  $results = getFilteredItemsIDs($clientTriggerTypeID, $clientID, $filterProperties, $returnProperties);

  $triggerIDs = array();

  foreach ($results as $result) {
    $triggerIDs[] = $result["ID"];
  }

  return getActionsFromTriggerIDs($triggerIDs, $clientID);
}

function getActionIDsFromTriggerIDs($triggerIDs, $clientID)
{
  $clientEventTriggerPropertyID = getClientPropertyIDRelatedWithByName('eventTrigger.eventID', $clientID);
  $actionIDs = array();

  foreach ($triggerIDs as $triggerID) {
    $actions = explode(",", getItemPropertyValue($triggerID, $clientEventTriggerPropertyID, $clientID));

    foreach ($actions as $action) {
      if (!in_array($action, $actionIDs)) {
        $actionIDs[] = $action;
      }
    }
  }

  return $actionIDs;
}

function getActionsFromTriggerIDs($triggerIDs, $clientID)
{
  $clientEventTriggerPropertyID               = getClientPropertyIDRelatedWithByName('eventTrigger.eventID', $clientID);
  $clientEventTriggerPropertyPriority         = getClientPropertyIDRelatedWithByName('eventTrigger.priority', $clientID);
  $clientEventTriggerPropertyAvoidDuplication = getClientPropertyIDRelatedWithByName('eventTrigger.avoidDuplication', $clientID);
  $actionIDs             = array();
  $actionIDsWithPriority = array();

  foreach ($triggerIDs as $triggerID) {
    $actions          = explode(",", getItemPropertyValue($triggerID, $clientEventTriggerPropertyID, $clientID));
    $priority         = $clientEventTriggerPropertyPriority == 0 ? 0 : getItemPropertyValue($triggerID, $clientEventTriggerPropertyPriority, $clientID);
    $avoidDuplication = $clientEventTriggerPropertyAvoidDuplication == 0 ? "No" : getItemPropertyValue($triggerID, $clientEventTriggerPropertyAvoidDuplication, $clientID);

    foreach ($actions as $action) {
      $pos = array_search($action, $actionIDs);
      if ($pos === false) {
        $actionIDs[] = $action;
        $actionIDsWithPriority[] = array("ID" => $action, "priority" => $priority, "avoidDuplication" => $avoidDuplication);
      } else {
        if ($actionIDsWithPriority[$pos]["priority"] > $priority) {
          $actionIDsWithPriority[$pos]["priority"] = $priority;
        }
        if ($actionIDsWithPriority[$pos]["avoidDuplication"] != 'No' && $avoidDuplication == 'No') {
          $actionIDsWithPriority[$pos]["avoidDuplication"] = $avoidDuplication;
        }
      }
    }
  }

  return $actionIDsWithPriority;
}

function getActionScript($actionID, $clientID)
{
  // This function returns an array with the action scripts corresponding with the passed actionID
  // Retrieve the script for each action
  $propertyScriptID = getClientPropertyIDRelatedWithByName('event.actions', $clientID);

  // Filter includes
  return getItemPropertyValue($actionID, $propertyScriptID, $clientID);
}

function getActionToken($actionID, $clientID)
{
  // This function returns an array with the token corresponding with the passed actionID
  // Retrieve the script for each action
  $propertyTokenID = getClientPropertyIDRelatedWithByName('event.token', $clientID);

  // Filter includes
  return getItemPropertyValue($actionID, $propertyTokenID, $clientID);
}

function getActionName($actionID, $clientID)
{
  // This function returns a string with the action name corresponding with the passed actionID
  // Retrieve the script for each action
  $propertyNameID = getClientPropertyIDRelatedWithByName('event.name', $clientID);

  // Filter includes
  return getItemPropertyValue($actionID, $propertyNameID, $clientID);
}

function getIncludesScript($actionID, $clientID)
{
  // This function returns an array with the included scripts corresponding with the passed actionID

  // Retrieve the script for each action
  $includeTypeID    = getClientItemTypeIDRelatedWithByName('eventInclude', $clientID);
  $propertyScriptID = getClientPropertyIDRelatedWithByName('eventInclude.actions', $clientID);
  $propertyEventID  = getClientPropertyIDRelatedWithByName('eventInclude.eventIDs', $clientID);

  // Build filter properties array
  $filterProperties = array();
  $filterProperties[] = array('ID' => $propertyEventID, 'value' => $actionID, 'mode' => 'IN');

  // Build return properties array
  $returnProperties[] = array('ID' => $propertyScriptID, 'name' => 'action');

  // Filter includes
  return getFilteredItemsIDs($includeTypeID, $clientID, $filterProperties, $returnProperties);
}

function queueActions($RSdata, $triggerIDs, $RStoken)
{
  $clientID  = RSclientFromToken($RStoken);
  $actions = getActionsFromTriggerIDs($triggerIDs, $clientID);

  foreach ($actions as $action) {
    $result = queueEvent($clientID, $action["ID"], implode(";", $RSdata), $action["priority"], $action["avoidDuplication"]);

    // TODO: Send an email if there were a problem
    if (!$result) {
      mail('webmaster@redsauce.net', 'Error scheduling job', wordwrap("The events for triggers " . $triggerIDs . " could not be queued.", 70, "\r\n"));
    }
  }
}

function queueAction($RSdata, $actionID, $clientID, $priority = 0, $avoidDuplication = 'No', $staffID = 0)
{
  $result = queueEvent($clientID, $actionID, $RSdata, $priority, $avoidDuplication, $staffID);

  //error_log("RSMeventsManagement/queueAction - staffID: ". $staffID);
  // TODO: Send an email if there were a problem
  if (!$result) {
    mail('webmaster@redsauce.net', 'Error scheduling job', wordwrap("The action ID " . $actionID . " could not be queued.", 70, "\r\n"));
  }
}

function queueEvent($clientID, $actionID, $data, $priority = 0, $avoidDuplication = 'No', $staffID = 0)
{
  // Register the event in the rs_events table

  $eventPID        = getClientPropertyIDRelatedWithByName("scheduledEvents.event", $clientID);
  $creationDatePID = getClientPropertyIDRelatedWithByName("scheduledEvents.creationDate", $clientID);
  $executionEndPID = getClientPropertyIDRelatedWithByName("scheduledEvents.executionEnd", $clientID);
  $parametersPID   = getClientPropertyIDRelatedWithByName("scheduledEvents.parameters", $clientID);
  $priorityPID     = getClientPropertyIDRelatedWithByName("scheduledEvents.priority", $clientID);
  $userPID         = getClientPropertyIDRelatedWithByName("scheduledEvents.userLogin", $clientID);

  if (($eventPID        == 0) ||
    ($creationDatePID == 0) ||
    ($executionEndPID == 0) ||
    ($parametersPID   == 0) ||
    ($priorityPID     == 0)
  ) {
    // One of the properties is not related
    return false;
  }

  $pValues   = array();
  $pValues[] = array('ID' => $eventPID, 'value' => $actionID);
  $pValues[] = array('ID' => $creationDatePID, 'value' => date_create()->format('Y-m-d H:i:s'));
  $pValues[] = array('ID' => $parametersPID, 'value' => $data);
  $pValues[] = array('ID' => $priorityPID, 'value' => $priority);

  if ($staffID != 0) {
    if ($userPID == 0) {
      // One of the properties is not related
      return false;
    }

    $pValues[] = array('ID' => $userPID, 'value' => $staffID);
  }

  //check if pending event can be duplicated
  $results = array();
  if ($avoidDuplication != 'No') {
    // Construct filterProperties array
    $filterProperties  = array(
      array('ID' => $eventPID, 'value' => $actionID, 'mode' => "="),
      array('ID' => $parametersPID, 'value' => $data, 'mode' => "="),
      array('ID' => $executionEndPID, 'value' => '00-00-00 00:00:00', 'mode' => "=")
    );
    // Construct returnProperties array
    $returnProperties = array();
    // Filter results
    $results = getFilteredItemsIDs(parseITID("scheduledEvents", $clientID), $clientID, $filterProperties, $returnProperties);
  }

  if ($avoidDuplication == 'No' || count($results) == 0) {
    //create pending event only if not equal pending event (not executed) exists or duplication is allowed
    $itemID = createItem($clientID, $pValues);
    if ($itemID == 0) {
      return false;
    }
  }

  return true;
}
