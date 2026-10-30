<?php
// Isolate response helpers from the database/security bootstrap. Run with PHP CLI.
namespace ResponseThresholdTest;

require __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSconfiguration.php';
require __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSresponseTempFiles.php';
$source = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSdatabase.php');
foreach (array('RSGetResponseFileFieldThreshold', '_predictNumberOfFields',
    'RSReturnArrayQueryResults', 'RSReturnQueryResults', 'gzCompressFile',
    'checkCompressionVersion', 'removeTmpFile') as $name) {
    if (!preg_match('/^function ' . $name . '\(.*?^\}/ms', $source, $match)) {
        throw new \RuntimeException('Cannot extract ' . $name);
    }
    $code = preg_replace('/\bexit\s*;/', 'return;', $match[0]);
    if ($name === 'gzCompressFile' || $name === 'removeTmpFile') {
        $code = str_replace('function ' . $name . '(', 'function original_' . $name . '(', $code);
    }
    eval('namespace ' . __NAMESPACE__ . ';' . $code);
}

function header($value) { $GLOBALS['responseHeaders'][] = $value; }
function RSCreateResponseTempFile($directory) {
    if ($GLOBALS['failTempFile']) return false;
    $path = \RSCreateResponseTempFile($directory);
    $GLOBALS['createdFiles'][] = $path;
    return $path;
}
function gzCompressFile($file, $level = 9) {
    return $GLOBALS['failCompression'] ? false : original_gzCompressFile($file, $level);
}
function removeTmpFile($file, $prefix = 'RSR') {
    $GLOBALS['removedFiles'][] = $file;
    original_removeTmpFile($file, $prefix);
}
function mem_usage_check() {}
function checkTriggeredEvents($client) {}
function check($condition, $message) {
    if (!$condition) throw new \RuntimeException($message);
}

class Result {
    public $num_rows;
    public $field_count = 1;
    private $remaining;
    public function __construct($rows) { $this->num_rows = $this->remaining = $rows; }
    public function fetch_assoc() { return $this->remaining-- > 0 ? array('value' => 'sample') : null; }
}

check($RSresponseFileFieldThreshold === 2000, 'Configuration default');
$RStempPath = sys_get_temp_dir() . '/rsm-threshold-' . bin2hex(random_bytes(8));
mkdir($RStempPath);
$RSallowUncompressed = true;
$GLOBALS[$cstRS_POST] = array();
$cases = array(
    array('default below', 2000, 1999, false),
    array('default boundary', 2000, 2000, false),
    array('default above', 2000, 2001, true),
    array('missing', null, 2001, true),
    array('negative', -1, 2000, false),
    array('fractional', 0.5, 2000, false),
    array('boolean', false, 2000, false),
    array('nonnumeric', 'invalid', 2001, true),
    array('numeric string', '1', 2000, false),
    array('custom below', 2, 1, false),
    array('custom boundary', 2, 2, false),
    array('custom above', 2, 3, true),
    array('zero positive', 0, 1, false),
    array('zero large', 0, 2001, false),
    array('zero empty', 0, 0, false)
);
$count = 0;
try {
    foreach (array('RSReturnArrayQueryResults', 'RSReturnQueryResults') as $responder) {
        foreach ($cases as $case) {
            foreach (array('plain', 'compressed', 'compression failure', 'file failure') as $mode) {
                list($label, $threshold, $rows, $disk) = $case;
                if ($threshold === null) unset($RSresponseFileFieldThreshold);
                else $RSresponseFileFieldThreshold = $threshold;
                $failTempFile = $mode === 'file failure';
                $failCompression = $mode === 'compression failure';
                $createdFiles = $removedFiles = $responseHeaders = array();
                // A request parameter must not override the server configuration.
                $GLOBALS[$cstRS_POST] = array('RSresponseFileFieldThreshold' => 0);
                $input = $responder === 'RSReturnQueryResults'
                    ? new Result($rows) : array_fill(0, $rows, array('value' => 'sample'));
                ob_start();
                $function = __NAMESPACE__ . '\\' . $responder;
                $function($input, $mode !== 'plain');
                $body = ob_get_clean();
                $usedDisk = $disk && !$failTempFile;
                $compressed = $mode !== 'plain' && !($usedDisk && $failCompression);
                $xml = $compressed ? ($usedDisk ? gzdecode($body) : gzuncompress($body)) : $body;
                $context = "$responder / $label / $mode";
                check(count($createdFiles) === ($usedDisk ? 1 : 0), "$context storage");
                check(substr_count($xml, '<row>') === $rows, "$context row count");
                check(strpos($xml, '</RSRecordset>') !== false, "$context XML completion");
                check(in_array('Content-Length: ' . strlen($body), $responseHeaders), "$context length");
                $headers = implode('\n', $responseHeaders);
                check(strpos($headers, $compressed ? 'application/x-gzip' : 'text/xml') !== false, "$context type");
                check(count($removedFiles) === ($usedDisk ? ($compressed ? 2 : 1) : 0), "$context cleanup calls");
                check(count(glob($RStempPath . '/RSR*')) === 0, "$context remaining files");
                $count++;
            }
        }
    }
} finally {
    rmdir($RStempPath);
}
$itemSource = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
check(preg_match('/^function getFilteredItemsIDs\(.*?^\}/ms', $itemSource, $itemMatch) === 1,
    'Cannot extract item query helper');
eval('namespace ' . __NAMESPACE__ . ';' . $itemMatch[0]);
function IQ_getFilteredItemIDsOnly(...$arguments) { return new Result(1001); }
function RShydrateFilteredItemsProperties($rows, ...$arguments) { return $rows; }
function applyExternalFilters($itemTypeID, $clientID, $rows, $rules) { return $rows; }
function RSstreamFilteredItemsPropertiesToXML(...$arguments) {
    $GLOBALS['itemFileCalls']++;
    return 'stream-file';
}
function RSfilteredItemsArrayToXML(...$arguments) {
    $GLOBALS['itemFileCalls']++;
    return 'array-file';
}
foreach (array('', 'external-filter') as $externalRules) {
    $RSresponseFileFieldThreshold = 0;
    $itemFileCalls = 0;
    $rows = getFilteredItemsIDs(1, 1, array(), array(), '', false, '', '', 'AND', 0, true, $externalRules);
    check(is_array($rows) && count($rows) === 1001, 'Zero must keep large item queries in RAM');
    check($itemFileCalls === 0, 'Zero must bypass both item file paths');
    $count++;
    $RSresponseFileFieldThreshold = 2000;
    $rows = getFilteredItemsIDs(1, 1, array(), array(), '', false, '', '', 'AND', 0, true, $externalRules);
    check($rows === ($externalRules === '' ? 'stream-file' : 'array-file'), 'Positive setting preserves item file paths');
    check($itemFileCalls === 1, 'Positive setting calls item file helper');
    $count++;
}
echo "PASS: $count response cases (thresholds, XML, compression, fallback, cleanup, RAM-only item queries)\n";
