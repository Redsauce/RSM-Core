<?php
// Database connection startup
require_once "RSdatabase.php";

// Definitions
$url    = base64_decode($GLOBALS['RS_POST']['url'   ]);
$post   = base64_decode($GLOBALS['RS_POST']['post'  ]);
$result = base64_decode($GLOBALS['RS_POST']['result']);

$query = "INSERT INTO `rs_error_log` (`RS_DATE`,`RS_URL`,`RS_POST`,`RS_RESULT`) VALUES (NOW(),'".$mysqli->real_escape_string($url)."','".$mysqli->real_escape_string($post)."','".$mysqli->real_escape_string($result)."')";

// Query the database
if (RSquery($query)) {
    //send mail to admin
    /*$mensaje = "Se ha registrado un nuevo error en RSM:\n\nURL:\n".$url."\n\nPOST DATA SENT:\n".$post."\n\nSERVER RESPONSE:\n".$result."";
    $mensaje = wordwrap($mensaje, 70);
    mail('webmaster@redsauce.net', 'Nuevo error en RSM', $mensaje);*/

    $results['result'] = "OK";
    $results['ID'] = $mysqli->insert_id;
} else {
    //send mail to admin
    /*$mensaje = "Se ha notificado un nuevo error en RSM, este resultado NO HA PODIDO SER ALMACENADO EN LA BD:\n\nURL:\n".$url."\n\nPOST DATA SENT:\n".$post."\n\nSERVER RESPONSE:\n".$result."";
    $mensaje = wordwrap($mensaje, 70);
    mail('webmaster@redsauce.net', 'Nuevo error en RSM (NO ALMACENADO)', $mensaje);*/

    $results['result'] = "NOK";
}

// Write XML Response back to the application
RSReturnArrayResults($results);
