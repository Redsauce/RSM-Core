<?php
//***************************************************
// RSM configuration file
//***************************************************

$RShost      = '{RSMhost}';
$RSdatabase  = '{RSMdatabase}';
$RSuser      = '{RSMlogin}';
$RSpassword  = '{RSMpassword}';

$RSmongohost = '{RSMmongohost}';
// Determine in combination with POST value RSsendUncompressed=1 if response will be sent uncompressed
$RSallowUncompressed = true;

// Determine in combination with POST value RSdebug = 1 if queries will be sent
$RSallowDebug = true;

$RStempPath = '{PHPtempPath}';

// URL of the api directory
$RSMapiURL   = '{RSMAPIURL}';

// URL of the media server api
$RSMmediaURL   = '{RSMMEDIAURL}';

// File and image cache configuration
$RSimageCache = '{RSMImageCache}';
$RSfileCache  = '{RSMFileCache}';

// Protocol encryption support
$RSblowfishKey = '{RSMBLOWFISHKEY}';
?>
