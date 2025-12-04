<?php
// Database connection startup
require_once "../utilities/RSdatabase.php";

if (($GLOBALS[$cstRS_POST][$cstClientID] != 0) AND ($GLOBALS[$cstRS_POST][$cstUserID] != 0))
	{
		$theQuery = "SELECT DISTINCT rs_properties_groups.RS_PROPERTY_ID as propertyID FROM rs_properties_groups WHERE rs_properties_groups.RS_GROUP_ID IN ( SELECT RS_GROUP_ID FROM rs_users_groups WHERE RS_USER_ID =". $GLOBALS[$cstRS_POST][$cstUserID] ." AND RS_CLIENT_ID=". $GLOBALS[$cstRS_POST][$cstClientID].")";

		if(isset($GLOBALS[$cstRS_POST]['RSdebug'])&&$GLOBALS[$cstRS_POST]['RSdebug'])
			{
				echo $theQuery;
			}

		$results = RSQuery($theQuery);
	}
else
	{
		$results['result'] = "NOK";
	}

// And write XML Response back to the application
RSReturnQueryResults($results);
?>
