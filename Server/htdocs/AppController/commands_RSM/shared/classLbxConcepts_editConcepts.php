<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";

// definitions
$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];

// get the operations item type
$operationsItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get the operation description
$operationDescription = getPropertyValue($definitions['operationDescription'], $operationsItemTypeID, $operationID, $clientID);


// get the concepts item type
$itemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);

// --- DELETE OLD CONCEPTS ---
// build filter properties array
$filterProperties = array();
$filterProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID), 'value' => $operationID);

// build return properties array
$returnProperties = array();
$returnProperties[] = array('ID' => getClientPropertyID_RelatedWith_byName($definitions['conceptName'], $clientID), 'name' => 'name');

// get operation concepts
$operationConceptsQuery = IQ_getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, 'name');

$operationConcepts = array();
$description = '';
while ($row = $operationConceptsQuery->fetch_assoc()) {
    $operationConcepts[] = $row['ID'];
    $description .= $row['name'] . '; ';
}

// remove last separator
$description = substr($description, 0, -2);

// delete the concepts
if (!empty($operationConcepts)) {
    if (count($operationConcepts) > 1) {
        deleteItems($itemTypeID, $clientID, implode(',', $operationConcepts));
    } else {
        deleteItem($itemTypeID, $operationConcepts[0], $clientID);
    }
}


// --- INSERT NEW CONCEPTS ---
$conceptNames = array();

for ($i = 0; isset($GLOBALS['RS_POST']['concept' . $i]); $i++) {

    // initialize properties values array for the new concept
    $propertiesValues = array();

    // the concept will pertains to the operation passed
    $propertiesValues[] = array(
        'ID'    => getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID),
        'value' => $operationID
    );

    // get concept passed properties
    $properties = explode(' ', $GLOBALS['RS_POST']['concept' . $i]);

    foreach ($properties as $property) {
        // get property name and value
        $propertyArr = explode(',', $property);

        // save value
        $value = base64_decode($propertyArr[1]);

        if ($propertyArr[0] == 'Name') {
            // prepare the concept names array for the operation description (it will be updated only if required)
            $conceptNames[] = $value;
        }

        // update properties values array
        $propertiesValues[] = array(
            'ID'    => getClientPropertyID_RelatedWith_byName($definitions['concept' . $propertyArr[0]], $clientID),
            'value' => $value
        );
    }

    // create new concept
    createItem($clientID, $propertiesValues);
}

// sort concept names
sort($conceptNames, SORT_STRING);

if ($operationDescription == $description) {
    // the old operation's description was formed by the concepts descriptions, so we have to update it
    $newOperationDescription = implode('; ', $conceptNames);

    // update operation description
    setItemPropertyValue($definitions['operationDescription'], $operationsItemTypeID, $operationID, $clientID, $newOperationDescription, $RSuserID);

    // return the description
    $results['description'] = $newOperationDescription;
}


$results['result'] = 'OK';

// Return results
RSreturnArrayResults($results);
