<?php

//WARNING: UPDATE THE INCLUDE FROM A FINAL VERSION (Change x for real version)
include "./phpUpdate_00033_From_v4.4.9.3.94_to_4.4.10.3.95/updateAllProjectsForClient.php";


//Launch the update php for the defined clients
$clientsToUpdate = array();
//empty to check all clients
//$clientsToUpdate[] = '1'; //Redsauce Client

echo start_update_relations($clientsToUpdate);


?>
