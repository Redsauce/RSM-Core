<?php
//***************************************************
//***************************************************

// Database connection startup
include_once "../utilities/RSdatabase.php";
include_once "../utilities/RSMitemsManagement.php";

$clientID = $GLOBALS['RS_POST']['clientID'];
$operationID = $GLOBALS['RS_POST']['operationID'];
$subAccountID = $GLOBALS['RS_POST']['subAccountID'];

// get the operations item type
$operationItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['operations'], $clientID);

// get the concept item type
$conceptItemTypeID = getClientItemTypeID_RelatedWith_byName($definitions['concepts'], $clientID);

// get concepts properties IDs
$conceptOperationPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptOperationID'], $clientID);
$conceptNamePropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptName'], $clientID);
$conceptProjectPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptProjectID'], $clientID);
$conceptUnitsPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptUnits'], $clientID);
$conceptIVAPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptIVA'], $clientID);
$conceptPricePropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptPrice'], $clientID);
$conceptDeductionPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptDeduction'], $clientID);
$conceptStockItemPropertyID = getClientPropertyID_RelatedWith_byName($definitions['conceptStockItemID'], $clientID);

// get properties default values
$conceptNamePropertyDefault = getClientPropertyDefaultValue($conceptNamePropertyID			, $clientID);
$conceptProjectPropertyDefault = getClientPropertyDefaultValue($conceptProjectPropertyID	, $clientID);
$conceptUnitsPropertyDefault = getClientPropertyDefaultValue($conceptUnitsPropertyID		, $clientID);
$conceptIVAPropertyDefault = getClientPropertyDefaultValue($conceptIVAPropertyID			, $clientID);
$conceptPricePropertyDefault = getClientPropertyDefaultValue($conceptPricePropertyID		, $clientID);
$conceptDeductionPropertyDefault = getClientPropertyDefaultValue($conceptDeductionPropertyID, $clientID);
$conceptStockItemPropertyDefault = getClientPropertyDefaultValue($conceptStockItemPropertyID, $clientID);

//check operation values
$isEmptyOperation=true;

if(getPropertyValue($definitions['operationSubAccountID'], $operationItemTypeID, $operationID, $clientID)!= $subAccountID){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationOperationID'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationOperationID'], $clientID), $clientID)&&getPropertyValue($definitions['operationOperationID'], $operationItemTypeID, $operationID, $clientID)!= 0){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationInvoiceDate'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationInvoiceDate'], $clientID), $clientID)){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationPayMethod'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationPayMethod'], $clientID), $clientID)){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationBase'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationBase'], $clientID), $clientID)&&getPropertyValue($definitions['operationBase'], $operationItemTypeID, $operationID, $clientID)!= 0){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationIVA'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationIVA'], $clientID), $clientID)&&getPropertyValue($definitions['operationIVA'], $operationItemTypeID, $operationID, $clientID)!= 0){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationDeduction'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationDeduction'], $clientID), $clientID)&&getPropertyValue($definitions['operationDeduction'], $operationItemTypeID, $operationID, $clientID)!= 0){$isEmptyOperation=false;}

if(getPropertyValue($definitions['operationTotal'], $operationItemTypeID, $operationID, $clientID)!= getClientPropertyDefaultValue(getClientPropertyID_RelatedWith_byName($definitions['operationTotal'], $clientID), $clientID)&&getPropertyValue($definitions['operationTotal'], $operationItemTypeID, $operationID, $clientID)!= 0){$isEmptyOperation=false;}

if($isEmptyOperation){
	//check concepts values
	$filterProperties = array(array('ID' => $conceptOperationPropertyID, 'value' => $operationID));
	$returnProperties = array();
	$returnProperties[] = array('ID' => $conceptNamePropertyID, 'name' => 'name');
	$returnProperties[] = array('ID' => $conceptProjectPropertyID, 'name' => 'projectID');
	$returnProperties[] = array('ID' => $conceptUnitsPropertyID, 'name' => 'units');
	$returnProperties[] = array('ID' => $conceptIVAPropertyID, 'name' => 'VAT');
	$returnProperties[] = array('ID' => $conceptPricePropertyID, 'name' => 'price');
	$returnProperties[] = array('ID' => $conceptDeductionPropertyID, 'name' => 'deduction');
	$returnProperties[] = array('ID' => $conceptStockItemPropertyID, 'name' => 'stockItemID');
	
	$result = getFilteredItemsIDs($conceptItemTypeID, $clientID, $filterProperties, $returnProperties);
	
	foreach($result as $row){		 
		if($row["name"]!= $conceptNamePropertyDefault){$isEmptyOperation=false;}
		if($row["projectID"]!= $conceptProjectPropertyDefault&&$row["projectID"]!=0){$isEmptyOperation=false;}
		if($row["units"]!= $conceptUnitsPropertyDefault&&$row["units"]!=0){$isEmptyOperation=false;}
		if($row["VAT"]!= $conceptIVAPropertyDefault&&$row["VAT"]!=0){$isEmptyOperation=false;}
		if($row["price"]!= $conceptPricePropertyDefault&&$row["price"]!=0){$isEmptyOperation=false;}
		if($row["deduction"]!= $conceptDeductionPropertyDefault&&$row["deduction"]!=0){$isEmptyOperation=false;}
		if($row["stockItemID"]!= $conceptStockItemPropertyDefault&&$row["stockItemID"]!=0){$isEmptyOperation=false;}
	}
}
	
if($isEmptyOperation){
	//remove concepts
	foreach($result as $row){
		deleteItem($conceptItemTypeID, $row['ID'], $clientID);
	}
	
	//remove operation
	deleteItem($operationItemTypeID, $operationID, $clientID);
}


$results['result'] = "OK";

// And write XML Response back to the application
RSReturnArrayResults($results);
?>