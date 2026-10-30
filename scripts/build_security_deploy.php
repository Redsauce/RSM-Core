<?php
// Package the current reviewed source. This script never connects to PROD.
$repo = dirname(__DIR__);
$sourceRoot = $repo . '/Server/htdocs/AppController/commands_RSM/';
$plan = json_decode(file_get_contents($repo . '/docs/security/sec-02-deploy-plan.json'), true, 512, JSON_THROW_ON_ERROR);
$output = $argv[1] ?? (sys_get_temp_dir() . '/rsm-sec02-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)));
if (file_exists($output)) throw new RuntimeException('Use a new output directory; existing packages are never overwritten.');
// Resolve every input and validate all PHP before creating any package.
$seenStages = array(); $seenFiles = array();
foreach ($plan['stages'] as $stage) {
    foreach ($stage['requires'] as $dependency) {
        if (!isset($seenStages[$dependency])) throw new RuntimeException('Invalid stage dependency: ' . $dependency);
    }
    foreach ($stage['files'] as $file) {
        if (!preg_match('~\A[a-zA-Z0-9_/]+\.php\z~', $file) || isset($seenFiles[$file])) throw new RuntimeException('Invalid/duplicate source: ' . $file);
        if (!is_file($sourceRoot . $file)) throw new RuntimeException('Missing source: ' . $file);
        $process = proc_open(array(PHP_BINARY, '-l', $sourceRoot . $file),
            array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
        if (!is_resource($process)) throw new RuntimeException('Unable to lint: ' . $file);
        $lint = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if (proc_close($process) !== 0) throw new RuntimeException($lint);
        $seenFiles[$file] = true;
    }
    $seenStages[$stage['id']] = true;
}
if (!mkdir($output, 0777, true)) throw new RuntimeException('Unable to create package directory');
foreach ($plan['stages'] as &$stage) {
    $stage['sha256'] = array();
    foreach ($stage['files'] as $file) {
        $target = $output . '/' . $stage['id'] . '/' . $plan['destination'] . '/' . $file;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0777, true)) throw new RuntimeException('Unable to create target directory');
        if (!copy($sourceRoot . $file, $target)) throw new RuntimeException('Unable to copy: ' . $file);
        $stage['sha256'][$file] = hash_file('sha256', $target);
        if ($stage['sha256'][$file] !== hash_file('sha256', $sourceRoot . $file)) throw new RuntimeException('Copy verification failed: ' . $file);
    }
}
unset($stage);
file_put_contents($output . '/manifest.json', json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
if (!copy($repo . '/docs/security/sec-02-binary-property-writes.md', $output . '/DEPLOY.md')) throw new RuntimeException('Unable to copy deployment guide');
echo 'PASS: lint and SHA-256 verified for ' . count($seenFiles) . " files in ordered stages\n";
echo $output . "\n";
