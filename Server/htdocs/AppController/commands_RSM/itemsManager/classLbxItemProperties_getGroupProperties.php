<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";


if ($GLOBALS[$cstRS_POST][$cstClientID] != 0 && $GLOBALS[$cstRS_POST][$cstGroupID] != 0 && $GLOBALS[$cstRS_POST][$cstItemTypeID] != 0)
	{
		//We check if the group exists into the client

	$theQuery_groupValidation = "SELECT RS_GROUP_ID FROM rs_groups WHERE RS_GROUP_ID ='".$GLOBALS[$cstRS_POST][$cstGroupID]."' AND RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID];
		$resultgroupOK = RSQuery($theQuery_groupValidation);

		//show query if debug mode
		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug'])
		{
			echo $theQuery_groupValidation;
		}

		if ( $resultgroupOK->num_rows != 0 )
			{
				//The group exists, so perform the action
				$theQuery = "SELECT DISTINCT rs_properties_groups.RS_PROPERTY_ID as 'propertyID' FROM rs_categories INNER JOIN (rs_item_properties INNER JOIN (rs_groups INNER JOIN rs_properties_groups ON rs_groups.RS_GROUP_ID=rs_properties_groups.RS_GROUP_ID AND rs_groups.RS_CLIENT_ID=rs_properties_groups.RS_CLIENT_ID) ON rs_item_properties.RS_PROPERTY_ID=rs_properties_groups.RS_PROPERTY_ID AND rs_item_properties.RS_CLIENT_ID=rs_properties_groups.RS_CLIENT_ID) ON rs_categories.RS_CATEGORY_ID=rs_item_properties.RS_CATEGORY_ID AND rs_categories.RS_CLIENT_ID=rs_item_properties.RS_CLIENT_ID WHERE rs_properties_groups.RS_GROUP_ID = ".$GLOBALS[$cstRS_POST][$cstGroupID]." AND rs_groups.RS_CLIENT_ID=".$GLOBALS[$cstRS_POST][$cstClientID]." AND rs_categories.RS_ITEMTYPE_ID=".$GLOBALS[$cstRS_POST][$cstItemTypeID];

				//show query if debug mode
				if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug'])
				{
					echo $theQuery;
				}
				// Query the database
				$results = RSQuery($theQuery);
				// And write XML Response back to the application
				RSReturnQueryResults($results);
				exit;
			}
		else
			{
				$results["result"] = "NOK";
				RSReturnArrayResults($results);
				exit;
			}

	}
else
	{
		$results["result"] = "NOK";
		RSReturnArrayResults($results);
		exit;
	}


?>
