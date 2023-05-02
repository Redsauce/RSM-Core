<?php
//***************************************************
// RSM configuration file
//***************************************************

$RShost      = getenv('DBHOST') == false ? 'dbhost_undefined' : getenv('BHOST');
$RSdatabase  = getenv('DBNAME') == false ? 'dbname_undefined' : getenv('DBNAME');
$RSuser      = getenv('DBUSERNAME') == false ? 'dbusername_undefined' :  getenv('DBUSERNAME');
$RSpassword  = getenv('DBPASSWORD') == false ? 'dbpassword_undefined' :  getenv('DBPASSWORD');

$RSmongohost = getenv('MONGODBHOST') == false ? 'mongodbhost_undefined' : getenv('MONGODBHOST');

// Determine in combination with POST value RSsendUncompressed=1 if response will be sent uncompressed
$RSallowUncompressed = true;

// Determine in combination with POST value RSdebug = 1 if queries will be sent
$RSallowDebug = strtolower(getenv('ALLOWDEBUG')) === "true";

$RStempPath = getenv('TEMPPATH') == false ? sys_get_temp_dir() : getenv('TEMPPATH');

// URL of the api directory
$RSMapiURL   = getenv('APIURL') == false ? 'apiurl_undefined' : getenv('APIURL');

// URL of the media server api
$RSMmediaURL   = getenv('MEDIAURL') == false ? 'mediaurl_undefined' : getenv('MEDIAURL');

// Image cache configuration
$RSimageCacheUndefined = 'imagecache_undefined'
$RSimageCache = getenv('IMAGECACHE') == false ? $RSimageCacheUndefined : getenv('IMAGECACHE');
if (!is_dir($RSimageCache) && getenv('IMAGECACHE') != false && $RSimageCache != $RSimageCacheUndefined) {
    mkdir($RSimageCache, 0770, true);
}
// Determine if images cache will be used
$enable_image_cache  = is_dir($RSimageCache) ? true : strtolower(getenv('ENABLE_IMAGE_CACHE')) === "true";

// File cache configuration
$RSfileCacheUndefined = 'filecache_undefined'
$RSfileCache  = getenv('FILECACHE') == false ? $RSfileCacheUndefined : getenv('FILECACHE');
if (!is_dir($RSfileCache) && getenv('FILECACHE') != false && $RSfileCache != $RSfileCacheUndefined) {
    mkdir($RSfileCache, 0770, true);
}
// Determine if files cache will be used
$enable_file_cache  = is_dir($RSfileCache) ? true : strtolower(getenv('ENABLE_FILE_CACHE')) === "true";

// Protocol encryption support
$RSblowfishKey = getenv('BLOWFISHKEY') == false ? 'blowfishkey_undefined' : getenv('BLOWFISHKEY');

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
