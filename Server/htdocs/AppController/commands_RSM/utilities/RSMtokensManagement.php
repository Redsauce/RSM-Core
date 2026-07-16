<?php
// Functions in this file related with the use of tokens in RSM
// - RSclientFromToken
// - RSisTokenEnabled
// - RSenableToken
// - RSdisableToken
// - RSgetTokenID
// - RSdeleteTokenProperties
// - RSdeleteTokens
// - RStokensFromClient
// - RScountToken
// - RScreateToken
// - RSeditToken
// - RSgetTokenCustomerScope
// - RSisCustomerScopedToken
// - RSisTokenCustomerScopeValid
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
// Returns the clientID related with a token or 0 if there is no relation.
function RSclientFromToken($RStoken) {

	$theQuery = "SELECT `RS_CLIENT_ID` FROM `rs_tokens`
                WHERE `RS_TOKEN` = '" . $RStoken . "'";

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

// Return raw token metadata for administration and authorization decisions.
// This lookup deliberately includes master templates; callers decide whether
// the token is being managed or used as an API credential.
function RSgetTokenMetadata($RStoken, $clientID = null) {
	global $mysqli;

	if ($RStoken === '') return null;

	$clientFilter = is_null($clientID) ? '' : ' AND token.RS_CLIENT_ID = ' . intval($clientID);
	$results = RSQuery("SELECT token.RS_ID AS tokenID,
							 token.RS_CLIENT_ID AS clientID,
							 token.RS_ENABLED AS enabled,
							 token.RS_MASTER_TEMPLATE AS isMasterTemplate,
							 token.RS_PARENT_MASTER_TOKEN AS parentMasterTokenID,
							 token.RS_CUSTOMER_ITEM_TYPE_ID AS customerItemTypeID,
							 token.RS_CUSTOMER_ITEM_ID AS customerItemID,
							 parent.RS_ID AS parentTokenID,
							 parent.RS_ENABLED AS parentEnabled,
							 parent.RS_MASTER_TEMPLATE AS parentIsMasterTemplate,
							 parent.RS_PARENT_MASTER_TOKEN AS parentParentMasterTokenID
					FROM rs_tokens token
					LEFT JOIN rs_tokens parent
					  ON parent.RS_CLIENT_ID = token.RS_CLIENT_ID
					 AND parent.RS_ID = token.RS_PARENT_MASTER_TOKEN
					WHERE token.RS_TOKEN = '" . $mysqli->real_escape_string($RStoken) . "'" . $clientFilter . "
					LIMIT 1");

	if (!$results || $results->num_rows == 0) return null;

	$row = $results->fetch_assoc();
	$metadata = array(
		'tokenID' => intval($row['tokenID']),
		'clientID' => intval($row['clientID']),
		'enabled' => intval($row['enabled']) === 1,
		'isMasterTemplate' => intval($row['isMasterTemplate']) === 1,
		'parentMasterTokenID' => intval($row['parentMasterTokenID']),
		'customerItemTypeID' => intval($row['customerItemTypeID']),
		'customerItemID' => intval($row['customerItemID']),
		'parentEnabled' => intval($row['parentEnabled']) === 1,
		'parentIsMasterTemplate' => intval($row['parentIsMasterTemplate']) === 1,
		'parentParentMasterTokenID' => intval($row['parentParentMasterTokenID'])
	);

	$hasParent = $metadata['parentMasterTokenID'] > 0;
	$validStandalone = !$metadata['isMasterTemplate'] && !$hasParent;
	$validMaster = $metadata['isMasterTemplate'] && !$hasParent;
	$validChild = !$metadata['isMasterTemplate'] && $hasParent
		&& intval($row['parentTokenID']) === $metadata['parentMasterTokenID']
		&& $metadata['parentIsMasterTemplate']
		&& $metadata['parentParentMasterTokenID'] === 0;

	$metadata['isChild'] = $validChild;
	$metadata['relationshipValid'] = $validStandalone || $validMaster || $validChild;
	return $metadata;
}

function RSgetTokenMetadataByID($tokenID, $clientID) {
	global $mysqli;
	$results = RSQuery("SELECT RS_TOKEN AS token
						FROM rs_tokens
						WHERE RS_ID = " . intval($tokenID) . "
						AND RS_CLIENT_ID = " . intval($clientID) . "
						LIMIT 1");
	if (!$results || $results->num_rows == 0) return null;
	$row = $results->fetch_assoc();
	return RSgetTokenMetadata($row['token'], $clientID);
}

// API credential validity. Masters never authenticate. Children additionally
// require an enabled, valid direct master; disabling one child affects only it.
function RSisTokenEnabled($RStoken) {
	$metadata = RSgetTokenMetadata($RStoken);
	if (is_null($metadata) || !$metadata['enabled'] || !$metadata['relationshipValid'] || $metadata['isMasterTemplate']) return false;
	return !$metadata['isChild'] || $metadata['parentEnabled'];
}

// Resolve the permission row owner. Runtime calls reject masters and require
// full credential validity; management reads may inspect masters/disabled rows.
function RSgetEffectivePermissionTokenID($RStoken, $managementContext = false) {
	$metadata = RSgetTokenMetadata($RStoken);
	if (is_null($metadata) || !$metadata['relationshipValid']) return 0;

	if ($managementContext) {
		return $metadata['isChild'] ? $metadata['parentMasterTokenID'] : $metadata['tokenID'];
	}

	if (!RSisTokenEnabled($RStoken)) return 0;
	return $metadata['isChild'] ? $metadata['parentMasterTokenID'] : $metadata['tokenID'];
}

function RStokenHasChildren($tokenID, $clientID) {
	$results = RSQuery("SELECT 1 FROM rs_tokens
						WHERE RS_CLIENT_ID = " . intval($clientID) . "
						AND RS_PARENT_MASTER_TOKEN = " . intval($tokenID) . "
						LIMIT 1");
	return $results && $results->num_rows > 0;
}

// Return the optional customer scope configured for a token.
// Standard tokens have both customer fields empty and must keep the previous behavior.
// A token with only one customer field populated is invalid and must fail closed.
function RSgetTokenCustomerScope($RStoken) {
	if ($RStoken == '') {
		return array('customerItemTypeID' => 0, 'customerItemID' => 0, 'valid' => true, 'scoped' => false);
	}

	$metadata = RSgetTokenMetadata($RStoken);
	if (is_null($metadata) || !$metadata['enabled']) {
		return array('customerItemTypeID' => 0, 'customerItemID' => 0, 'valid' => false, 'scoped' => false);
	}

	$customerItemTypeID = $metadata['customerItemTypeID'];
	$customerItemID = $metadata['customerItemID'];
	$scoped = ($customerItemTypeID > 0 && $customerItemID > 0);
	$valid = (($customerItemTypeID == 0 && $customerItemID == 0) || $scoped);

	return array(
		'customerItemTypeID' => $customerItemTypeID,
		'customerItemID' => $customerItemID,
		'valid' => $valid,
		'scoped' => $scoped
	);
}

// True only when the token has a complete customer item type + customer item pair.
function RSisCustomerScopedToken($RStoken) {
	$scope = RSgetTokenCustomerScope($RStoken);
	return $scope['valid'] && $scope['scoped'];
}

// Used by item authorization helpers to reject partially configured customer scopes.
function RSisTokenCustomerScopeValid($RStoken) {
	$scope = RSgetTokenCustomerScope($RStoken);
	return $scope['valid'];
}

function RSgetTokenCustomerItemTypeID($RStoken) {
	$scope = RSgetTokenCustomerScope($RStoken);
	return $scope['customerItemTypeID'];
}

function RSgetTokenCustomerItemID($RStoken) {
	$scope = RSgetTokenCustomerScope($RStoken);
	return $scope['customerItemID'];
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

// Delete a token atomically. Referenced masters cannot be removed because that
// would invalidate every child credential.
function RSdeleteTokenSafely($RStoken, $clientID) {
	global $mysqli;
	$metadata = RSgetTokenMetadata($RStoken, $clientID);
	if (is_null($metadata)) return true;

	$mysqli->begin_transaction();
	try {
		$lockedToken = RSQuery("SELECT RS_ID FROM rs_tokens
								WHERE RS_CLIENT_ID = " . intval($clientID) . "
								AND RS_ID = " . intval($metadata['tokenID']) . "
								FOR UPDATE");
		if (!$lockedToken || $lockedToken->num_rows != 1) throw new Exception('Unable to lock token');
		if ($metadata['isMasterTemplate'] && RStokenHasChildren($metadata['tokenID'], $clientID)) {
			throw new Exception('Master token has children');
		}
		if (!RSdeleteTokenProperties($metadata['tokenID'], $clientID)) throw new Exception('Unable to delete token permissions');
		if (!RSdeleteTokens($RStoken, $clientID)) throw new Exception('Unable to delete token');
		$mysqli->commit();
		return true;
	} catch (Exception $exception) {
		$mysqli->rollback();
		return false;
	}
}

// -----------------------------
// Includes customer scope fields so the RSM client can display/configure scoped tokens.
function RStokensFromClient($clientID) {
	$results = RSQuery("SELECT  RS_TOKEN AS  'token',
                         RS_ENABLED       AS  'enabled',
                         RS_CUSTOMER_ITEM_TYPE_ID AS 'customerItemTypeID',
                         RS_CUSTOMER_ITEM_ID AS 'customerItemID',
                         RS_TOKEN_ALIAS AS 'tokenAlias',
						 RS_MASTER_TEMPLATE AS 'isMasterTemplate',
						 RS_PARENT_MASTER_TOKEN AS 'parentMasterTokenID'
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
// Creates either a standard token or a customer-scoped token.
// Both customer fields must be supplied together; partial scope data is unsafe.
function RScreateToken($RStoken, $clientID, $customerItemTypeID = 0, $customerItemID = 0, $isMasterTemplate = false, $parentMasterTokenID = 0) {
	global $mysqli;

	$customerItemTypeID = intval($customerItemTypeID);
	$customerItemID = intval($customerItemID);
	$clientID = intval($clientID);
	$isMasterTemplate = (bool)$isMasterTemplate;
	$parentMasterTokenID = intval($parentMasterTokenID);

	if (($customerItemTypeID == 0 && $customerItemID != 0) || ($customerItemTypeID != 0 && $customerItemID == 0)) {
		return false;
	}
	if ($isMasterTemplate && $parentMasterTokenID > 0) return false;
	if ($parentMasterTokenID < 0) return false;

	$customerItemTypeValue = ($customerItemTypeID > 0) ? "'" . $customerItemTypeID . "'" : "NULL";
	$customerItemValue = ($customerItemID > 0) ? "'" . $customerItemID . "'" : "NULL";

	$mysqli->begin_transaction();
	try {
		if ($parentMasterTokenID > 0) {
			$parent = RSQuery("SELECT RS_MASTER_TEMPLATE, RS_PARENT_MASTER_TOKEN
							 FROM rs_tokens
							 WHERE RS_CLIENT_ID = " . $clientID . "
							 AND RS_ID = " . $parentMasterTokenID . "
							 FOR UPDATE");
			if (!$parent || $parent->num_rows != 1) throw new Exception('Invalid master token');
			$parentRow = $parent->fetch_assoc();
			if (intval($parentRow['RS_MASTER_TEMPLATE']) !== 1 || intval($parentRow['RS_PARENT_MASTER_TOKEN']) !== 0) {
				throw new Exception('Invalid master token relationship');
			}
		}

		$results = RSQuery("INSERT INTO rs_tokens (RS_ID, RS_TOKEN, RS_CLIENT_ID, RS_CUSTOMER_ITEM_TYPE_ID, RS_CUSTOMER_ITEM_ID, RS_ENABLED, RS_MASTER_TEMPLATE, RS_PARENT_MASTER_TOKEN)
							SELECT COALESCE(MAX(RS_ID), 0)+1,
								'" . $mysqli->real_escape_string($RStoken) . "',
								'" . $clientID . "',
								" . $customerItemTypeValue . ",
								" . $customerItemValue . ",
								'0',
								'" . ($isMasterTemplate ? 1 : 0) . "',
								'" . $parentMasterTokenID . "'
							FROM rs_tokens
							WHERE RS_CLIENT_ID = " . $clientID);
		if (!$results) throw new Exception('Unable to create token');
		$mysqli->commit();
		return $results;
	} catch (Exception $exception) {
		$mysqli->rollback();
		return false;
	}
}

// -----------------------------
// Edits the optional customer scope and alias for an existing token.
// Omitted values are not updated.
function RSeditToken($RStoken, $clientID, $customerItemTypeID = null, $customerItemID = null, $tokenAlias = null) {
	global $mysqli;

	$updates = array();

	if (!is_null($customerItemTypeID) && !is_null($customerItemID)) {
		$updates[] = "RS_CUSTOMER_ITEM_TYPE_ID = '" . intval($customerItemTypeID) . "'";
		$updates[] = "RS_CUSTOMER_ITEM_ID = '" . intval($customerItemID) . "'";
	}

	if (!is_null($tokenAlias)) {
		$updates[] = "RS_TOKEN_ALIAS = '" . $mysqli->real_escape_string($tokenAlias) . "'";
	}

	if (count($updates) == 0) {
		return true;
	}

	$results = RSQuery("UPDATE rs_tokens
                        SET " . implode(", ", $updates) . "
                        WHERE RS_TOKEN = '" . $mysqli->real_escape_string($RStoken) . "'
                        AND RS_CLIENT_ID = " . intval($clientID));
	return $results;
}

// -----------------------------
function RSremovePermissionFromTokenProperty($tokenID, $clientID, $propertyID, $permission) {
	$metadata = RSgetTokenMetadataByID($tokenID, $clientID);
	if (is_null($metadata) || $metadata['isChild'] || !$metadata['relationshipValid']) return false;
	$results = RSQuery("DELETE FROM rs_token_permissions
                            WHERE RS_CLIENT_ID = '" . $clientID . "'" . "
                                AND    RS_TOKEN_ID = '" . $tokenID . "'" . "
                                AND RS_PROPERTY_ID = '" . $propertyID . "'" . "
                                AND  RS_PERMISSION = '" . $permission . "'");
	return $results;
}

// -----------------------------
function RScreateTokenPermission($tokenID, $clientID, $propertyID, $permission) {
	$metadata = RSgetTokenMetadataByID($tokenID, $clientID);
	if (is_null($metadata) || $metadata['isChild'] || !$metadata['relationshipValid']) return false;
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
	$tokenID = RSgetEffectivePermissionTokenID($RStoken);
	if ($tokenID <= 0) return false;

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
	global $cstRS_POST;

	if (!RSisTokenEnabled($RStoken)) {
		return false;
	}

	$tokenID = RSgetEffectivePermissionTokenID($RStoken);
	if ($tokenID <= 0) return false;

	// If the user needs a translated value related with itemTypes, we will see if the user has access to the translated main property of that itemtype
	if ((isset($GLOBALS[$cstRS_POST]['translateIDs'])) && ($GLOBALS[$cstRS_POST]['translateIDs'] == "true")) {
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
