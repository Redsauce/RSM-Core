<?php

// Focused tests for next-integer sequence calculation and locking. The real
// item-manager functions are exercised against a small mysqli statement double.

function nextIntegerAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

class NextIntegerFakeStatement
{
    private $query;
    private $parameters = array();
    private $scalar;
    private $boundResult;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function bind_param($types, &...$parameters)
    {
        $this->parameters = &$parameters;
        return true;
    }

    public function execute()
    {
        global $nextIntegerScalarQueue, $nextIntegerLocks, $nextIntegerExecutions;

        $parameters = array_values($this->parameters);
        $nextIntegerExecutions[] = array('query' => $this->query, 'parameters' => $parameters);

        if (strpos($this->query, 'GET_LOCK') !== false) {
            $lockName = (string)$parameters[0];
            if (isset($nextIntegerLocks[$lockName])) {
                $this->scalar = 0;
            } else {
                $nextIntegerLocks[$lockName] = true;
                $this->scalar = 1;
            }
        } elseif (strpos($this->query, 'RELEASE_LOCK') !== false) {
            $lockName = (string)$parameters[0];
            $this->scalar = isset($nextIntegerLocks[$lockName]) ? 1 : 0;
            unset($nextIntegerLocks[$lockName]);
        } else {
            $this->scalar = array_shift($nextIntegerScalarQueue);
        }

        return true;
    }

    public function bind_result(&$result)
    {
        $this->boundResult = &$result;
        return true;
    }

    public function fetch()
    {
        $this->boundResult = $this->scalar;
        return true;
    }

    public function close()
    {
        return true;
    }
}

class NextIntegerFakeMysqli
{
    public $commits = 0;
    public $rollbacks = 0;
    private $snapshot;
    public function begin_transaction() { $this->snapshot = $GLOBALS['allocationValues']; return true; }
    public function commit() { $this->commits++; return true; }
    public function rollback() { $GLOBALS['allocationValues'] = $this->snapshot; $this->rollbacks++; return true; }

    public function prepare($query)
    {
        return new NextIntegerFakeStatement($query);
    }
}

$mysqli = new NextIntegerFakeMysqli();
$propertiesTables = array(
    'text' => 'rs_property_text',
    'date' => 'rs_property_dates',
    'datetime' => 'rs_property_datetime',
    'integer' => 'rs_property_integers',
    'identifier' => 'rs_property_identifiers',
    'identifiers' => 'rs_property_multiIdentifiers',
);
$nextIntegerScalarQueue = array();
$nextIntegerLocks = array();
$nextIntegerExecutions = array();

$endpointPath = __DIR__ . '/../Server/htdocs/AppController/commands_RSM/api/v2/items/nextInteger.php';
$endpointSource = file_get_contents($endpointPath);
nextIntegerAssert($endpointSource !== false, 'nextInteger endpoint must be readable');
$itemsManagementPath = __DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php';
$itemsManagementSource = file_get_contents($itemsManagementPath);
nextIntegerAssert($itemsManagementSource !== false, 'RSMitemsManagement must be readable');

function extractNextIntegerFunction($source, $functionName)
{
    $start = strpos($source, 'function ' . $functionName . '(');
    nextIntegerAssert($start !== false, $functionName . ' must exist in RSMitemsManagement');
    $brace = strpos($source, '{', $start);
    nextIntegerAssert($brace !== false, $functionName . ' must have a body');
    $depth = 0;
    for ($index = $brace, $length = strlen($source); $index < $length; $index++) {
        if ($source[$index] === '{') $depth++;
        if ($source[$index] === '}') $depth--;
        if ($depth === 0) return substr($source, $start, $index - $start + 1);
    }
    nextIntegerAssert(false, $functionName . ' body must close');
}

foreach (array(
    'RSnextIntegerExecuteScalar',
    'RSgetNextIntegerPropertyValue',
    'RSgetNextIntegerLockName',
    'RSacquireNextIntegerLock',
    'RSreleaseNextIntegerLock',
    'RSallocateNextIntegerPropertyValue',
) as $functionName) {
    eval(extractNextIntegerFunction($itemsManagementSource, $functionName));
}

$nextIntegerScalarQueue[] = 5;
$next = RSgetNextIntegerPropertyValue(7, 8, 100);
nextIntegerAssert($next === 6, 'unscoped sequence must return the last item number plus one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert(strpos($execution['query'], 'ORDER BY targetValue.RS_ITEM_ID DESC LIMIT 1') !== false, 'calculation must use the last numbered item');
nextIntegerAssert(strpos($execution['query'], 'MAX(targetValue.RS_DATA)') === false, 'calculation must not use the largest number');
nextIntegerAssert(strpos($execution['query'], 'yearValue') === false && strpos($execution['query'], 'seriesValue') === false, 'unscoped query must not add scope joins');
nextIntegerAssert(count($execution['parameters']) === 0, 'unscoped query must not bind scope values');

$nextIntegerScalarQueue[] = 12;
$next = RSgetNextIntegerPropertyValue(7, 8, 100, array('propertyID' => 101, 'type' => 'date', 'year' => 2026));
nextIntegerAssert($next === 13, 'year-scoped sequence must return the last matching item number plus one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert(strpos($execution['query'], 'rs_property_dates yearValue') !== false, 'year scope must join the date property table');
nextIntegerAssert($execution['parameters'] === array('2026-01-01', '2027-01-01'), 'year scope must use inclusive/exclusive year boundaries');

$nextIntegerScalarQueue[] = 7;
$next = RSgetNextIntegerPropertyValue(7, 8, 100, null, array('propertyID' => 102, 'type' => 'text', 'value' => 'A'));
nextIntegerAssert($next === 8, 'series-scoped sequence must return the last matching item number plus one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert(strpos($execution['query'], 'rs_property_text seriesValue') !== false, 'series scope must join its property table');
nextIntegerAssert($execution['parameters'] === array('A'), 'series scope must bind exact series value');

$nextIntegerScalarQueue[] = 25;
$next = RSgetNextIntegerPropertyValue(
    7,
    8,
    100,
    array('propertyID' => 101, 'type' => 'datetime', 'year' => 2026),
    array('propertyID' => 102, 'type' => 'text', 'value' => 'B')
);
nextIntegerAssert($next === 26, 'combined scope must return the last matching item number plus one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert(strpos($execution['query'], 'yearValue') !== false && strpos($execution['query'], 'seriesValue') !== false, 'combined query must include both joins');
nextIntegerAssert($execution['parameters'] === array('2026-01-01', '2027-01-01', 'B'), 'combined query must bind both scopes in order');

$nextIntegerScalarQueue[] = 0;
nextIntegerAssert(RSgetNextIntegerPropertyValue(7, 8, 100) === 1, 'empty sequence must start at one');
nextIntegerAssert(RSgetNextIntegerPropertyValue(7, 8, 100, array('propertyID' => 101, 'type' => 'text', 'year' => 2026)) === false, 'year scope must reject a non-date property type');
nextIntegerAssert(RSgetNextIntegerPropertyValue(7, 8, 100, null, array('propertyID' => 102, 'type' => 'file', 'value' => 'A')) === false, 'series scope must reject unsupported property types');

$nextIntegerScalarQueue[] = 3;
$next = RSgetNextIntegerPropertyValue(7, 8, 100, null, null, array('propertyID' => 103, 'type' => 'identifier', 'itemID' => 99));
nextIntegerAssert($next === 4, 'customer-scoped sequence must return the last item number plus one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert(strpos($execution['query'], 'rs_property_identifiers customerValue') !== false, 'customer scope must join its dependency property');
nextIntegerAssert($execution['parameters'] === array('99'), 'customer scope must bind the token customer item');

$setScope = array('propertyID' => 103, 'type' => 'identifier', 'values' => array(9, 2, 9));
$nextIntegerScalarQueue[] = 18;
nextIntegerAssert(RSgetNextIntegerPropertyValue(7, 8, 100, null, $setScope) === 19, 'set scope must return the last item number plus one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert($execution['parameters'] === array('9', '2', '9'), 'set values must be bound');
nextIntegerAssert(strpos($execution['query'], 'IN (?,?,?)') !== false, 'set scope must restrict related items');
nextIntegerAssert(RSgetNextIntegerLockName(7, 100, null, $setScope) === RSgetNextIntegerLockName(7, 100, null, array('propertyID' => 103, 'values' => array(2, 9))), 'set locks must ignore order and duplicate values');
nextIntegerAssert(RSgetNextIntegerLockName(7, 100, null, array('propertyID' => 103, 'values' => array(9))) === RSgetNextIntegerLockName(7, 100, null, array('propertyID' => 103, 'value' => '9')), 'single-value sets must share equality locks');
$nextIntegerScalarQueue[] = 0;
nextIntegerAssert(RSgetNextIntegerPropertyValue(7, 8, 100, null, array('propertyID' => 103, 'type' => 'identifier', 'values' => array())) === 1, 'empty set must start at one');
$execution = $nextIntegerExecutions[count($nextIntegerExecutions) - 1];
nextIntegerAssert(strpos($execution['query'], '1 = 0') !== false, 'empty set must not become unscoped');

$globalLock = RSgetNextIntegerLockName(7, 100);
$sameGlobalLock = RSgetNextIntegerLockName(7, 100);
$yearLock = RSgetNextIntegerLockName(7, 100, array('propertyID' => 101, 'year' => 2026));
$seriesLock = RSgetNextIntegerLockName(7, 100, null, array('propertyID' => 102, 'value' => 'A'));
$customerLock = RSgetNextIntegerLockName(7, 100, null, null, array('propertyID' => 103, 'itemID' => 99));
nextIntegerAssert($globalLock === $sameGlobalLock, 'same sequence must produce the same lock name');
nextIntegerAssert($globalLock !== $yearLock && $yearLock !== $seriesLock, 'different scopes must produce independent lock names');
nextIntegerAssert($globalLock !== $customerLock, 'customer scope must be part of the lock name');
nextIntegerAssert(strlen($globalLock) <= 64, 'lock name must fit the database identifier limit');
nextIntegerAssert(RSacquireNextIntegerLock($globalLock), 'first caller must acquire a sequence lock');
nextIntegerAssert(!RSacquireNextIntegerLock($globalLock), 'second caller must not acquire an occupied sequence lock');
nextIntegerAssert(RSacquireNextIntegerLock($yearLock), 'different scope must acquire an independent lock');
nextIntegerAssert(RSreleaseNextIntegerLock($globalLock), 'owned sequence lock must be releasable');
nextIntegerAssert(RSacquireNextIntegerLock($globalLock), 'released sequence lock must be available again');

nextIntegerAssert(strpos($endpointSource, 'RSMnextIntegerManagement.php') === false, 'endpoint must use the shared item manager directly');
nextIntegerAssert(strpos($endpointSource, 'SELECT ') === false && strpos($endpointSource, '->prepare(') === false, 'API endpoint must not contain database queries');
nextIntegerAssert(strpos($itemsManagementSource, 'IQ_getFilteredItemsIDs') !== false, 'item manager source must remain readable as a complete utility');
nextIntegerAssert(strpos($itemsManagementSource, 'ORDER BY targetValue.RS_ITEM_ID DESC LIMIT 1') !== false, 'sequences must use a bounded last-item query in RSMitemsManagement');
nextIntegerAssert(strpos($endpointSource, "checkCorrectRequestMethod('POST')") !== false, 'endpoint must require POST');
nextIntegerAssert(strpos($endpointSource, 'RShasTokenPermission($RStoken, $propertyID, \'WRITE\')') !== false, 'endpoint must enforce target WRITE access');
nextIntegerAssert(substr_count($endpointSource, 'RShasTokenPermission($RStoken') >= 3, 'endpoint must enforce READ access on optional scopes');
nextIntegerAssert(strpos($endpointSource, 'RSitemMatchesTokenCustomerScope') !== false, 'endpoint must enforce customer item scope');
nextIntegerAssert(strpos($endpointSource, 'propertyType !== \'integer\'') !== false, 'endpoint must reject non-integer target properties');
nextIntegerAssert(strpos($endpointSource, 'Invalid propertyID') !== false, 'endpoint must reject unresolved target properties');
nextIntegerAssert(strpos($endpointSource, 'verifyItemExists') !== false, 'endpoint must reject nonexistent target items');
nextIntegerAssert(strpos($endpointSource, "array('date', 'datetime')") !== false, 'endpoint must validate year property types');
nextIntegerAssert(strpos($endpointSource, "array('file', 'image')") !== false, 'endpoint must reject non-filterable series property types');
nextIntegerAssert(strpos($endpointSource, '$hasYear !== $hasYearProperty') !== false, 'endpoint must reject incomplete year scope');
nextIntegerAssert(strpos($endpointSource, '$hasSeries !== $hasSeriesProperty') !== false, 'endpoint must reject incomplete series scope');
nextIntegerAssert(strpos($endpointSource, 'intval($currentValue) > 0') !== false, 'endpoint must preserve an existing positive value');
nextIntegerAssert(strpos($endpointSource, 'finally') !== false && strpos($endpointSource, 'RSreleaseNextIntegerLock') !== false, 'endpoint must release its sequence lock');

echo "next integer endpoint tests passed\n";

// Exercise persistence, conflict detection, rollback, and release without a server.
function getPropertyType($propertyID, $clientID) { return array(101 => 'date', 102 => 'text')[$propertyID] ?? 'integer'; }
function RSError($message) { }
function getItemPropertyValue($itemID, $propertyID, $clientID) {
    if ($itemID === null || $propertyID === null) return null;
    return $GLOBALS['allocationValues'][$itemID][$propertyID] ?? null;
}
function setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $value, $type, $userID) {
    if ($GLOBALS['allocationFailProperty'] === $propertyID) return -1;
    $GLOBALS['allocationValues'][$itemID][$propertyID] = $value;
    return 0;
}
$allocationValues = array();
$allocationFailProperty = 101;
$RSuserID = 1;
$allocationLock = RSgetNextIntegerLockName(7, 300);
$nextIntegerScalarQueue[] = 40;
nextIntegerAssert(nextIntegerTestAllocation(7,8,10,300,101,'2026-09-10') === false, 'date failure must reject assignment');
nextIntegerAssert($allocationValues === array() && $mysqli->rollbacks === 1, 'date failure must roll back the earlier number write');
nextIntegerAssert(!isset($nextIntegerLocks[$allocationLock]), 'failure must release sequence lock');
$allocationFailProperty = null;
$nextIntegerScalarQueue[] = 40;
nextIntegerAssert(nextIntegerTestAllocation(7,8,10,300,101,'2026-09-10') === 41, 'number/date assignment must succeed');
nextIntegerAssert($allocationValues[10] === array(300 => 41, 101 => '2026-09-10'), 'both properties must be persisted');
nextIntegerAssert($mysqli->commits === 1 && !isset($nextIntegerLocks[$allocationLock]), 'success must commit and release lock');
$nextIntegerScalarQueue[] = 41;
nextIntegerAssert(nextIntegerTestAllocation(7,8,10,300,101,'2026-09-11') === false, 'existing number/date must be preserved');
nextIntegerAssert($allocationValues[10][300] === 41 && $allocationValues[10][101] === '2026-09-10', 'conflict must not change either value');
$nextIntegerScalarQueue[] = 41;
nextIntegerAssert(RSallocateNextIntegerPropertyValue(7,8,300,function ($next) { throw new RuntimeException('injected'); }) === false, 'persistence exception must reject assignment');
nextIntegerAssert(!isset($nextIntegerLocks[$allocationLock]), 'exception must release lock');
RSacquireNextIntegerLock($allocationLock);
nextIntegerAssert(RSallocateNextIntegerPropertyValue(7,8,300,function ($next) { throw new RuntimeException('must not run'); }) === false, 'busy lock must prevent allocation');
RSreleaseNextIntegerLock($allocationLock);
echo "legacy sequence persistence tests passed\n";

// Test fixture: a caller persisting two properties inside the generic allocation callback.
function nextIntegerTestAllocation($clientID, $itemTypeID, $itemID, $propertyID, $datePropertyID, $date, $yearScope = null, $seriesScope = null)
{
    global $RSuserID;

    return RSallocateNextIntegerPropertyValue($clientID, $itemTypeID, $propertyID,
        function ($next) use ($clientID, $itemTypeID, $itemID, $propertyID, $datePropertyID, $date, $RSuserID) {
            if (getItemPropertyValue($itemID, $propertyID, $clientID) > 0
                || getItemPropertyValue($itemID, $datePropertyID, $clientID) != '') return false;
            if (setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $next, '', $RSuserID) !== 0) return false;
            return setPropertyValueByID($datePropertyID, $itemTypeID, $itemID, $clientID, $date, '', $RSuserID) === 0;
        }, $yearScope, $seriesScope);
}


// Execute the actual legacy command: this catches scope-construction bugs that
// helper-only tests miss, including RSM's null result for a missing property.
class InvoiceNumberResponse extends Exception {
    public $result;
    public function __construct($result) { parent::__construct('response'); $this->result = $result; }
}
function RSReturnArrayResults($result) { throw new InvoiceNumberResponse($result); }
function RSCheckUserAccess() { return 1; }
function dieWithError($code) { throw new RuntimeException('Unexpected HTTP ' . $code); }
function getClientItemTypeID_RelatedWith_byName($name, $clientID) { return 8; }
function getClientPropertyID_RelatedWith_byName($name, $clientID) {
    return array('invoice.client.invoiceID' => 300, 'invoice.client.invoiceDate' => 101,
        'invoice.client.serie' => $GLOBALS['invoiceSeriesPropertyID'])[$name] ?? 0;
}
class InvoiceGlobalResult {
    public function fetch_assoc() { return array('value' => $GLOBALS['invoiceResetYear']); }
}
function RSQuery($query) { return new InvoiceGlobalResult(); }
function runInvoiceNumberCommand($source) {
    global $mysqli, $cstRS_POST;
    try { eval($source); } catch (InvoiceNumberResponse $response) { return $response->result; }
    throw new RuntimeException('Invoice command did not respond');
}
$invoiceNumberSource = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/financialDocuments/wndFinancialDocuments_generateInvoiceDateAndID.php');
$invoiceNumberSource = preg_replace('/^require_once .*;\R/m', '', substr($invoiceNumberSource, 5));
$cstRS_POST = 'invoiceTestPost';
$GLOBALS[$cstRS_POST] = array('clientID' => 7, 'invoiceID' => '10');
foreach (array(0, 102) as $invoiceSeriesPropertyID) {
    foreach (array(null, '', 'A', '0') as $seriesValue) {
        foreach (array('0', '1') as $invoiceResetYear) {
            $allocationValues = array(10 => array(300 => 0));
            if ($seriesValue !== null) $allocationValues[10][102] = $seriesValue;
            $allocationFailProperty = null;
            $nextIntegerScalarQueue = array(453);
            $nextIntegerExecutions = array();
            $nextIntegerLocks = array();
            $result = runInvoiceNumberCommand($invoiceNumberSource);
            nextIntegerAssert($result['result'] === 'OK' && $result['ID'] === 454, 'legacy command must allocate with missing, empty or populated series');
            nextIntegerAssert($allocationValues[10][101] === date('Y-m-d'), 'legacy command must persist the date');
            $sequenceQuery = array_values(array_filter($nextIntegerExecutions, function ($execution) { return strpos($execution['query'], 'ORDER BY targetValue.RS_ITEM_ID DESC LIMIT 1') !== false; }))[0];
            $hasSeries = $invoiceSeriesPropertyID > 0 && $seriesValue !== null && $seriesValue !== '';
            nextIntegerAssert((strpos($sequenceQuery['query'], 'seriesValue') !== false) === $hasSeries, 'only a configured and populated series may filter the sequence');
            nextIntegerAssert((strpos($sequenceQuery['query'], 'yearValue') !== false) === ($invoiceResetYear === '1'), 'global annual reset must be respected');
            if ($hasSeries) nextIntegerAssert(in_array($seriesValue, $sequenceQuery['parameters'], true), 'series value including zero must remain a bound filter');
            nextIntegerAssert(count($nextIntegerLocks) === 0, 'legacy command must release its lock');
        }
    }
}
echo "legacy invoice number command tests passed\n";
