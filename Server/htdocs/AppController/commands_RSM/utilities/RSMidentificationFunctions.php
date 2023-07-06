<?php
// ***********************************************************************************************************
//function _buildExtraRequestsString
//		--> returns a string represents extra conditions for the identification of a column
//		--> the parameter "extraRequests" must be an array of keys => values: 'columnName' => 'columnValue'
// ***********************************************************************************************************
	function _buildExtraRequestsString($extraRequests) {

		// first, the parameter must be not null
		if ($extraRequests == null) return '';

		// the parameter must be an array
		if (!is_array($extraRequests)) return '';

		// the array must be not empty
		if (count($extraRequests) == 0) return '';

		// build extra requests string
		$extraRequestsString = '';
		foreach ($extraRequests as $column=>$value) {
			$extraRequestsString .= ' AND '.$column.' = "'.$value.'"';
		}

		return $extraRequestsString;
	}


//************************************************************************************************************
//function getLastIdentification
//************************************************************************************************************
	function getLastIdentification($tableName,$columnName,$clientID,$extraRequests = null) {
		// build extra requests string
		$extraReqs = _buildExtraRequestsString($extraRequests);

		//query db
		$query = RSquery("SELECT MAX(".$columnName.") as MAX FROM ".$tableName." WHERE RS_CLIENT_ID = ".$clientID.$extraReqs);

		// TODO: Validate that the $query object is a valid one and return -1 if it isn't

		if($query->num_rows != 1) return 0;

		$orig = @$query->fetch_assoc();

		if($orig['MAX'] != 'NULL') return $orig['MAX'];

    return 0;
	}

//************************************************************************************************************
//function getNextIdentification
//************************************************************************************************************
	function getNextIdentification($tableName,$columnName,$clientID,$extraRequests = null) {
		// get last ID
		$lastID = getLastIdentification($tableName,$columnName,$clientID,$extraRequests);

		if ($lastID < 0) return -1; //Error en la query

		return $lastID + 1;
	}

	//************************************************************************************************************
	//function getNextItemTypeIdentification
	//************************************************************************************************************
		function getNextItemTypeIdentification($itemTypeID,$clientID) {
			// get last ID
			$query = RSquery("SELECT RS_LAST_ITEM_ID FROM rs_item_types WHERE RS_ITEMTYPE_ID=" . $itemTypeID . " AND RS_CLIENT_ID = " . $clientID);

			$results = $query->fetch_assoc();

			$nextID = $results['RS_LAST_ITEM_ID'];

			return $nextID + 1;
		}

//************************************************************************************************************
//function getNextOrder
//************************************************************************************************************
	// Esta función devuelve el valor máximo dentro del campo $campoName,
	// de la tabla $tablename, del website $webID,
	// cuya referencia de $campo sea $valueCampo

	function getNextOrder($tableName,$campoName,$webID,$valueCampo) {
		$queryMaxOrder= RSquery("SELECT MAX(`HB_ORDER`) as MAX FROM `".$tableName."` WHERE `HB_WEBSITE_ID` = '".$webID."' AND `".$campoName."` = ".$valueCampo);
		if($queryMaxOrder->num_rows==0) return 0;

		$orig=$queryMaxOrder->fetch_assoc();
		return $orig['MAX']+1;
	}
//*****************************************************************************
//function getGenericNext
//*****************************************************************************
	//Esta función devuelve el valor máximo +1

	function getGenericNext($tableName,$campoName,$conditions=array()) {
		//generate basic query
		$theQuery="SELECT MAX(`".$campoName."`) as MAX FROM `".$tableName."`";

		//check conditions
		if(count($conditions)>0){
			$theQuery.=" WHERE";
			foreach($conditions as $fld_name=>$value){
				$theQuery.=" `".$fld_name."` = '".$value."' AND";
			}

			//remove last AND
			$theQuery=substr($theQuery,0,strlen($theQuery)-3);
		}
		//execute query
		$queryMax= RSquery($theQuery);

		//check value returned
		if($queryMax->num_rows==0){
			return 0;
		}

		$orig=$queryMax->fetch_assoc();
		return $orig['MAX']+1;
	}

//************************************************************************************************************
//function value_exists
//************************************************************************************************************
function value_exists($value,$tableName,$colName,$clientID,$extraReqs=null) {

	// build extra requests string
	$extraReqs = _buildExtraRequestsString($extraReqs);

	$query = RSquery('SELECT '.$colName.' FROM '.$tableName.' WHERE RS_CLIENT_ID = '.$clientID.' AND '.$colName.' = "'.$value.'"'.$extraReqs);

	if ($query->num_rows > 0) {
		return true;
	}

	return false;
}
