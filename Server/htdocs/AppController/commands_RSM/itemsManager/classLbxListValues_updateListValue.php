<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RSMlistsManagement.php";

//First of all, we need to check if the variable clientID does not have the value 0

if ($GLOBALS[$cstRS_POST][$cstClientID] > 0)
	{

		//We check if the value already exists
		$theQuery_valueExists = 'SELECT RS_VALUE_ID FROM rs_property_values WHERE RS_CLIENT_ID='.$GLOBALS[$cstRS_POST][$cstClientID].' AND RS_LIST_ID='.$GLOBALS[$cstRS_POST][$cstListID].' AND RS_VALUE= "'.base64_decode($GLOBALS[$cstRS_POST][$cstValue]).'" AND RS_VALUE_ID <> '.$GLOBALS[$cstRS_POST][$cstValueID];
		$result = RSQuery($theQuery_valueExists);
		if ($result->num_rows == 0)
			{
				// The value exists, so we update it
			$theQuery = "UPDATE rs_property_values SET RS_VALUE = '".base64_decode($GLOBALS[$cstRS_POST][$cstValue])."' WHERE RS_VALUE_ID=".$GLOBALS[$cstRS_POST][$cstValueID]." AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID]." AND RS_LIST_ID=".$GLOBALS[$cstRS_POST][$cstListID];

				//show query if debug mode
				if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug'])
				{
					echo $theQuery;
				}

				if ($result = RSQuery($theQuery))
				{
					$results['result'] = "OK";
				$results['ID'] = $GLOBALS[$cstRS_POST][$cstValueID];
				$results['value'] = base64_decode($GLOBALS[$cstRS_POST][$cstValue]);
					// change old properties value
				$properties = getPropertiesUsingLists($GLOBALS[$cstRS_POST][$cstListID], $GLOBALS[$cstRS_POST][$cstClientID]);

					foreach ($properties as $property) {
						$propertyType = getPropertyType($property, $GLOBALS[$cstRS_POST][$cstClientID]);

						// Ensure property value match the defined property type and convert to default otherwise
						$value = enforcePropertyType(base64_decode($GLOBALS[$cstRS_POST][$cstValue]), $clientID, $property, $propertyType);

						RSQuery("UPDATE ".$propertiesTables[$propertyType]." SET RS_DATA = '".$value."' WHERE RS_PROPERTY_ID = ".$property." AND RS_DATA = '".$GLOBALS[$cstRS_POST][$cstOldValue]."' AND RS_CLIENT_ID = ".$GLOBALS[$cstRS_POST][$cstClientID]);
					}
				}
				else
				{
					$results['result'] = 'NOK1';
				}

			}
		else
			{
				$results['result'] = 'NOK2';
			$results['value'] = base64_decode($GLOBALS[$cstRS_POST][$cstValue]);
			}

	}

else
	{
		$results['result'] = 'NOK3';
	}
// And write XML Response back to the application
RSReturnArrayResults($results);
