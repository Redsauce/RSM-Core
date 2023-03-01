<?php
//***************************************************
// RSM configuration file
//***************************************************

$RShost      = 'localhost';
$RSdatabase  = 'localhost';
$RSuser      = 'root';
$RSpassword  = 'root';

$RSmongohost = '{RSMmongohost}';
// Determine in combination with POST value RSsendUncompressed=1 if response will be sent uncompressed
$RSallowUncompressed = false;

// Determine in combination with POST value RSdebug = 1 if queries will be sent
$RSallowDebug = false;

$RSimageCache = '/var/www/rsm_image_cache';

// URL of the api directory
$RSMapiURL   = '{RSMAPIURL}';

// URL of the media server api
$RSMmediaURL   = '{RSMMEDIAURL}';

// File and image cache configuration
$RSimageCache = '{RSMImageCache}';
$RSfileCache  = '/var/www/rsm_file_cache';

// Determine if files/images cache will be used
$enable_image_cache  = true;
$enable_file_cache  = true;

// Protocol encryption support
$RSblowfishKey = 'JPPQJD64YRGVCDGE';

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
