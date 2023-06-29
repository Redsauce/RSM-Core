<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS['RS_POST']['clientID'] > 0) {

    //We check if the value already exists
    $theQuery_valueExists = 'SELECT RS_VALUE_ID FROM rs_property_values WHERE RS_CLIENT_ID='.$GLOBALS['RS_POST']['clientID'].' AND RS_LIST_ID='.$GLOBALS['RS_POST']['listID'].' AND RS_VALUE= "'.base64_decode($GLOBALS['RS_POST']['value']).'" AND RS_VALUE_ID <> '.$GLOBALS['RS_POST']['valueID'];
    $result = RSQuery($theQuery_valueExists);
    if ($result->num_rows == 0) {
        // The value exists, so we update it
        $theQuery = "UPDATE rs_property_values SET RS_VALUE = '".base64_decode($GLOBALS['RS_POST']['value'])."' WHERE RS_VALUE_ID=".$GLOBALS['RS_POST']['valueID']." AND RS_CLIENT_ID=".$GLOBALS['RS_POST']['clientID']." AND RS_LIST_ID=".$GLOBALS['RS_POST']['listID'];

        //show query if debug mode
        if (isset($GLOBALS['RS_POST']['RSdebug'])&&$GLOBALS['RS_POST']['RSdebug']) {
            echo $theQuery;
        }

        if ($result = RSQuery($theQuery)) {
            $results['result'] = "OK";
            $results['ID'] = $GLOBALS['RS_POST']['valueID'];
            $results['value'] = base64_decode($GLOBALS['RS_POST']['value']);
            // change old properties value
            $properties = getPropertiesUsingLists($GLOBALS['RS_POST']['listID'], $GLOBALS['RS_POST']['clientID']);

            foreach ($properties as $property) {
                $propertyType = getPropertyType($property, $GLOBALS['RS_POST']['clientID']);

                // Ensure property value match the defined property type and convert to default otherwise
                $value = enforcePropertyType(base64_decode($GLOBALS['RS_POST']['value']), $clientID, $property, $propertyType);

                RSQuery("UPDATE ".$propertiesTables[$propertyType]." SET RS_DATA = '".$value."' WHERE RS_PROPERTY_ID = ".$property." AND RS_DATA = '".$GLOBALS['RS_POST']['oldValue']."' AND RS_CLIENT_ID = ".$GLOBALS['RS_POST']['clientID']);
            }
        } else {
            $results['result'] = 'NOK1';
        }
    } else {
        $results['result'] = 'NOK2';
        $results['value'] = base64_decode($GLOBALS['RS_POST']['value']);
    }
} else {
    $results['result'] = 'NOK3';
}
// And write XML Response back to the application
RSReturnArrayResults($results);
