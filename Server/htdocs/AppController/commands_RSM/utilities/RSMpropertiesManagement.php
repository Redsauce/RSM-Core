<?php
$propertiesTables = array(
	'text'       				  => 'rs_property_text',
	'longtext'			   	      => 'rs_property_longtext',
	'date'   		    		  => 'rs_property_dates',
	'integer'    				  => 'rs_property_integers',
	'float'      				  => 'rs_property_floats',
	'datetime'   				  => 'rs_property_datetime',
	'identifier' 				  => 'rs_property_identifiers',
	'identifier2itemtype'         => 'rs_property_identifiers_to_itemtypes',
	'identifier2property'         => 'rs_property_identifiers_to_properties',
	'identifiers'				  => 'rs_property_multiIdentifiers',
	'image'		 				  => 'rs_property_images',
	'password'   				  => 'rs_property_passwords',
	'variant'    				  => 'rs_property_variant',
	'color'    					  => 'rs_property_colors',
	'file'    					  => 'rs_property_files'
);

$propertiesDefaultValues = array(
    'text'                        => '',
    'longtext'                    => '',
    'date'                        => '0000-00-00',
    'integer'                     => '0',
    'float'                       => '0',
    'datetime'                    => '0000-00-00 00:00:00',
    'identifier'                  => '0',
    'identifier2itemtype'         => '0',
    'identifier2property'         => '0',
    'identifiers'                 => '0',
    'image'                       => '',
    'password'                    => '',
    'variant'                     => '',
    'color'                       => '',
    'file'                        => ''
);

$propertiesValidationExpressions = array(
    'text'                        => '/.*/',
    'longtext'                    => '/.*/',
    'date'                        => '/\d{4}-\d{2}-\d{2}/',
    'integer'                     => '/\d+/',
    'float'                       => '/\d+(\.\d+)?/',
    'datetime'                    => '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
    'identifier'                  => '/\d+/',
    'identifier2itemtype'         => '/\d+/',
    'identifier2property'         => '/\d+/',
    'identifiers'                 => '/\d+(,\d+)*/',
    'image'                       => '/.*/',
    'password'                    => '/.*/',
    'variant'                     => '/.*/',
    'color'                       => '/.*/',
    'file'                        => '/.*/'
);

$auditTrailPropertiesTables = array(
	'text'     				    => 'rs_audit_trail_property_text',
	'longtext' 				    => 'rs_audit_trail_property_longtext',
	'date'     				    => 'rs_audit_trail_property_dates',
	'integer'  				    => 'rs_audit_trail_property_integers',
	'float'    				    => 'rs_audit_trail_property_floats',
	'datetime' 				    => 'rs_audit_trail_property_datetime',
	'identifier' 			    => 'rs_audit_trail_property_identifiers',
	'identifier2itemtype'       => 'rs_audit_trail_property_identifiers_to_itemtypes',
	'identifier2property'       => 'rs_audit_trail_property_identifiers_to_properties',
	'identifiers'			    => 'rs_audit_trail_property_multiIdentifiers',
	'image'					    => 'rs_audit_trail_property_images',
	'password'  			    => 'rs_audit_trail_property_passwords',
	'variant'   			    => 'rs_audit_trail_property_variant',
	'color'   				    => 'rs_audit_trail_property_colors',
	'file'   				    => 'rs_audit_trail_property_files'
);


function enforcePropertyType($value, $clientID, $propertyID, $propertyType) {
    global $propertiesTables, $propertiesDefaultValues, $propertiesValidationExpressions, $RSuserID, $RStoken;

    if ($propertyType == "identifiers") {

        // Clean leading, trailing and duplicated commas
        $value = trim($value, ",");
        $value = preg_replace("/,+/", ",", $value);

    }

    //check value match the right format (if match partially, just take the first valid part of the value)
    if (preg_match($propertiesValidationExpressions[$propertyType], $value, $extracted)) {

        // match found, we take the first valid match
        $value = $extracted[0];

    } else {

        // no match found (wrong format), set to the default value for the property
        $value = getClientPropertyDefaultValue($propertyID, $clientID);

        //check default value match the right format (if match partially, just take the first valid part of the default value)
        if (preg_match($propertiesValidationExpressions[$propertyType], $value, $extracted)) {

            // match found, we take the first valid match
            $value = $extracted[0];

        } else {

            // no match found in default value (wrong format), set to the system default value for the property type
            $value = $propertiesDefaultValues[$propertyType];

        }

    }


    return $value;
}
?>
