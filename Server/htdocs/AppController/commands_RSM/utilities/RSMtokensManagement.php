<?php
// Functions in this file related with the use of tokens in RSM
// - RSclientFromToken
// - RSenableToken
// - RSdisableToken
// - RSgetTokenID
// - RSdeleteTokenProperties
// - RSdeleteTokens
// - RStokensFromClient
// - RScountToken
// - RScreateToken
// - RSremovePermissionFromTokenProperty
// - RScreateTokenPermission
// - RSgetTokenPermissions
// - RShasREADTokenPermission
// - RShasCREATETokenPermission
// - RShasWRITETokenPermission
// - RShasDELETETokenPermission
// - RShasTokenPermissions
// - RShasTokenPermission

// -----------------------------
// Returns the clientID related with a token (only if exists and the relation is active) or 0 if there is no relation
function RSclientFromToken($RStoken) {

	$theQuery = "SELECT `RS_CLIENT_ID` FROM `rs_tokens`
                WHERE `RS_TOKEN` = '" . $RStoken . "'
                AND `RS_ENABLED` = '1'";

	$clients = RSQuery($theQuery);

	// Analyze results
	if ($clients && $clients->num_rows > 0) {
		$row = $clients->fetch_assoc();
		return $row['RS_CLIENT_ID'];
	} else {
			//query failed or client not related
			return 0;
	}
}

// -----------------------------
// Enable token for a clientID
function RSenableToken($RStoken, $clientID) {
	$results = RSQuery("UPDATE  rs_tokens
               SET  RS_ENABLED   = 1
               WHERE  RS_TOKEN   = '" . $RStoken . "'
               AND  RS_CLIENT_ID = " . $clientID);
	return $results;
}

// -----------------------------
// Disable token for a clientID
function RSdisableToken($RStoken, $clientID) {
	$results = RSQuery("UPDATE  rs_tokens
               SET  RS_ENABLED   = 0
               WHERE  RS_TOKEN   = '" . $RStoken . "'
               AND  RS_CLIENT_ID = " . $clientID);
	return $results;
}

// -----------------------------
// Retrieve the ID pertaining to the token
function RSgetTokenID($RStoken) {
	$results = RSQuery("SELECT RS_ID as tokenID
               FROM rs_tokens
               WHERE RS_TOKEN = '" . $RStoken . "'");

	if (!$results) {
		// There was a problem executing the query
		$response['result'] = "NOK";
		$response['description'] = "ERROR EXECUTING QUERY TO GATHER TOKEN ID";

		// And write XML Response back to the application
		RSReturnArrayResults($response);
	}

	// Obtain the token ID from the query results
	$result = $results->fetch_assoc();
	return $result["tokenID"];
}

// -----------------------------
// Delete the token properties
function RSdeleteTokenProperties($tokenID, $clientID) {
	$results = RSQuery("DELETE FROM rs_token_permissions
                        WHERE RS_CLIENT_ID = '" . $clientID . "'
                        AND   RS_TOKEN_ID  = '" . $tokenID . "'");
	return $results;
}

// -----------------------------
function RSdeleteTokens($RStoken, $clientID) {
	$results = RSQuery("DELETE FROM rs_tokens
                        WHERE RS_CLIENT_ID = '" . $clientID . "'
                        AND RS_TOKEN       = '" . $RStoken . "'");
	return $results;
}

// -----------------------------
function RStokensFromClient($clientID) {
	$results = RSQuery("SELECT  RS_TOKEN AS  'token',
                         RS_ENABLED       AS  'enabled'
                         FROM rs_tokens
                         WHERE RS_CLIENT_ID = '" . $clientID . "'");
	return $results;
}

// -----------------------------
function RScountToken($RStoken) {
	$results = RSQuery("SELECT COUNT('RS_TOKEN') as total
	                    FROM rs_tokens
	                    WHERE RS_TOKEN = '" . $RStoken . "'");
	return $results;
}

// -----------------------------
function RScreateToken($RStoken, $clientID) {
	$results = RSQuery("INSERT INTO rs_tokens (RS_ID, RS_TOKEN, RS_CLIENT_ID, RS_ENABLED)
                        SELECT COALESCE(MAX(RS_ID), 0)+1,
                            '" . $RStoken . "',
                            '" . $clientID . "',
                            '0'
                        FROM rs_tokens");
	return $results;
}

// -----------------------------
function RSremovePermissionFromTokenProperty($tokenID, $clientID, $propertyID, $permission) {
	$results = RSQuery("DELETE FROM rs_token_permissions
                            WHERE RS_CLIENT_ID = '" . $clientID . "'" . "
                                AND    RS_TOKEN_ID = '" . $tokenID . "'" . "
                                AND RS_PROPERTY_ID = '" . $propertyID . "'" . "
                                AND  RS_PERMISSION = '" . $permission . "'");
	return $results;
}

// -----------------------------
function RScreateTokenPermission($tokenID, $clientID, $propertyID, $permission) {
	$results = RSQuery("INSERT INTO rs_token_permissions (
						RS_CLIENT_ID  ,
						RS_TOKEN_ID   ,
						RS_PROPERTY_ID,
						RS_PERMISSION )
				    VALUES ('" . $clientID . "', " . "'" . $tokenID . "', " . "'" . $propertyID . "', " . "'" . $permission . "')");
	return $results;
}

// Dado un token y un propertyId, devuelve los permisos
function RSgetTokenPermissions($RStoken, $propertyId) {
	$tokenID = RSgetTokenID($RStoken);

	$theQuery = "SELECT RS_PERMISSION AS 'permission', RS_PROPERTY_ID as 'propertyID' FROM rs_token_permissions WHERE RS_TOKEN_ID = " . $tokenID . " AND RS_PROPERTY_ID= " . $propertyId;

	$results = RSQuery($theQuery);

	return $results;
}

function RShasREADTokenPermission($RStoken, $propertyId) {
	return RShasTokenPermission($RStoken, $propertyId, "READ");
}
function RShasCREATETokenPermission($RStoken, $propertyId) {
	return RShasTokenPermission($RStoken, $propertyId, "CREATE");
}
function RShasWRITETokenPermission($RStoken, $propertyId) {
	return RShasTokenPermission($RStoken, $propertyId, "WRITE");
}
function RShasDELETETokenPermission($RStoken, $propertyId) {
	return RShasTokenPermission($RStoken, $propertyId, "DELETE");
}

function RShasTokenPermissions($RStoken, $propertiesID, $permission) {
	foreach ($propertiesID as $propertyId) {

		if (!RShasTokenPermission($RStoken, ParsePID($propertyId, RSclientFromToken($RStoken)), $permission)) {
			return false;
		}
	}
	return true;
}

function RShasTokenPermission($RStoken, $propertyId, $permission) {
	$tokenID = RSgetTokenID($RStoken);

	// If the user needs a translated value related with itemTypes, we will see if the user has access to the translated main property of that itemtype
    if ((isset($GLOBALS['RS_POST']['translateIDs'])) && ($GLOBALS['RS_POST']['translateIDs'] == "true")) {
        $propertyType = getPropertyType($propertyId, RSclientFromToken($RStoken));
        if ($propertyType == "identifier" || $propertyType == "identifiers"){
            //Get the main property of the referred itemtype
            $mainPropertyID = getMainPropertyID(getClientPropertyReferredItemType($propertyId, RSclientFromToken($RStoken)), RSclientFromToken($RStoken));
            if (RShasTokenPermission($RStoken, $mainPropertyID, $permission) == false) return false;
         }
     }

    // Always verify the access to the property itself
    $theQuery = "SELECT RS_PERMISSION AS 'permission', RS_PROPERTY_ID as 'propertyID'  FROM rs_token_permissions WHERE "
				. " RS_TOKEN_ID = " . $tokenID
				. " AND RS_PROPERTY_ID= " . ParsePID($propertyId, RSclientFromToken($RStoken))
				. " AND RS_PERMISSION ='" . $permission . "'";
	$permissions = RSquery($theQuery);

	if (!$permissions) return false;
	if ($permissions->num_rows == 0) return false;

	return true;
}
?>
