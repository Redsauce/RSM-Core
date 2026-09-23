<?php

// Optional MariaDB integration tests for last-item sequence calculation and
// connection-level advisory locking. Uses a disposable local database.

function nextIntegerDbAssert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$admin = @new mysqli('127.0.0.1', 'root', '', 'mysql', 3306);
if ($admin->connect_errno) {
    fwrite(STDERR, 'Local MariaDB is not reachable as root without password: ' . $admin->connect_error . PHP_EOL);
    exit(2);
}
$admin->set_charset('utf8mb4');

$databaseName = 'rsm_next_integer_test';
$admin->query('DROP DATABASE IF EXISTS `' . $databaseName . '`');
$admin->query('CREATE DATABASE `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$admin->select_db($databaseName);

$mysqli = $admin;
$propertiesTables = array(
    'text' => 'rs_property_text',
    'date' => 'rs_property_dates',
    'datetime' => 'rs_property_datetime',
    'integer' => 'rs_property_integers',
    'identifier' => 'rs_property_identifiers',
    'identifiers' => 'rs_property_multiIdentifiers',
);

$itemsManagementSource = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php');
nextIntegerDbAssert($itemsManagementSource !== false, 'RSMitemsManagement must be readable');

function extractNextIntegerDbFunction($source, $functionName)
{
    $start = strpos($source, 'function ' . $functionName . '(');
    nextIntegerDbAssert($start !== false, $functionName . ' must exist in RSMitemsManagement');
    $brace = strpos($source, '{', $start);
    nextIntegerDbAssert($brace !== false, $functionName . ' must have a body');
    $depth = 0;
    for ($index = $brace, $length = strlen($source); $index < $length; $index++) {
        if ($source[$index] === '{') $depth++;
        if ($source[$index] === '}') $depth--;
        if ($depth === 0) return substr($source, $start, $index - $start + 1);
    }
    nextIntegerDbAssert(false, $functionName . ' body must close');
}

foreach (array(
    'RSnextIntegerExecuteScalar',
    'RSgetNextIntegerPropertyValue',
    'RSgetNextIntegerLockName',
    'RSacquireNextIntegerLock',
    'RSreleaseNextIntegerLock',
    'RSallocateNextIntegerPropertyValue',
) as $functionName) {
    eval(extractNextIntegerDbFunction($itemsManagementSource, $functionName));
}

// Persistence doubles use real database writes so rollback and lock lifetime are tested.
function getPropertyType($propertyID, $clientID) { return 'integer'; }
function RSError($message) { }
function getItemPropertyValue($itemID, $propertyID, $clientID) {
    global $mysqli;
    $table = $propertyID == 101 ? 'rs_property_dates' : 'rs_property_integers';
    $row = $mysqli->query("SELECT RS_DATA FROM $table WHERE RS_CLIENT_ID=$clientID AND RS_ITEMTYPE_ID=8 AND RS_ITEM_ID=$itemID AND RS_PROPERTY_ID=$propertyID")->fetch_assoc();
    return $row['RS_DATA'] ?? '';
}
function setPropertyValueByID($propertyID, $itemTypeID, $itemID, $clientID, $value, $type, $userID) {
    global $mysqli, $failDateWrite;
    if ($propertyID == 101 && $failDateWrite) return -1;
    $table = $propertyID == 101 ? 'rs_property_dates' : 'rs_property_integers';
    $value = $mysqli->real_escape_string((string)$value);
    return $mysqli->query("REPLACE INTO $table VALUES ($clientID, $itemTypeID, $itemID, $propertyID, '$value')") ? 0 : -1;
}
$RSuserID = 1;
$failDateWrite = false;

try {
    foreach (array_unique(array_values($propertiesTables)) as $table) {
        $admin->query(
            'CREATE TABLE `' . $table . '` ('
            . 'RS_CLIENT_ID INT NOT NULL, '
            . 'RS_ITEMTYPE_ID INT NOT NULL, '
            . 'RS_ITEM_ID INT NOT NULL, '
            . 'RS_PROPERTY_ID INT NOT NULL, '
            . ($table === $propertiesTables['integer'] ? 'RS_DATA INT, ' : 'RS_DATA VARCHAR(255), ')
            . 'PRIMARY KEY (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID)'
            . ') ENGINE=InnoDB'
        );
    }

    $admin->query("INSERT INTO rs_property_integers VALUES
        (7, 8, 1, 100, '41'),
        (7, 8, 2, 100, '50'),
        (7, 8, 3, 100, '70'),
        (7, 8, 4, 100, '90')");
    $admin->query("INSERT INTO rs_property_integers VALUES
        (7, 8, 1, 201, '1'),
        (7, 8, 2, 201, '2'),
        (7, 8, 3, 201, '3'),
        (7, 8, 4, 201, '4'),
        (7, 8, 5, 201, '1')");
    $admin->query("INSERT INTO rs_property_dates VALUES
        (7, 8, 1, 101, '2026-04-10'),
        (7, 8, 2, 101, '2025-06-01'),
        (7, 8, 3, 101, '2026-08-20'),
        (7, 8, 4, 101, '2026-09-02')");
    $admin->query("INSERT INTO rs_property_text VALUES
        (7, 8, 1, 102, 'A'),
        (7, 8, 2, 102, 'A'),
        (7, 8, 3, 102, 'B'),
        (7, 8, 4, 102, 'A')");
    $admin->query("INSERT INTO rs_property_identifiers VALUES
        (7, 8, 1, 103, '99'),
        (7, 8, 2, 103, '99'),
        (7, 8, 3, 103, '99'),
        (7, 8, 4, 103, '100')");

    $yearScope = array('propertyID' => 101, 'type' => 'date', 'year' => 2026);
    $seriesA = array('propertyID' => 102, 'type' => 'text', 'value' => 'A');
    $customer99 = array('propertyID' => 103, 'type' => 'identifier', 'itemID' => 99);

    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100) === 91, 'global sequence calculation failed');
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 201) === 2, 'sequence 1,2,3,4,1 must continue with 2 from the last numbered item');
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, $yearScope) === 91, 'year-only sequence calculation failed');
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, null, $seriesA) === 91, 'series-only sequence calculation failed');
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, $yearScope, $seriesA) === 91, 'combined sequence calculation failed');
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, $yearScope, $seriesA, $customer99) === 42, 'customer-scoped sequence calculation failed');

    // Large foreign values must not leak across tenant, item type, or property boundaries.
    $admin->query("INSERT INTO rs_property_integers VALUES (9,8,1,100,'999'),(7,9,1,100,'999'),(7,8,1,999,'999')");
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100) === 91, 'scope isolation failed');
    $relatedScope = array('propertyID' => 103, 'type' => 'identifier', 'values' => array(99));
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, $yearScope, $relatedScope) === 71, 'related-item set must use the last matching item');
    $relatedScope['values'] = array(99, 100);
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, $yearScope, $relatedScope) === 91, 'related-item set union failed');
    $relatedScope['values'] = array();
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 100, $yearScope, $relatedScope) === 1, 'empty related-item set must match nothing');

    $admin->query("INSERT INTO rs_property_text VALUES (7,8,10,102,'A'),(7,8,11,102,'A')");
    $failDateWrite = true;
    nextIntegerDbAssert(nextIntegerTestAllocation(7,8,10,100,101,'2026-09-10',$yearScope,$seriesA) === false, 'date failure must fail allocation');
    nextIntegerDbAssert(getItemPropertyValue(10,100,7) === '', 'failed date write must roll back the number');
    $failDateWrite = false;
    nextIntegerDbAssert(nextIntegerTestAllocation(7,8,10,100,101,'2026-09-10',$yearScope,$seriesA) === 91, 'number and date allocation failed');
    nextIntegerDbAssert(getItemPropertyValue(10,101,7) === '2026-09-10', 'date was not persisted');
    nextIntegerDbAssert(nextIntegerTestAllocation(7,8,10,100,101,'2026-09-10',$yearScope,$seriesA) === false, 'existing number/date must not be overwritten');
    nextIntegerDbAssert(nextIntegerTestAllocation(7,8,11,100,101,'2026-09-10',$yearScope,$seriesA) === 92, 'next allocation must see the committed date and number');
    nextIntegerDbAssert(RSallocateNextIntegerPropertyValue(7,8,100,function ($next) { throw new RuntimeException('injected failure'); }) === false, 'exception must fail allocation');
    $probe = new mysqli('127.0.0.1', 'root', '', $databaseName, 3306);
    $mysqli = $probe;
    nextIntegerDbAssert(RSacquireNextIntegerLock(RSgetNextIntegerLockName(7,100),0), 'exception must release the lock');
    RSreleaseNextIntegerLock(RSgetNextIntegerLockName(7,100));
    $probe->close();
    $mysqli = $admin;

    $largeValues = array();
    for ($itemID = 1; $itemID <= 5000; $itemID++) {
        $largeValues[] = '(7, 8, ' . $itemID . ", 200, '" . $itemID . "')";
        if (count($largeValues) === 500) {
            $admin->query('INSERT INTO rs_property_integers VALUES ' . implode(',', $largeValues));
            $largeValues = array();
        }
    }
    nextIntegerDbAssert(RSgetNextIntegerPropertyValue(7, 8, 200) === 5001, 'large last-item sequence calculation failed');

    $secondConnection = new mysqli('127.0.0.1', 'root', '', $databaseName, 3306);
    $secondConnection->set_charset('utf8mb4');
    $sameScopeLock = RSgetNextIntegerLockName(7, 100, $yearScope, $seriesA);
    $differentScopeLock = RSgetNextIntegerLockName(7, 100, array('propertyID' => 101, 'year' => 2025), $seriesA);

    $mysqli = $admin;
    nextIntegerDbAssert(RSacquireNextIntegerLock($sameScopeLock, 0), 'first connection could not acquire lock');
    $mysqli = $secondConnection;
    nextIntegerDbAssert(!RSacquireNextIntegerLock($sameScopeLock, 0), 'second connection acquired an occupied lock');
    nextIntegerDbAssert(RSacquireNextIntegerLock($differentScopeLock, 0), 'different scope lock was unnecessarily blocked');
    nextIntegerDbAssert(RSreleaseNextIntegerLock($differentScopeLock), 'second connection could not release different scope lock');
    $mysqli = $admin;
    nextIntegerDbAssert(RSreleaseNextIntegerLock($sameScopeLock), 'first connection could not release lock');
    $mysqli = $secondConnection;
    nextIntegerDbAssert(RSacquireNextIntegerLock($sameScopeLock, 0), 'released lock was not available to second connection');
    nextIntegerDbAssert(RSreleaseNextIntegerLock($sameScopeLock), 'second connection could not release acquired lock');
    $secondConnection->close();

    echo "next integer MariaDB integration tests passed\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    $admin->select_db('mysql');
    $admin->query('DROP DATABASE IF EXISTS `' . $databaseName . '`');
    exit(1);
}

$admin->select_db('mysql');
$admin->query('DROP DATABASE IF EXISTS `' . $databaseName . '`');

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

