<?php
//***************************************************
// RSM configuration file
//***************************************************

$RShost      = 'localhost';
$RSdatabase  = 'rsm_dev';
$RSuser      = 'root';
$RSpassword  = 'root';

$RSmongohost = '{RSMmongohost}';
// Determine in combination with POST value RSsendUncompressed=1 if response will be sent uncompressed
$RSallowUncompressed = false;

// Determine in combination with POST value RSdebug = 1 if queries will be sent
$RSallowDebug = false;

$RStempPath = '/var/www/html/../phptmp/';

// URL of the api directory
$RSMapiURL   = 'http://localhost:81/rsm-api-2.0/server/htdocs/AppController/commands_RSM/api/';

// URL of the media server api
$RSMmediaURL   = 'http://localhost:81/rsm-api-2.0/server/htdocs/AppController/commands_MediaServer/api/';

// File and image cache configuration
$RSimageCache = '/var/www/rsm_image_cache';
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
