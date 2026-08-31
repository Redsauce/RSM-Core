<?php

// PHP 8.5.1 compatibility gate for RSM-Core.
// Runs local static checks by default and optional HTTP smoke checks via env vars.

class CompatibilitySuite
{
    private string $root;
    private array $failures = array();
    private array $skips = array();

    public function __construct(string $root)
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
    }

    public function run(array $argv): int
    {
        if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
            $this->printHelp();
            return 0;
        }

        $this->heading('RSM-Core PHP compatibility suite');
        $this->line('Repository: ' . $this->root);
        $this->line('PHP binary: ' . PHP_BINARY);
        $this->line('PHP version: ' . PHP_VERSION);
        $this->line('');

        $this->checkPhpVersion();
        $this->lintPhpFiles();
        $this->scanPhp85Compatibility();
        $this->runExistingRegressions();
        $this->runDatabaseDiffTests();
        $this->runHttpSmokeTests();

        $this->line('');
        foreach ($this->skips as $skip) {
            $this->line('SKIP ' . $skip);
        }

        if (count($this->failures) > 0) {
            $this->line('');
            $this->line('FAILED checks:');
            foreach ($this->failures as $failure) {
                $this->line('- ' . $failure);
            }
            return 1;
        }

        $this->line('');
        $this->line('All required compatibility checks passed.');
        return 0;
    }

    private function printHelp(): void
    {
        echo "RSM-Core PHP 8.5.1 compatibility suite\n\n";
        echo "Usage:\n";
        echo "  php scripts/php_compatibility_suite.php\n\n";
        echo "Required local checks:\n";
        echo "  - PHP version gate: requires PHP 8.5.x, minimum 8.5.1\n";
        echo "  - Recursive php -l linting for Server/ and scripts/\n";
        echo "  - Token-based PHP 8.5 compatibility scan\n";
        echo "  - Existing regression scripts: scripts/test_master_token_templates.php, scripts/test_dynamic_item_joins.php\n\n";
        echo "Optional MariaDB differential checks:\n";
        echo "  RSM_COMPAT_DB_DIFF=1\n";
        echo "      Creates and drops local database rsm_dynamic_join_diff.\n";
        echo "      Loads random deterministic item data and compares legacy HEAD vs current optimized responses.\n\n";
        echo "Optional HTTP smoke checks:\n";
        echo "  RSM_COMPAT_BASE_URL\n";
        echo "      Example: https://rsm-dev.redsauce.net/AppController/commands_RSM\n";
        echo "      Enables GET api/public/timezones.php smoke test.\n\n";
        echo "  RSM_COMPAT_HTTP_INVENTORY=1\n";
        echo "      Calls every PHP endpoint found under Server/htdocs/AppController/commands_RSM/api.\n";
        echo "      This catches remote 404/5xx responses and visible PHP parse/fatal/warning/deprecation output.\n\n";
        echo "  RSM_COMPAT_STAFF_CLIENT_ID\n";
        echo "  RSM_COMPAT_STAFF_LOGIN\n";
        echo "  RSM_COMPAT_STAFF_PASSWORD_MD5\n";
        echo "      Enables credentialed GET api/v2/staff/get.php smoke test.\n\n";
        echo "  RSM_COMPAT_EXPECT_STAFF_ID\n";
        echo "      Optional expected staff ID for the credentialed staff smoke test.\n";
    }

    private function checkPhpVersion(): void
    {
        $this->start('PHP version gate');
        if (PHP_VERSION_ID < 80501 || PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5) {
            $this->fail('PHP version gate', 'Expected PHP 8.5.x with minimum 8.5.1, got ' . PHP_VERSION);
            return;
        }
        $this->pass('PHP version gate');
    }

    private function lintPhpFiles(): void
    {
        $this->start('PHP syntax lint');
        $files = $this->phpFiles(array('Server', 'scripts'));
        $failed = array();

        foreach ($files as $file) {
            $result = $this->runProcess(array(PHP_BINARY, '-l', $file));
            if ($result['exitCode'] !== 0) {
                $failed[] = $this->relative($file) . "\n" . trim($result['stdout'] . $result['stderr']);
            }
        }

        if (count($failed) > 0) {
            $this->fail('PHP syntax lint', implode("\n", $failed));
            return;
        }

        $this->pass('PHP syntax lint', count($files) . ' files');
    }

    private function scanPhp85Compatibility(): void
    {
        $this->start('PHP 8.5 compatibility scan');
        $issues = array();
        $removedOrDeprecatedFunctions = array(
            'create_function' => 'removed legacy function',
            'each' => 'removed legacy function',
            'ereg' => 'removed legacy function',
            'eregi' => 'removed legacy function',
            'split' => 'removed legacy function',
            'spliti' => 'removed legacy function',
            'mysql_query' => 'removed mysql extension function',
            'mysql_connect' => 'removed mysql extension function',
            'mysql_select_db' => 'removed mysql extension function',
            'mysql_real_escape_string' => 'removed mysql extension function',
            'mcrypt_encrypt' => 'removed mcrypt extension function',
            'mcrypt_decrypt' => 'removed mcrypt extension function',
            'utf8_encode' => 'deprecated function',
            'utf8_decode' => 'deprecated function',
            'strftime' => 'deprecated function',
            'gmstrftime' => 'deprecated function',
            'curl_close' => 'deprecated function in PHP 8.5; unsetting the handle is sufficient',
        );

        foreach ($this->phpFiles(array('Server', 'scripts')) as $file) {
            $code = file_get_contents($file);
            if ($code === false) {
                $issues[] = $this->relative($file) . ': unable to read file';
                continue;
            }
            $tokens = token_get_all($code);
            $issues = array_merge($issues, $this->scanTokens($file, $tokens, $removedOrDeprecatedFunctions));
        }

        if (count($issues) > 0) {
            $this->fail('PHP 8.5 compatibility scan', implode("\n", $issues));
            return;
        }

        $this->pass('PHP 8.5 compatibility scan');
    }

    private function scanTokens(string $file, array $tokens, array $functionRules): array
    {
        $issues = array();
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_string($token)) {
                if ($token === '`') {
                    $issues[] = $this->relative($file) . ':' . $this->nearestLine($tokens, $i) . ': PHP backtick shell execution operator is deprecated in PHP 8.5';
                }
                continue;
            }

            $id = $token[0];
            $text = $token[1];
            $line = $token[2];
            $lower = strtolower($text);

            if (($id === T_INT_CAST || $id === T_BOOL_CAST || $id === T_DOUBLE_CAST || $id === T_STRING_CAST)
                && preg_match('/\((boolean|integer|double|binary)\)/i', $text)) {
                $issues[] = $this->relative($file) . ':' . $line . ': non-canonical cast ' . trim($text) . ' is deprecated in PHP 8.5';
            }

            if ($id === T_STRING && isset($functionRules[$lower]) && $this->nextMeaningfulTokenText($tokens, $i) === '(') {
                $issues[] = $this->relative($file) . ':' . $line . ': ' . $text . '() ' . $functionRules[$lower];
            }

            if ($id === T_FUNCTION) {
                $name = $this->nextMeaningfulToken($tokens, $i);
                if (is_array($name) && $name[0] === T_STRING && in_array(strtolower($name[1]), array('__sleep', '__wakeup'), true)) {
                    $issues[] = $this->relative($file) . ':' . $name[2] . ': magic method ' . $name[1] . '() is deprecated in PHP 8.5';
                }
            }

            if ($id === T_CASE && $this->caseUsesSemicolon($tokens, $i)) {
                $issues[] = $this->relative($file) . ':' . $line . ': case statement terminated with semicolon is deprecated in PHP 8.5';
            }

            if ($id === T_STRING && $lower === 'array_key_exists' && $this->arrayKeyExistsUsesNullKey($tokens, $i)) {
                $issues[] = $this->relative($file) . ':' . $line . ': array_key_exists(null, ...) is deprecated in PHP 8.5';
            }

            if ($id === T_VARIABLE && $this->variableUsesNullOffset($tokens, $i)) {
                $issues[] = $this->relative($file) . ':' . $line . ': using null as an array offset is deprecated in PHP 8.5';
            }
        }

        return $issues;
    }

    private function runExistingRegressions(): void
    {
        $this->start('Existing regression scripts');
        $scripts = array(
            $this->root . '/scripts/test_master_token_templates.php',
            $this->root . '/scripts/test_dynamic_item_joins.php',
        );

        $outputs = array();
        foreach ($scripts as $script) {
            if (!is_file($script)) {
                $this->fail('Existing regression scripts', 'Missing ' . $this->relative($script));
                return;
            }

            $result = $this->runProcess(array(PHP_BINARY, '-d', 'error_reporting=E_ALL', '-d', 'display_errors=1', $script));
            if ($result['exitCode'] !== 0) {
                $this->fail('Existing regression scripts', trim($result['stdout'] . $result['stderr']));
                return;
            }
            $outputs[] = trim($result['stdout']);
        }

        $this->pass('Existing regression scripts', implode('; ', array_filter($outputs)));
    }

    private function runDatabaseDiffTests(): void
    {
        // Prueba opcional: compara legacy y codigo actual con datos MariaDB reales.
        $enabled = getenv('RSM_COMPAT_DB_DIFF');
        if ($enabled === false || !in_array(strtolower((string) $enabled), array('1', 'true', 'yes'), true)) {
            $this->skips[] = 'Optional MariaDB differential tests: RSM_COMPAT_DB_DIFF is not enabled';
            return;
        }

        $this->start('Optional MariaDB differential tests');
        $script = $this->root . '/scripts/test_dynamic_item_joins_db.php';
        if (!is_file($script)) {
            $this->fail('Optional MariaDB differential tests', 'Missing ' . $this->relative($script));
            return;
        }

        $result = $this->runProcess(array(PHP_BINARY, '-d', 'error_reporting=E_ALL', '-d', 'display_errors=1', $script));
        if ($result['exitCode'] !== 0) {
            $this->fail('Optional MariaDB differential tests', trim($result['stdout'] . $result['stderr']));
            return;
        }

        $this->pass('Optional MariaDB differential tests', trim($result['stdout']));
    }

    private function runHttpSmokeTests(): void
    {
        $this->start('Optional HTTP smoke tests');
        $baseUrl = getenv('RSM_COMPAT_BASE_URL');
        if ($baseUrl === false || trim($baseUrl) === '') {
            $this->skips[] = 'Optional HTTP smoke tests: RSM_COMPAT_BASE_URL is not set';
            $this->pass('Optional HTTP smoke tests', 'skipped');
            return;
        }

        $baseUrl = rtrim(trim($baseUrl), '/');
        $this->httpTimezonesSmoke($baseUrl);
        $this->httpEndpointInventorySmoke($baseUrl);
        $this->httpStaffSmoke($baseUrl);
    }

    private function httpTimezonesSmoke(string $baseUrl): void
    {
        $url = $baseUrl . '/api/public/timezones.php';
        $response = $this->httpRequest('GET', $url, null, array());
        if (!$response['ok']) {
            $this->fail('HTTP smoke: public timezones', $response['error']);
            return;
        }
        if ($response['status'] !== 200) {
            $this->fail('HTTP smoke: public timezones', 'Expected HTTP 200, got ' . $response['status']);
            return;
        }
        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded) || !in_array('Europe/Madrid', $decoded, true)) {
            $this->fail('HTTP smoke: public timezones', 'Expected JSON timezone list containing Europe/Madrid');
            return;
        }
        $this->pass('HTTP smoke: public timezones');
    }


    private function httpEndpointInventorySmoke(string $baseUrl): void
    {
        $enabled = getenv('RSM_COMPAT_HTTP_INVENTORY');
        if ($enabled === false || !in_array(strtolower((string) $enabled), array('1', 'true', 'yes'), true)) {
            $this->skips[] = 'HTTP endpoint inventory smoke: RSM_COMPAT_HTTP_INVENTORY is not enabled';
            return;
        }

        $this->start('HTTP endpoint inventory smoke');
        $apiRoot = $this->root . '/Server/htdocs/AppController/commands_RSM/api';
        $endpoints = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apiRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($apiRoot) + 1));
            $endpoints[] = 'api/' . $relative;
        }
        sort($endpoints);

        $issues = array();
        foreach ($endpoints as $endpoint) {
            $url = $baseUrl . '/' . $endpoint;
            $response = $this->httpRequest('GET', $url, null, array());
            if (!$response['ok']) {
                $issues[] = $endpoint . ': request failed: ' . $response['error'];
                continue;
            }
            if ($response['status'] === 404) {
                $issues[] = $endpoint . ': HTTP 404, endpoint exists locally but not remotely';
                continue;
            }
            if ($response['status'] >= 500) {
                $issues[] = $endpoint . ': HTTP ' . $response['status'] . ' body=' . $this->shortBody($response['body']);
                continue;
            }
            $visibleError = $this->visiblePhpError($response['body']);
            if ($visibleError !== '') {
                $issues[] = $endpoint . ': visible PHP error output: ' . $visibleError;
            }
        }

        if (count($issues) > 0) {
            $this->fail('HTTP endpoint inventory smoke', implode("\n", $issues));
            return;
        }

        $this->pass('HTTP endpoint inventory smoke', count($endpoints) . ' endpoints');
    }

    private function httpStaffSmoke(string $baseUrl): void
    {
        $clientID = getenv('RSM_COMPAT_STAFF_CLIENT_ID');
        $login = getenv('RSM_COMPAT_STAFF_LOGIN');
        $password = getenv('RSM_COMPAT_STAFF_PASSWORD_MD5');
        if ($clientID === false || $login === false || $password === false || $clientID === '' || $login === '' || $password === '') {
            $this->skips[] = 'Credentialed staff HTTP smoke: staff env vars are not all set';
            return;
        }

        $url = $baseUrl . '/api/v2/staff/get.php';
        $body = json_encode(array(
            'clientID' => $clientID,
            'login' => $login,
            'password' => $password,
        ));
        $response = $this->httpRequest('GET', $url, $body, array('Content-Type: application/json'));
        if (!$response['ok']) {
            $this->fail('HTTP smoke: v2 staff get', $response['error']);
            return;
        }
        if ($response['status'] !== 200) {
            $this->fail('HTTP smoke: v2 staff get', 'Expected HTTP 200, got ' . $response['status'] . ' body=' . substr($response['body'], 0, 300));
            return;
        }
        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded) || !isset($decoded['ID']) || !is_numeric($decoded['ID'])) {
            $this->fail('HTTP smoke: v2 staff get', 'Expected JSON object with numeric ID');
            return;
        }
        $expected = getenv('RSM_COMPAT_EXPECT_STAFF_ID');
        if ($expected !== false && $expected !== '' && (string) $decoded['ID'] !== (string) $expected) {
            $this->fail('HTTP smoke: v2 staff get', 'Expected staff ID ' . $expected . ', got ' . $decoded['ID']);
            return;
        }
        $this->pass('HTTP smoke: v2 staff get');
    }

    private function phpFiles(array $dirs): array
    {
        $files = array();
        foreach ($dirs as $dir) {
            $path = $this->root . '/' . $dir;
            if (!is_dir($path)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
        sort($files);
        return $files;
    }

    private function runProcess(array $command): array
    {
        $descriptor = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($command, $descriptor, $pipes, $this->root);
        if (!is_resource($process)) {
            return array('exitCode' => 1, 'stdout' => '', 'stderr' => 'Unable to start process: ' . implode(' ', $command));
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        return array('exitCode' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr);
    }

    private function httpRequest(string $method, string $url, ?string $body, array $headers): array
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            if ($body !== null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            }
            if (count($headers) > 0) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }
            $responseBody = curl_exec($curl);
            $error = curl_error($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($responseBody === false) {
                return array('ok' => false, 'status' => 0, 'body' => '', 'error' => $error);
            }
            return array('ok' => true, 'status' => $status, 'body' => $responseBody, 'error' => '');
        }

        return array('ok' => false, 'status' => 0, 'body' => '', 'error' => 'curl extension is not available');
    }

    private function visiblePhpError(string $body): string
    {
        $patterns = array(
            'Fatal error',
            'Parse error',
            'Deprecated:',
            'Warning:',
            'Notice:',
            'Uncaught ',
        );
        foreach ($patterns as $pattern) {
            $position = stripos($body, $pattern);
            if ($position !== false) {
                return $this->shortBody(substr($body, $position));
            }
        }
        return '';
    }

    private function shortBody(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? $body);
        if (strlen($body) > 300) {
            return substr($body, 0, 300) . '...';
        }
        return $body;
    }

    private function nextMeaningfulToken(array $tokens, int $index)
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
                continue;
            }
            return $token;
        }
        return null;
    }

    private function nextMeaningfulTokenText(array $tokens, int $index): ?string
    {
        $token = $this->nextMeaningfulToken($tokens, $index);
        if ($token === null) {
            return null;
        }
        return is_array($token) ? $token[1] : $token;
    }

    private function caseUsesSemicolon(array $tokens, int $index): bool
    {
        $depth = 0;
        for ($i = $index + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $text = is_array($token) ? $token[1] : $token;
            if ($text === '(' || $text === '[') {
                $depth++;
            } elseif (($text === ')' || $text === ']') && $depth > 0) {
                $depth--;
            } elseif ($depth === 0 && $text === ':') {
                return false;
            } elseif ($depth === 0 && $text === ';') {
                return true;
            }
        }
        return false;
    }

    private function arrayKeyExistsUsesNullKey(array $tokens, int $index): bool
    {
        $open = $this->nextMeaningfulTokenText($tokens, $index);
        if ($open !== '(') {
            return false;
        }
        $first = $this->nextMeaningfulToken($tokens, $index + 1);
        return is_array($first) && strtolower($first[1]) === 'null';
    }

    private function variableUsesNullOffset(array $tokens, int $index): bool
    {
        $open = $this->nextMeaningfulTokenText($tokens, $index);
        if ($open !== '[') {
            return false;
        }
        $inner = $this->nextMeaningfulToken($tokens, $index + 1);
        if (!(is_array($inner) && strtolower($inner[1]) === 'null')) {
            return false;
        }
        $close = $this->nextMeaningfulTokenText($tokens, $index + 2);
        return $close === ']';
    }

    private function nearestLine(array $tokens, int $index): int
    {
        for ($i = $index; $i >= 0; $i--) {
            if (is_array($tokens[$i])) {
                return $tokens[$i][2];
            }
        }
        return 1;
    }

    private function heading(string $text): void
    {
        $this->line($text);
        $this->line(str_repeat('=', strlen($text)));
    }

    private function start(string $name): void
    {
        $this->line('RUN  ' . $name);
    }

    private function pass(string $name, string $detail = ''): void
    {
        $this->line('PASS ' . $name . ($detail !== '' ? ' (' . $detail . ')' : ''));
    }

    private function fail(string $name, string $detail): void
    {
        $this->line('FAIL ' . $name);
        $this->failures[] = $name . ': ' . $detail;
    }

    private function line(string $text): void
    {
        echo $text . PHP_EOL;
    }

    private function relative(string $path): string
    {
        $prefix = $this->root . DIRECTORY_SEPARATOR;
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }
        return $path;
    }
}

$root = dirname(__DIR__);
$suite = new CompatibilitySuite($root);
exit($suite->run($argv));
