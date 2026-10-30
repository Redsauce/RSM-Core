<?php
// Execute the affected production blocks without a production connection.
function readAssert($condition, $message) { if (!$condition) throw new RuntimeException($message); }
set_error_handler(function ($severity, $message) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity);
    return false;
});
$root = dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/';
$source = file_get_contents($root . 'shared/classDataManager_getItems.php');
$start = strpos($source, '//creck if need');
$end = strpos($source, '// Prepare the filter properties', $start);
readAssert($start !== false && $end !== false, 'Order block not found');
$orderBlock = substr($source, $start, $end - $start);
function getPropertyType($id, $client) { return 'identifier'; }
function isSingleIdentifier($type) { return $type === 'identifier'; }
function isMultiIdentifier($type) { return $type === 'identifiers'; }
function parsePID($id, $client) { return 200; }
function array_search_ID($id, $rows) { foreach ($rows as $index=>$row) if ($row['ID'] == $id) return $index; return false; }
foreach (array(true, false) as $allowed) {
    unset($property_parsed);
    $clientID = 7; $orderPropertyID = 'parent';
    $visibleProperties = $allowed ? array(200) : array(100);
    $returnProperties = array();
    eval($orderBlock);
    readAssert(count($returnProperties) === ($allowed ? 1 : 0), 'Order property visibility mismatch');
    if ($allowed) readAssert($returnProperties[0]['ID'] === 200, 'Wrong ordering property returned');
}
readAssert(function_exists('imagecreatefromstring'), 'Enable GD for the image regression');
class ReadResponse extends RuntimeException { }
function RSError($message) { $GLOBALS['readLog'] = $message; }
function dieWithError($status) { throw new ReadResponse('', $status); }
$source = file_get_contents($root . 'api/api_getPicture.php');
$start = strpos($source, '$originalImage = @imagecreatefromstring');
$end = strpos($source, 'dieWithError(400);', $start);
readAssert($start !== false && $end !== false, 'Image validation block not found');
$imageBlock = substr($source, $start, $end + strlen('dieWithError(400);') - $start) . "\n}";
$clientID = 7; $itemID = 2675; $propertyID = 390;
$imageOriginal = 'invalid-image-private-content';
$status = null;
try { eval($imageBlock); } catch (ReadResponse $response) { $status = $response->getCode(); }
readAssert($status === 400, 'Invalid image response changed');
readAssert(strpos($readLog, $imageOriginal) === false && strpos($readLog, 'property=390') !== false, 'Image data leaked or diagnostic missing');
$fixture = imagecreatetruecolor(1,1);
ob_start(); imagepng($fixture); $imageOriginal = ob_get_clean();
eval($imageBlock);
readAssert($originalImage !== false && imagesx($originalImage) === 1, 'Valid image rejected');
restore_error_handler();
echo "PASS: order property visibility, invalid image handling and valid image decoding\n";
