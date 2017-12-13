<?php
//This php updates from v.4.4.5.2.90 to 4.4.6.3.91

//WARNING: UPDATE THE INCLUDE FROM A FINAL VERSION (Change x for real version)
include "./phpUpdate_00030_From_v4.4.5.2.90_to_4.4.6.3.91/updateAllTestCategoriesRelationsForClient.php";


//Launch the update php for the defined clients
$clientsToUpdate = array();
$clientsToUpdate[] = '1'; //Redsauce Client
$clientsToUpdate[] = '10'; //Innova Clinical Client

echo start_update_relations($clientsToUpdate);
?>