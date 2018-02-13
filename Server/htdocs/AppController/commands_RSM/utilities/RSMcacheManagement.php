<?php

/**
 * Save file in cache directory
 */
function saveFileCache($file_original, $path, $name, $extension) {
    
    $file = $path . "_" . base64_encode($name) . "." . $extension;

    // Check folder exists or create it otherwise
    $dirname = dirname($file);
    if (!is_dir($dirname)) {
        if (!mkdir($dirname, 0755, true)) {
            RSError("api_getFile: Could not create cache directory");
        }
    }

    $fh = fopen($file, "w");
    if ($fh) {
        fwrite($fh, $file_original);
        fclose($fh);
    } else {
        RSError("api_getFile: Could not create cache file");
    }

    return 0;
}

?>
