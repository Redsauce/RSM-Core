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

// Determine if files/images cache will be used
$enable_image_cache  = true;
$enable_file_cache  = true;

// Protocol encryption support
$RSblowfishKey = '{RSMBLOWFISHKEY}';

// Code constants
$cstCDATAseparator       = ']]]]><![CDATA[>';
$cstRSsendUncompressed   = 'RSsendUncompressed';
$cstClientID             = 'clientID';
$cstMainPropertyID       = 'mainPropertyID';
$cstMainPropertyType     = 'mainPropertyType';
$cstReferredItemTypeID   = 'referredItemTypeID';
$cstRS_POST              = 'RS_POST';
$cstUTF8                 = 'UTF-8';

// Mailing settings

$SMTPServer = '{SMTPServer}';
$mailUser = '{SMTPMailUser}';
$mailPassword = '{SMTPMailPassword}';
$mailRecipient = '{MailRecipient}';

?>
