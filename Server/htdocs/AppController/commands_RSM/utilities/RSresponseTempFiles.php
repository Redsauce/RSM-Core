<?php
// Track only files owned by this request. Never delete another active response.
function &RSResponseTempFileState() {
    static $state = null;
    if ($state === null) {
        $state = array('files' => array(), 'reserve' => str_repeat(' ', 65536));
        register_shutdown_function('RSCleanupResponseTempFiles');
    }
    return $state;
}

function RSNormalizeResponseTempFile($filename) {
    if (strpos($filename, '://') === false) {
        $directory = realpath(dirname($filename));
        if ($directory !== false) $filename = $directory . DIRECTORY_SEPARATOR . basename($filename);
    }
    return $filename;
}

function RSRegisterResponseTempFile($filename, $handle = null) {
    $filename = RSNormalizeResponseTempFile($filename);
    $state =& RSResponseTempFileState();
    $state['files'][$filename] = $handle;
}

function RSCreateResponseTempFile($directory) {
    // Register cleanup before creating the first file.
    RSResponseTempFileState();
    $filename = @tempnam($directory, 'RSR');
    if ($filename !== false) {
        RSRegisterResponseTempFile($filename);
    }
    return $filename;
}

function RSDeleteResponseTempFile($filename) {
    $filename = RSNormalizeResponseTempFile($filename);
    $state =& RSResponseTempFileState();
    if (isset($state['files'][$filename]) && is_resource($state['files'][$filename])) {
        @fclose($state['files'][$filename]);
        $state['files'][$filename] = null;
    } elseif (isset($state['files'][$filename]) && is_callable($state['files'][$filename])) {
        $state['files'][$filename]();
        $state['files'][$filename] = null;
    }
    // One attempt only: shutdown must not retry a failed explicit deletion.
    unset($state['files'][$filename]);
    clearstatcache(true, $filename);
    if ((!file_exists($filename) && !is_link($filename)) || @unlink($filename)) {
        return true;
    }
    error_log('RSM temporary response cleanup failed: ' . $filename);
    return false;
}

function RSCleanupResponseTempFiles() {
    $state =& RSResponseTempFileState();
    // Make a little memory available even after a memory-limit fatal error.
    $state['reserve'] = null;
    foreach ($state['files'] as $filename => $handle) {
        RSDeleteResponseTempFile($filename);
    }
}
