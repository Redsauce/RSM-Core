<?php
//This php updates from v4.4.7.2.92 to 4.4.8.3.93

//WARNING: UPDATE THE INCLUDE FROM A FINAL VERSION (Change x for real version)
include "./phpUpdate_00031_From_v4.4.7.2.92_to_4.4.8.3.93/updateAllOperationsForClient.php";


//Launch the update php for the defined clients
$clientsToUpdate = array();
//$clientsToUpdate[] = '1'; //Redsauce Client
$clientsToUpdate[] = array('11','4'); //Dinamic-sport, caja tienda física

echo start_update_relations($clientsToUpdate);


?>
