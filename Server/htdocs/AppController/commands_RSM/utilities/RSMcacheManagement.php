<?php

/* Save file in cache directory */
function saveFileCache($fileOriginal, $path, $name, $extension)
{

    $file = $path . "_" . rawurlencode(base64_encode($name)) . "." . $extension;

    // Check folder exists or create it otherwise
    $dirname = dirname($file);

    if (!is_dir($dirname) && !mkdir($dirname, 0755, true)) {
        RSerror("api_getFile: Could not create cache directory");
    }

    $fh = fopen($file, "w");
    if ($fh) {
        fwrite($fh, $fileOriginal);
        fclose($fh);
    } else {
        RSerror("api_getFile: Could not create cache file");
    }

    return 0;
}
