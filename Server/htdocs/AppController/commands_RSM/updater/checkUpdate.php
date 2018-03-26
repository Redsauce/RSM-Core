<?php
$RSUpdatingProcess = true;

// The user and password are not included
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSvalidationFunctions.php";
require_once "../utilities/RStools.php";

isset($GLOBALS['RS_POST']['RSappName' ]) ? $RSappName  = $GLOBALS['RS_POST']['RSappName' ] : dieWithError(400);
isset($GLOBALS['RS_POST']['RSplatform']) ? $RSplatform = $GLOBALS['RS_POST']['RSplatform'] : dieWithError(400);


// ServiceMode, si está definido, no importa el valor, ha de mostrar todas las versiones publicadas y no publicadas, caso contrario sólo las publicadas
if (isset($_POST['serviceMode'])) {
	$serviceMode = 1;
} else {
	$serviceMode = 0;
}

if ($serviceMode == 1) {
	$theQuery = "SELECT RS_BUILD,RS_URL,RS_SIGNATURE,RS_OS FROM rs_versions WHERE RS_NAME= '". $RSappName ."' AND `RS_OS`= '". $RSplatform ."' ORDER BY RS_BUILD DESC limit 1";
} else {
	$theQuery = "SELECT RS_BUILD,RS_URL,RS_SIGNATURE,RS_OS FROM rs_versions WHERE RS_NAME= '". $RSappName ."' AND `RS_OS`= '". $RSplatform ."' AND RS_PUBLIC= 1 ORDER BY RS_BUILD DESC limit 1";
}

//only for postmaster debug
if(isset($_POST['RSdebug'])){
	echo $theQuery;
}

$result = RSQuery($theQuery);


$data=array();

if($result->num_rows>0){
	$registro = $result->fetch_array();

	if($registro && $registro['RS_BUILD'] != $_POST['RSbuild']){
		//write into the data array
		$data[] = array("build"=>$registro['RS_BUILD'],"url"=>$registro['RS_URL'],"signature"=>$registro['RS_SIGNATURE'],"compatible"=>RSCheckCompatibleDB($serviceMode));

	} else {
		//write into the data array
		$data[] = array("compatible"=>RSCheckCompatibleDB($serviceMode));

	}


}else{
	//write into the data array
	$data[] = array("compatible"=>RSCheckCompatibleDB($serviceMode));

}

// And write XML Response back to the application
RSReturnArrayQueryResults($data);

?>
