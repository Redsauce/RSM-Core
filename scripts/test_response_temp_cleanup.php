<?php
require __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSresponseTempFiles.php';
$source = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSdatabase.php');
preg_match('/^function gzCompressFile\(.*?^\}/ms', $source, $match);
eval($match[0]);

function verify($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}

function RShydrateFilteredItemsProperties($rows, ...$arguments) { return $rows; }
class FatalItemValue {
    public function __toString() {
        trigger_error('Injected failure while writing item XML', E_USER_ERROR);
        return '';
    }
}
class CleanupItemResult {
    private $sent = false;
    public function fetch_assoc() {
        if ($this->sent) return null;
        $this->sent = true;
        return array('value' => new FatalItemValue());
    }
}

if (isset($argv[1]) && $argv[1] === '--child') {
    if (strpos($argv[3], 'item-') === 0) {
        $RStempPath = $argv[2];
        $cstCDATAseparator = ']]]]><![CDATA[>';
        $cstUTF8 = 'UTF-8';
        $itemSource = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
        foreach (array('RSstreamFilteredItemsPropertiesToXML', 'RSfilteredItemsArrayToXML') as $name) {
            verify(preg_match('/^function ' . $name . '\(.*?^\}/ms', $itemSource, $itemMatch) === 1,
                'Cannot extract ' . $name);
            eval($itemMatch[0]);
        }
        if ($argv[3] === 'item-stream-fatal') {
            RSstreamFilteredItemsPropertiesToXML(new CleanupItemResult(), 1, 1, array());
        } else {
            RSfilteredItemsArrayToXML(array(array('value' => new FatalItemValue())), 1, 1);
        }
        exit(2); // Must fail during serialization, not silently return.
    }
    $file = RSCreateResponseTempFile($argv[2]);
    $handle = fopen($file, 'wb');
    RSRegisterResponseTempFile($file, $handle);
    fwrite($handle, str_repeat('x', 1024 * 1024));
    switch ($argv[3]) {
        case 'exit-during-send':
            // Model PHP aborting output before control reaches normal cleanup.
            echo 'sending';
            exit;
        case 'fatal': trigger_error('Injected fatal during serialization', E_USER_ERROR); break;
        case 'exception': throw new RuntimeException('Injected uncaught exception');
        case 'memory':
            ini_set('memory_limit', '16M');
            $allocation = str_repeat('x', 32 * 1024 * 1024);
            break;
        case 'timeout':
            set_time_limit(1);
            while (true) { hash('sha256', 'busy'); }
        case 'changed-directory': chdir(dirname($argv[2])); exit;
    }
    exit;
}

$directory = sys_get_temp_dir() . '/rsm-cleanup-' . bin2hex(random_bytes(8));
mkdir($directory);
$count = 0;
try {
    foreach (array('exit-during-send', 'fatal', 'exception', 'memory', 'timeout', 'changed-directory',
        'item-stream-fatal', 'item-array-fatal') as $mode) {
        $process = proc_open(array(PHP_BINARY, '-d', 'display_errors=0', '-d', 'log_errors=0',
            __FILE__, '--child', $directory, $mode), array(0 => array('pipe', 'r'),
            1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
        verify(is_resource($process), 'Cannot start child');
        fclose($pipes[0]);
        stream_get_contents($pipes[1]); fclose($pipes[1]);
        stream_get_contents($pipes[2]); fclose($pipes[2]);
        $status = proc_close($process);
        verify(in_array($mode, array('exit-during-send', 'changed-directory')) ? $status === 0 : $status !== 0,
            "$mode did not trigger the expected termination");
        if (strpos($mode, 'item-') === 0) verify($status !== 2, "$mode did not reach injected fatal");
        verify(count(glob($directory . '/RSR*')) === 0, "$mode left a temporary file");
        $count++;
    }

    // Destination is created but opening the source fails: no .gz may survive.
    $missing = $directory . '/missing';
    verify(gzCompressFile($missing) === false, 'Missing source must fail');
    verify(!file_exists($missing . '.gz'), 'Failed gzip destination was not deleted');
    $count++;

    // Inject read and write failures into the actual compressor via a namespaced copy.
    eval('namespace CompressionFailureTest;' . $match[0]);
    eval('namespace CompressionFailureTest;
        function fread($handle, $length) { return $GLOBALS["failRead"] ? false : \\fread($handle, $length); }
        function gzwrite($handle, $data) { return false; }');
    foreach (array(true, false) as $failRead) {
        $file = RSCreateResponseTempFile($directory);
        file_put_contents($file, 'source');
        verify(CompressionFailureTest\gzCompressFile($file) === false, 'Injected compression failure');
        verify(!file_exists($file . '.gz'), 'Partial gzip remained');
        RSDeleteResponseTempFile($file);
        $count++;
    }

    // A stream wrapper makes unlink failures deterministic, including on Windows/admin accounts.
    class FailedDeleteStream {
        public $context;
        public static $failures = 0;
        public static $attempts = 0;
        public static $exists = true;
        public function url_stat($path, $flags) { return self::$exists ? array('mode' => 0100600) : false; }
        public function unlink($path) {
            self::$attempts++;
            if (self::$failures-- > 0) return false;
            self::$exists = false;
            return true;
        }
    }
    stream_wrapper_register('rsmdelete', 'FailedDeleteStream');
    $virtual = 'rsmdelete://response';
    RSRegisterResponseTempFile($virtual);
    FailedDeleteStream::$failures = 0;
    verify(RSDeleteResponseTempFile($virtual), 'Successful deletion');
    verify(FailedDeleteStream::$attempts === 1, 'Successful deletion must attempt once');
    $count++;
    FailedDeleteStream::$exists = true;
    FailedDeleteStream::$failures = 3;
    FailedDeleteStream::$attempts = 0;
    RSRegisterResponseTempFile($virtual);
    $previousLog = ini_get('error_log');
    ini_set('error_log', $directory . '/error.log');
    verify(!RSDeleteResponseTempFile($virtual), 'Persistent failure must return false');
    verify(strpos(file_get_contents($directory . '/error.log'), $virtual) !== false, 'Failure must be logged');
    RSCleanupResponseTempFiles();
    verify(FailedDeleteStream::$exists, 'Failed deletion must not be retried at shutdown');
    verify(FailedDeleteStream::$attempts === 1, 'Failed deletion must attempt once only');
    ini_set('error_log', $previousLog);
    unlink($directory . '/error.log');
    $count++;
    stream_wrapper_unregister('rsmdelete');

} finally {
    RSCleanupResponseTempFiles();
    if (file_exists($directory . '/normal-completion')) unlink($directory . '/normal-completion');
    rmdir($directory);
}
echo "PASS: $count cleanup cases (shutdown, fatal, timeout, memory, gzip failures, single deletion/logging)\n";
