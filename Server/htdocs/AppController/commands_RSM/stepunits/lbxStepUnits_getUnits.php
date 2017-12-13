<?php
//***************************************************
//Description:
//	Get the units list
//***************************************************

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// Definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$parentStepID = $GLOBALS['RS_POST']['parentStepID'];
$parentStudyID = $GLOBALS['RS_POST']['parentStudyID'];

// get the item type and the properties
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['stepUnits'], $clientID);

$mainPropertyID = getMainPropertyID($itemTypeID, $clientID);
$parentStepPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsStepParentID'], $clientID);
$unitPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsUnit'], $clientID);
$conversionValuePropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsConversionValue'], $clientID);
$parentStudyPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsParentStudy'], $clientID);
$isGlobalPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsIsGlobal'], $clientID);
$valuesListPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepUnitsValuesList'], $clientID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $mainPropertyID, 'name' => 'mainValue');
$returnProperties[] = array('ID' => $parentStepPropertyID, 'name' => 'parentStepID');
$returnProperties[] = array('ID' => $unitPropertyID, 'name' => 'unit');
$returnProperties[] = array('ID' => $conversionValuePropertyID, 'name' => 'conversionValue');
$returnProperties[] = array('ID' => $parentStudyPropertyID, 'name' => 'studyID');
$returnProperties[] = array('ID' => $isGlobalPropertyID, 'name' => 'isGlobal');
$returnProperties[] = array('ID' => $valuesListPropertyID, 'name' => 'valuesList');

//build the filter for all the units that are global to the study
$filters = array();
$filters[] = array('ID' => $parentStudyPropertyID, 'value' => $parentStudyID);
$filters[] = array('ID' => $isGlobalPropertyID, 'value' => '1');

$stepUnits = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);

//Seguidamente, cogemos las unidades própias del step
$filters = array();
$filters[] = array('ID' => $parentStudyPropertyID, 'value' => $parentStudyID);
$filters[] = array('ID' => $parentStepPropertyID, 'value' => $parentStepID);
$filters[] = array('ID' => $isGlobalPropertyID, 'value' => '0');

$noGlobalStepUnits = getFilteredItemsIDs($itemTypeID, $clientID, $filters, $returnProperties);

//Juntamos los dos arrays
$stepUnits = array_merge($stepUnits, $noGlobalStepUnits);

//Hemos de marcar los stepUnits que estan chequeados y los que no
//Primero cogemos los steps units para el step pasado

$stepItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['steps'], $clientID);
$stepCheckedStepUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['stepsCheckedStepUnits'], $clientID);

//Get the value of checked units
$checkedValues = getItemPropertyValue($parentStepID, $stepCheckedStepUnitsPropertyID, $clientID);

//Next, save the values to an array and return it
$markedStepsUnitsIDs = explode(",", $checkedValues);

//Para los items marcados, añadimos la propiedad de checked
for ($i = 0; $i < count($stepUnits); $i++)
    if (in_array($stepUnits[$i]['ID'], $markedStepsUnitsIDs)) {
        $stepUnits[$i]['isChecked'] = 'True';
    } else {
        $stepUnits[$i]['isChecked'] = 'False';
    }

//Seguidamente, hemos de coger el order que tiene cada una de las unidades dentro del estudio
$orderUnitsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['orderUnits'], $clientID);
$orderUnitsStepPropID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsStepID'], $clientID);
$orderUnitsUnitPropID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsUnitID'], $clientID);
$orderUnitsOrderPropID = getClientPropertyID_RelatedWith_byName($definitions['orderUnitsOrder'], $clientID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => $orderUnitsOrderPropID, 'name' => 'order');
$returnProperties[] = array('ID' => $orderUnitsUnitPropID, 'name' => 'unitID');

//build the filter
$filters = array();
$filters[] = array('ID' => $orderUnitsStepPropID, 'value' => $parentStepID);

$OrderUnits = getFilteredItemsIDs($orderUnitsItemTypeID, $clientID, $filters, $returnProperties);

//Check if the unit exist and mark their order
for ($i = 0; $i < count($stepUnits); $i++) {

    $order = '0';

    for ($j = 0; $j < count($OrderUnits); $j++)
        if ($OrderUnits[$j]['unitID'] == $stepUnits[$i]['ID']) {
            $order = $OrderUnits[$j]['order'];
            break;
        }

    $stepUnits[$i]['order'] = $order;
}

// reorder array
usort($stepUnits, make_comparer(array('order', SORT_DESC)));

// return results
RSReturnArrayQueryResults($stepUnits);
?>
