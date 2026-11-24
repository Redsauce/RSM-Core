<?php
// Exercise the production guard without loading configuration or a database.
$source = file_get_contents(dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/utilities/RSdatabase.php');
$guard = substr($source, 5, strpos($source, 'require_once') - 5);
$cases = array(
    array('1K', 1024, 200), array('1K', 1025, 413),
    array('1k', 1025, 413), array('2M', 2097152, 200),
    array('2M', 2097153, 413), array('2m', 2097153, 413),
    array('1G', 1073741824, 200), array('1G', 1073741825, 413),
    array('1g', 1073741825, 413), array('512', 512, 200),
    array('512', 513, 413), array('0', 2147483648, 200),
    array('-1', 2147483648, 200), array('8M', 0, 200),
);
foreach ($cases as $case) {
    list($limit, $length, $expected) = $case;
    foreach (array('POST', 'GET') as $method) {
        $expectedStatus = $method === 'POST' ? $expected : 200;
        $code = 'http_response_code(200); $_SERVER = ' . var_export(array('REQUEST_METHOD' => $method, 'CONTENT_LENGTH' => $length), true) . ';'
            . 'register_shutdown_function(function () { echo " STATUS=" . http_response_code(); });'
            . $guard . 'echo "accepted";';
        $process = proc_open(array(PHP_BINARY, '-d', 'post_max_size=' . $limit, '-r', $code), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start guard test');
        }
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $expectedOutput = ($expectedStatus === 413 ? '413 Payload Too Large' : 'accepted') . ' STATUS=' . $expectedStatus;
        if ($exit !== 0 || $errors !== '' || $output !== $expectedOutput) {
            throw new RuntimeException($method . ' limit=' . $limit . ' length=' . $length . ': ' . $output . $errors);
        }
    }
}
echo 'PASS: ' . count($cases) * 2 . " POST limit and non-POST cases\n";
