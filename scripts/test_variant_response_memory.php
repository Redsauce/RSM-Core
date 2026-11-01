<?php
// Run with: php -d memory_limit=32M scripts/test_variant_response_memory.php
namespace VariantResponseMemoryTest;

require __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSresponseTempFiles.php';
$source = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
foreach (array('getFilteredItemsIDs', 'RSstreamFilteredItemsPropertiesToXML') as $name) {
    if (!preg_match('/^function ' . $name . '\(.*?^\}/ms', $source, $match)) {
        throw new \RuntimeException('Cannot extract ' . $name);
    }
    eval('namespace ' . __NAMESPACE__ . ';' . $match[0]);
}

function check($condition, $message) {
    if (!$condition) throw new \RuntimeException($message);
}
function RSGetResponseFileFieldThreshold() { return 2000; }
function RSMgetFilteredPropertyType(...$arguments) { return 'variant'; }
class Result {
    public $num_rows = 1201;
    private $next = 1;
    function fetch_assoc() {
        return $this->next <= $this->num_rows ? array('ID' => $this->next++) : null;
    }
}
function IQ_getFilteredItemIDsOnly(...$arguments) { return new Result(); }
function RShydrateFilteredItemsProperties($items, ...$arguments) {
    check(count($items) <= 8, 'Variant archives must be hydrated in batches of at most eight');
    $GLOBALS['largestVariantBatch'] = max($GLOBALS['largestVariantBatch'], count($items));
    foreach ($items as &$item) {
        $item['151'] = 'request-' . $item['ID'] . ':' . str_repeat('q', 32768);
        $item['152'] = 'response-' . $item['ID'] . ':' . str_repeat('a', 32768);
    }
    return $items;
}
function RSCreateResponseTempFile($directory) {
    return $GLOBALS['failTemp'] ? false : \RSCreateResponseTempFile($directory);
}

$RStempPath = sys_get_temp_dir() . '/rsm-variant-memory-' . bin2hex(random_bytes(8));
mkdir($RStempPath);
$cstCDATAseparator = ']]]]><![CDATA[>';
$cstUTF8 = 'UTF-8';
$GLOBALS['largestVariantBatch'] = 0;
$GLOBALS['failTemp'] = false;
$properties = array(array('ID' => 151, 'name' => '151'), array('ID' => 152, 'name' => '152'));
$file = null;
try {
    $file = getFilteredItemsIDs(23, 17, array(), $properties, '', false, '', '', 'AND', 0, true);
    check(is_string($file) && is_file($file), 'Below-threshold variant history must stream to a file');
    check(filesize($file) > 75 * 1024 * 1024, 'Fixture must exceed the PHP process memory limit');
    $reader = new \XMLReader();
    check($reader->open($file), 'Response XML must open');
    $rows = 0;
    $columns = 0;
    while ($reader->read()) {
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'row') $rows++;
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'column') $columns++;
    }
    $reader->close();
    check($rows === 1201 && $columns === 3603, 'All history rows and fields must survive streaming');
    check($GLOBALS['largestVariantBatch'] === 8, 'Expected bounded batch size');
    check(memory_get_peak_usage(true) < 32 * 1024 * 1024, 'Response must remain below 32 MiB peak');
    $GLOBALS['failTemp'] = true;
    $failed = false;
    try {
        getFilteredItemsIDs(23, 17, array(), $properties, '', false, '', '', 'AND', 0, true);
    } catch (\RuntimeException $error) {
        $failed = $error->getMessage() === 'Unable to stream variant response';
    }
    check($failed, 'A file failure must not fall back to an unbounded archive read');
    echo 'PASS: 1201 rows, ' . filesize($file) . ' XML bytes, peak ' . memory_get_peak_usage(true) . " bytes; disk failure fails closed\n";
} finally {
    \RSReleaseResponseTempResources();
    if ($file && is_file($file)) \RSDeleteResponseTempFile($file);
    foreach (glob($RStempPath . '/*') as $remaining) unlink($remaining);
    rmdir($RStempPath);
}
