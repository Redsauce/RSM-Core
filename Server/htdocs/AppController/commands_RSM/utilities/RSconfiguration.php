<?php
//***************************************************
// RSM configuration file
//***************************************************

$RShost      = getenv('DBHOST');
$RSdatabase  = getenv('DBNAME');
$RSuser      = getenv('DBUSERNAME');
$RSpassword  = getenv('DBPASSWORD');

$RSmongohost = getenv('MONGODBHOST');
// Determine in combination with POST value RSsendUncompressed=1 if response will be sent uncompressed
$RSallowUncompressed = true;

// Determine in combination with POST value RSdebug = 1 if queries will be sent
$RSallowDebug = true;

$RStempPath = getenv('TEMPPATH');

// URL of the api directory
$RSMapiURL   = getenv('APIURL');

// URL of the media server api
$RSMmediaURL   = getenv('MEDIAURL');

// File and image cache configuration
$RSimageCache = getenv('IMAGECACHE');
$RSfileCache  = getenv('FILECACHE');

// Determine if files/images cache will be used
$enable_image_cache  = true;
$enable_file_cache  = true;

// Protocol encryption support
$RSblowfishKey = getenv('BLOWFISHKEY');

// Code constants
$cstCDATAseparator       = ']]]]><![CDATA[>';
$cstRSsendUncompressed   = 'RSsendUncompressed';
$cstClientID             = 'clientID';
$cstMainPropertyID       = 'mainPropertyID';
$cstMainPropertyType     = 'mainPropertyType';
$cstReferredItemTypeID   = 'referredItemTypeID';
$cstRS_POST              = 'RS_POST';
$cstUTF8                 = 'UTF-8';
?>
