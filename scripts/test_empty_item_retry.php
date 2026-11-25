<?php
require_once __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMqueryText.php';
require_once __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMexceptions.php';
// Run the production allocator against deterministic query failures, without a DB.
$source = file_get_contents(dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
$start = strpos($source, 'function RScreateEmptyItemInTransaction(');
$function = '';
$depth = 0;
$started = false;
foreach (token_get_all('<?php ' . substr($source, $start)) as $token) {
    if (is_array($token) && $token[0] === T_OPEN_TAG) {
        continue;
    }
    $function .= is_array($token) ? $token[1] : $token;
    if ($token === '{') {
        $depth++;
        $started = true;
    } elseif ($token === '}') {
        $depth--;
    }
    if ($started && $depth === 0) {
        break;
    }
}
eval($function);
$helperStart = strpos($source, 'function RSretryEmptyItemInsert(');
$helperEnd = strpos($source, 'function RScreateEmptyItemInTransaction(', $helperStart);
eval(substr($source, $helperStart, $helperEnd - $helperStart));
function getNextItemTypeIdentification($type, $client) { return 10; }
function getNextIdentification($table, $column, $client, $filters) { return 11; }
function RSquery($query, $reportErrors = true)
{
    global $steps, $queries, $mysqli;
    $queries[] = array($query, $reportErrors);
    if (empty($steps)) {
        throw new RuntimeException('Unexpected extra query');
    }
    $step = array_shift($steps);
    $mysqli->errno = $step[1] ?? 0;
    return $step[0];
}
function latestItem($id)
{
    return new class ($id) {
        private $id;
        public function __construct($id) { $this->id = $id; }
        public function fetch_assoc() { return $this->id === null ? null : array('RS_ITEM_ID' => $this->id); }
    };
}
$cases = array(
    array(false, array(array(true), array(true)), 10, null),
    array(false, array(array(false, 1062), array(true), array(true)), 11, null),
    array(true, array(array(true), array(true)), 10, null),
    array(true, array(array(false, 1062), array(latestItem(10)), array(true), array(true)), 11, null),
    array(true, array(array(false, 1213)), null, 'insert_rs_items'),
    array(true, array(array(false, 1062), array(false)), null, 'read_latest_item_id'),
    array(true, array(array(false, 1062), array(latestItem(9))), null, 'insert_rs_items'),
    array(true, array(array(false, 1062), array(latestItem(10)), array(false)), null, 'retry_insert_rs_items'),
    array(true, array(array(true), array(false)), null, 'update_item_counter'),
    array(true, array(array(false, 1062), array(latestItem(null)), array(true), array(true)), 1, null),
);
foreach ($cases as $index => $case) {
    list($strict, $steps, $expectedID, $expectedFailure) = $case;
    $queries = array();
    $mysqli = (object) array('errno' => 0, 'sqlstate' => 'HY000', 'error' => 'simulated failure');
    $actualID = null;
    $failure = null;
    try {
        $actualID = RScreateEmptyItemInTransaction(50, 7, $strict);
    } catch (RuntimeException $exception) {
        $failure = $exception->getMessage();
    }
    if ($steps || $actualID !== $expectedID || ($expectedFailure === null ? $failure !== null : strpos($failure ?? '', 'operation=' . $expectedFailure . ';') !== 0)) {
        throw new RuntimeException('Allocator regression in case ' . $index);
    }
    if ($expectedID !== null && end($queries)[1] !== !$strict) {
        throw new RuntimeException('Counter error reporting changed');
    }
}
echo 'PASS: ' . count($cases) . " allocator success, collision and failure cases\n";
