<?php
// Recursive and grouped calls share the outer operation; only its owner
// begins, commits or rolls back the transaction.
function RSwithItemWriteTransaction($operation)
{
    global $mysqli, $RSMcreatedItemIDs, $RSMupdatedItemIDs, $RSMdeletedItemIDs;
    static $depth = 0;
    $ownsTransaction = $depth === 0;
    $before = array($RSMcreatedItemIDs ?? array(), $RSMupdatedItemIDs ?? array(), $RSMdeletedItemIDs ?? array());
    $started = false;
    $previousRequired = $GLOBALS['RSrequireItemWriteQueries'] ?? false;
    try {
        if ($ownsTransaction) {
            if (!$mysqli->begin_transaction()) {
                throw new RuntimeException('Unable to start item write transaction');
            }
            $started = true;
        }
        $depth++;
        $GLOBALS['RSrequireItemWriteQueries'] = true;
        try {
            $result = $operation();
        } finally {
            $depth--;
        }
        if ($ownsTransaction && !$mysqli->commit()) {
            throw new RuntimeException('Unable to commit item write transaction');
        }
        return $result;
    } catch (Throwable $exception) {
        if ($started) {
            try {
                $mysqli->rollback();
            } catch (Throwable $rollbackError) {
                error_log('RSM: item write rollback failed');
            }
        }
        list($RSMcreatedItemIDs, $RSMupdatedItemIDs, $RSMdeletedItemIDs) = $before;
        throw $exception;
    } finally {
        $GLOBALS['RSrequireItemWriteQueries'] = $previousRequired;
    }
}

// Remove values from every storage table, including values whose definition was
// removed or changed previously. Audit history deliberately remains untouched.
function RSdeleteStoredItemValues($itemTypeID, $clientID, $ids = '')
{
    global $propertiesTables;
    $condition = ' WHERE RS_CLIENT_ID = ' . intval($clientID)
        . ' AND RS_ITEMTYPE_ID = ' . intval($itemTypeID);
    if ($ids !== '') {
        $condition .= ' AND RS_ITEM_ID IN (' . $ids . ')';
    }
    foreach (array_unique(array_values($propertiesTables)) as $table) {
        RSQuery('DELETE FROM ' . $table . $condition);
    }
}
