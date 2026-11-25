<?php
// Exercise the changed pure helpers without loading the database bootstrap.
namespace SonarMaintenanceTest;

require_once __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMqueryText.php';

function extractFunction($source, $name)
{
    $start = strpos($source, 'function ' . $name . '(');
    if ($start === false) {
        throw new \LogicException('Missing function: ' . $name);
    }
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
    return $function;
}

function check($actual, $expected, $message)
{
    if ($actual !== $expected) {
        throw new \RuntimeException($message);
    }
    $GLOBALS['maintenanceChecks']++;
}

$GLOBALS['maintenanceChecks'] = 0;
$utilities = __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/';
$source = file_get_contents($utilities . 'RStools.php');
foreach (array('checkType', 'RSMarePositiveIdentifiers', 'isValidSqlDate', 'isValidSqlTime',
    'isValidSqlDatetime', 'isTimeBefore', 'isTimeAfter', 'RSMcompareTimeComponents') as $name) {
    eval('namespace ' . __NAMESPACE__ . ';' . extractFunction($source, $name));
}

foreach (array(
    array('text', 'hello', 'hello'), array('longtext', '', ''),
    array('image', 'f:00ff', 'f:00ff'), array('file', 'f:00ff', 'f:00ff'),
    array('variant', 'raw:history', 'raw:history'), array('unknown', 'x', null),
    array('integer', '12.5', 12), array('identifier', '7', 7), array('float', '1.25', 1.25),
    array('identifiers', '1,02,3', '1,02,3'), array('identifiers', '1,0', 0),
    array('identifiers', '', 0), array('identifiers', '1,invalid', 0),
    array('date', '2024-02-29', '2024-02-29'), array('date', '2025-02-29', '0000-00-00'),
    array('datetime', '2024-02-29 12:34:56', '2024-02-29 12:34:56'),
    array('datetime', '2024-02-29 25:34:56', '0000-00-00 00:00:00'),
) as $case) {
    check(checkType($case[1], $case[0]), $case[2], 'Property conversion changed: ' . $case[0]);
}

foreach (array(
    array('01:02:03', '1:2:3', false, false),
    array('01:59:59', '02:00:00', true, false),
    array('02:00:00', '01:59:59', false, true),
    array('12:01:59', '12:02:00', true, false),
    array('12:02:01', '12:02:00', false, true),
    array('100:90:60', '100:90:61', true, false),
) as $case) {
    check(isTimeBefore($case[0], $case[1]), $case[2], 'Time ordering changed');
    check(isTimeAfter($case[0], $case[1]), $case[3], 'Reverse time ordering changed');
}

// The six group writers must retain their validation and OK/NOK wire results.
function RSquery($query)
{
    $GLOBALS['maintenanceQueries'][] = $query;
    if (strpos($query, 'SELECT ') === 0) {
        return new class {
            public function fetch_array() { return $GLOBALS['maintenanceMembership']; }
        };
    }
    return $GLOBALS['maintenanceWriteSuccess'];
}

$source = file_get_contents($utilities . 'RSMusersManagement.php');
foreach (array('removeUserFromGroup', 'addUserToGroup', 'addPropertyToGroup',
    'removePropertyFromGroup', 'addActionToGroup', 'removeActionFromGroup') as $name) {
    eval('namespace ' . __NAMESPACE__ . ';' . extractFunction($source, $name));
    $call = __NAMESPACE__ . '\\' . $name;
    $remove = strpos($name, 'remove') === 0;
    foreach (array(false, true) as $write) {
        $GLOBALS['maintenanceQueries'] = array();
        $GLOBALS['maintenanceMembership'] = $remove ? array(1) : 0;
        $GLOBALS['maintenanceWriteSuccess'] = $write;
        check($call(1, 2, 3), $write ? 'OK' : 'NOK', $name . ': write result changed');
        check(count($GLOBALS['maintenanceQueries']), 2, $name . ': query count changed');
    }
    $GLOBALS['maintenanceQueries'] = array();
    $GLOBALS['maintenanceMembership'] = $remove ? 0 : array(1);
    check($call(1, 2, 3), 'OK', $name . ': idempotent operation changed');
    check(count($GLOBALS['maintenanceQueries']), 1, $name . ': unnecessary write');
    $GLOBALS['maintenanceQueries'] = array();
    check($call(0, 2, 3), 'NOK', $name . ': invalid ID accepted');
    check(count($GLOBALS['maintenanceQueries']), 0, $name . ': invalid ID reached SQL');
}

$source = file_get_contents($utilities . 'RSMeventsManagement.php');
eval('namespace ' . __NAMESPACE__ . ';' . extractFunction($source, 'RSMmergeTriggerActions'));
$ids = array();
$actions = array();
RSMmergeTriggerActions(array('10', '20'), 5, 'Yes', $ids, $actions);
RSMmergeTriggerActions(array('20', '30'), 2, 'No', $ids, $actions);
RSMmergeTriggerActions(array('10', '20'), 9, 'Yes', $ids, $actions);
check($ids, array('10', '20', '30'), 'Trigger order or deduplication changed');
check($actions, array(
    array('ID' => '10', 'priority' => 5, 'avoidDuplication' => 'Yes'),
    array('ID' => '20', 'priority' => 2, 'avoidDuplication' => 'No'),
    array('ID' => '30', 'priority' => 2, 'avoidDuplication' => 'No'),
), 'Duplicate triggers must keep the minimum priority and permissive duplication flag');

echo 'PASS: ' . $GLOBALS['maintenanceChecks'] . " property, time, trigger and group-write checks\n";
