<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";
require_once "../utilities/RStools.php";

// definitions
isset($GLOBALS['RS_POST']['clientID'  ]) ? $clientID   =               $GLOBALS['RS_POST']['clientID'  ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['tasks'     ]) ? $tasks      =               $GLOBALS['RS_POST']['tasks'     ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['newName'   ]) ? $newName    = base64_decode($GLOBALS['RS_POST']['newName'   ]) : dieWithError(400);
isset($GLOBALS['RS_POST']['parentID'  ]) ? $parentID   =               $GLOBALS['RS_POST']['parentID'  ]  : dieWithError(400);
isset($GLOBALS['RS_POST']['parentType']) ? $parentType =               $GLOBALS['RS_POST']['parentType']  : dieWithError(400);

if ($tasks == '') {
  // ERROR: no tasks selected
  RSReturnArrayResults(array('result' => 'no tasks selected'));
  exit;
}

// get item type
$tasksItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['tasks'], $clientID);

// get properties
$namePropertyID        = getClientPropertyID_RelatedWith_byName($definitions['taskName'       ], $clientID);
$descriptionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskDescription'], $clientID);
$projectPropertyID     = getClientPropertyID_RelatedWith_byName($definitions['taskProjectID'  ], $clientID);
$parentPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['taskParentID'   ], $clientID);
$startDatePropertyID   = getClientPropertyID_RelatedWith_byName($definitions['taskStartDate'  ], $clientID);
$endDatePropertyID     = getClientPropertyID_RelatedWith_byName($definitions['taskEndDate'    ], $clientID);
$staffPropertyID       = getClientPropertyID_RelatedWith_byName($definitions['taskStaff'      ], $clientID);
$currentTimePropertyID = getClientPropertyID_RelatedWith_byName($definitions['taskCurrentTime'], $clientID);
$totalTimePropertyID   = getClientPropertyID_RelatedWith_byName($definitions['taskTotalTime'  ], $clientID);
$statusPropertyID      = getClientPropertyID_RelatedWith_byName($definitions['taskStatus'     ], $clientID);
$parentPropertyType    = getPropertyType($parentPropertyID, $clientID);

// get tasks properties
$tasksArray = getFilteredItemsIDs(
    $tasksItemTypeID,
    $clientID,
    array(),
    array(
    array('ID' => $projectPropertyID    , 'name' => 'projectID'  ),
    array('ID' => $descriptionPropertyID, 'name' => 'description'),
    array('ID' => $startDatePropertyID  , 'name' => 'startDate'  ),
    array('ID' => $endDatePropertyID    , 'name' => 'endDate'    ),
    array('ID' => $staffPropertyID      , 'name' => 'staff'      ),
    array('ID' => $currentTimePropertyID, 'name' => 'currentTime'),
    array('ID' => $totalTimePropertyID  , 'name' => 'totalTime'  )
  ), '', false, '', $tasks
);


if (empty($tasksArray)) {
  // ERROR: no tasks found
  RSReturnArrayResults(array('result' => 'no tasks found'));
  exit;
}

// save tasks list
$tasksList = $tasksArray[0]['ID'];
for ($i = 1; $i < count($tasksArray); $i++) {
  $tasksList .= ','.$tasksArray[$i]['ID'];
}

// adjust values for new task
$description = $tasksArray[0]['description'];
$startDate   = $tasksArray[0]['startDate'  ];
$endDate     = $tasksArray[0]['endDate'    ];
$staff       = $tasksArray[0]['staff'      ];
$currentTime = $tasksArray[0]['currentTime'];
$totalTime   = $tasksArray[0]['totalTime'  ];

for ($i = 1; $i < count($tasksArray); $i++) {
  // update description
  $description .= '; '.$tasksArray[$i]['description'];
  
  // update start date
  if (isBefore($tasksArray[$i]['startDate'], $startDate)) {
    $startDate = $tasksArray[$i]['startDate'];
  }
  
  // update end date
  if (isAfter($tasksArray[$i]['endDate'], $endDate)) {
    $endDate = $tasksArray[$i]['endDate'];
  }
  
  // update staff
  if ($tasksArray[$i]['staff'] != '') {
        $staff .= ','.$tasksArray[$i]['staff'];
    }
    
  // update current time
  $currentTime += $tasksArray[$i]['currentTime'];
  
  // update total time
  $totalTime += $tasksArray[$i]['totalTime'];
}

// format values correctly
$description = trim($description, '; ');
$staff = implode(',', array_merge(array_unique(explode(',', trim($staff, ',')))));


// get worksessions
$worksessionsItemTypeID       = getClientItemTypeID_RelatedWith_byName($definitions['worksessions'], $clientID);
$worksessionsTaskPropertyID   = getClientPropertyID_RelatedWith_byName($definitions['worksessionTask'], $clientID);
$worksessionsTaskPropertyType = getPropertyType($worksessionsTaskPropertyID, $clientID);

$worksArray = getFilteredItemsIDs(
  $worksessionsItemTypeID,
   $clientID,
   array(
   array('ID' => $worksessionsTaskPropertyID, 'value' => $tasksList, 'mode' => '<-IN')
   ),
  array()
);

$status = getValue(getClientListValueID_RelatedWith(getAppListValueID("taskStatusOpen"), $clientID), $clientID);

// create new task after joining the others
$values = array();
$values[] = array('ID' => $namePropertyID       , 'value' => $newName    );
$values[] = array('ID' => $descriptionPropertyID, 'value' => $description);
$values[] = array('ID' => $startDatePropertyID  , 'value' => $startDate  );
$values[] = array('ID' => $endDatePropertyID    , 'value' => $endDate    );
$values[] = array('ID' => $staffPropertyID      , 'value' => $staff      );
$values[] = array('ID' => $currentTimePropertyID, 'value' => $currentTime);
$values[] = array('ID' => $totalTimePropertyID  , 'value' => $totalTime  );
$values[] = array('ID' => $statusPropertyID     , 'value' => $status     );

if ($parentType == 'project') {
    $values[] = array('ID' => $projectPropertyID    , 'value' => $parentID);
} else {
    $values[] = array('ID' => $parentPropertyID     , 'value' => $parentID);
}

// create the task
$newTaskID = createItem($clientID, $values);

// change worksessions parent task (now they pertain to the new task)
foreach ($worksArray as $ws) {
  setPropertyValueByID($worksessionsTaskPropertyID, $worksessionsItemTypeID, $ws['ID'], $clientID, $newTaskID, $worksessionsTaskPropertyType, $RSuserID);
}

// delete old tasks
deleteItems($tasksItemTypeID, $clientID, $tasksList);

$results['result'] = 'OK';
$results['taskID'] = $newTaskID;

// And write XML Response back to the application
RSReturnArrayResults($results);
