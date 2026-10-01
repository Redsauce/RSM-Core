<?php
require_once 'RSMbinaryProperties.php';

function RSpositiveUploadID($value)
{
    if ((!is_int($value) && !is_string($value)) || !ctype_digit((string)$value)
        || filter_var(ltrim((string)$value, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
        throw new InvalidArgumentException('Invalid upload identifier');
    }
    return intval($value);
}

function RScreateItemTypeWithIcon($clientID, $name, $icon)
{
    $clientID = RSpositiveUploadID($clientID);
    if (!is_string($name)) throw new InvalidArgumentException('Invalid item type name');
    $data = RSdecodeBinaryPropertyValue(RSparseBinaryPropertyValue($icon, ''));
    $itemTypeID = getNextIdentification('rs_item_types', 'RS_ITEMTYPE_ID', $clientID);
    $order = getGenericNext('rs_item_types', 'RS_ORDER', array('RS_CLIENT_ID' => $clientID));
    if ($itemTypeID <= 0 || $order === false) return false;
    return RSexecutePreparedUploadWrite('INSERT INTO rs_item_types (RS_ITEMTYPE_ID, RS_MAIN_PROPERTY_ID, RS_CLIENT_ID, RS_NAME, RS_ICON, RS_ORDER) VALUES (?, 0, ?, ?, ?, ?)',
        'iisbi', array($itemTypeID, $clientID, $name, null, $order), array(3 => $data)) ? $itemTypeID : false;
}

function RSupdateItemTypeWithIcon($clientID, $itemTypeID, $name, $icon)
{
    global $mysqli;
    $clientID = RSpositiveUploadID($clientID);
    $itemTypeID = RSpositiveUploadID($itemTypeID);
    if (!is_string($name)) throw new InvalidArgumentException('Invalid item type name');
    $data = RSdecodeBinaryPropertyValue(RSparseBinaryPropertyValue($icon, ''));
    $statement = $mysqli->prepare('SELECT RS_ITEMTYPE_ID FROM rs_item_types WHERE RS_CLIENT_ID = ? AND RS_ITEMTYPE_ID = ?');
    if (!$statement) return false;
    try {
        if (!$statement->bind_param('ii', $clientID, $itemTypeID) || !$statement->execute()) return false;
        $foundID = null;
        $statement->bind_result($foundID);
        if (!$statement->fetch()) return false;
    } finally { $statement->close(); }
    return RSexecutePreparedUploadWrite('UPDATE rs_item_types SET RS_NAME = ?, RS_ICON = ? WHERE RS_CLIENT_ID = ? AND RS_ITEMTYPE_ID = ?',
        'sbii', array($name, null, $clientID, $itemTypeID), array(1 => $data));
}

// Validate every image before removing old globals; renames and swaps stay atomic.
function RSsaveGlobalVariables($clientID, $updates, $inserts, $deletes)
{
    global $mysqli;
    $clientID = RSpositiveUploadID($clientID);
    $variables = array_merge($updates, $inserts);
    $oldNames = array_merge(array_column($updates, 'dbName'), array_column($deletes, 'dbName'));
    foreach ($oldNames as $name) if (!is_string($name)) throw new InvalidArgumentException('Invalid global name');
    foreach ($variables as &$variable) {
        if (!is_string($variable['name']) || !is_string($variable['value'])
            || !ctype_digit((string)$variable['type']) || intval($variable['type']) > 255) {
            throw new InvalidArgumentException('Invalid global variable');
        }
        $variable['type'] = intval($variable['type']);
        if ($variable['type'] !== 0) {
            $variable['value'] = RSdecodeBinaryPropertyValue(RSparseBinaryPropertyValue($variable['value'], ''));
        }
    }
    unset($variable);
    $started = false;
    try {
        if (!$mysqli->begin_transaction()) throw new RuntimeException('Unable to start global transaction');
        $started = true;
        foreach ($variables as $variable) {
            $statement = $mysqli->prepare('SELECT RS_NAME FROM rs_globals WHERE RS_CLIENT_ID = ? AND RS_NAME = ? FOR UPDATE');
            if (!$statement) throw new RuntimeException('Unable to read globals');
            try {
                $name = $variable['name']; $existingName = null;
                if (!$statement->bind_param('is', $clientID, $name) || !$statement->execute()) throw new RuntimeException('Unable to read globals');
                $statement->bind_result($existingName);
                $found = $statement->fetch();
                if ($found === false) throw new RuntimeException('Unable to fetch global');
            } finally { $statement->close(); }
            if ($found && !in_array($existingName, $oldNames, true)) {
                return array('result' => 'NOK', 'var' => $existingName);
            }
        }
        foreach ($oldNames as $name) {
            if (!RSexecutePreparedUploadWrite('DELETE FROM rs_globals WHERE RS_CLIENT_ID = ? AND RS_NAME = ?', 'is', array($clientID, $name))) {
                throw new RuntimeException('Unable to remove old globals');
            }
        }
        foreach ($variables as $variable) {
            $isImage = $variable['type'] !== 0;
            if (!RSexecutePreparedUploadWrite('INSERT INTO rs_globals (RS_CLIENT_ID, RS_NAME, RS_VALUE, RS_IMAGE) VALUES (?, ?, ?, ?)',
                $isImage ? 'isbi' : 'issi', array($clientID, $variable['name'], $isImage ? null : $variable['value'], $variable['type']),
                $isImage ? array(2 => $variable['value']) : array())) {
                throw new RuntimeException('Unable to insert global');
            }
        }
        if (!$mysqli->commit()) throw new RuntimeException('Unable to commit globals');
        $started = false;
        return array('result' => 'OK');
    } catch (Throwable $exception) {
        error_log('RSM: global variable write failed');
        return array('result' => 'NOK', 'message' => 'Unable to save variables');
    } finally {
        if ($started) $mysqli->rollback();
    }
}

// CSV transport stays unchanged; every property uses the shared typed writer.
function RSimportItemsWithProperties($clientID, $itemTypeID, $itemIDs, $propertyIDs, $rows, $overwrite)
{
    global $mysqli;
    $clientID = RSpositiveUploadID($clientID);
    $itemTypeID = RSpositiveUploadID($itemTypeID);
    $propertyIDs = array_map('RSpositiveUploadID', $propertyIDs);
    $itemIDs = array_map('RSpositiveUploadID', $itemIDs);
    if (!$rows || ($itemIDs && count($itemIDs) !== count($rows))) throw new InvalidArgumentException('Invalid import rows');
    if (!$overwrite && count(array_unique($itemIDs)) !== count($itemIDs)) return array('result' => 'ERROR2');
    $properties = getClientItemTypeProperties($itemTypeID, $clientID, 0, true);
    $writes = array();
    foreach ($rows as $row) {
        $itemWrites = array();
        foreach ($properties as $property) {
            $index = array_search(intval($property['id']), $propertyIDs, true);
            if ($index === false && $overwrite) continue;
            if ($index !== false && !isset($row[$index])) throw new InvalidArgumentException('Missing imported property');
            $value = $index === false ? getClientPropertyDefaultValue($property['id'], $clientID) : base64_decode($row[$index], true);
            if ($value === false) throw new InvalidArgumentException('Invalid imported Base64');
            $value = enforcePropertyType($value, $clientID, $property['id'], $property['type']);
            $itemWrites[] = array('property' => $property, 'value' => $value);
        }
        $writes[] = $itemWrites;
    }
    $started = false; $failure = 'ERROR3';
    try {
        if (!$mysqli->begin_transaction()) throw new RuntimeException('Unable to start import');
        $started = true;
        if (!RSlockItemTypeForDuplication($itemTypeID, $clientID)) throw new RuntimeException('Unable to lock import type');
        if (!$itemIDs) {
            $nextID = getNextIdentification('rs_items', 'RS_ITEM_ID', $clientID, array('RS_ITEMTYPE_ID' => $itemTypeID));
            if ($nextID <= 0) throw new RuntimeException('Unable to allocate import IDs');
            $itemIDs = range($nextID, $nextID + count($rows) - 1);
        } elseif (!$overwrite) {
            $existing = RSQuery('SELECT RS_ITEM_ID FROM rs_items WHERE RS_CLIENT_ID = ' . $clientID . ' AND RS_ITEMTYPE_ID = ' . $itemTypeID
                . ' AND RS_ITEM_ID IN (' . implode(',', $itemIDs) . ') LIMIT 1');
            if (!$existing) throw new RuntimeException('Unable to check import IDs');
            if ($existing->num_rows > 0) return array('result' => 'ERROR1');
        }
        foreach ($itemIDs as $index => $itemID) {
            $operation = $overwrite ? 'REPLACE' : 'INSERT';
            if (!RSexecutePreparedUploadWrite($operation . ' INTO rs_items (RS_ITEMTYPE_ID, RS_ITEM_ID, RS_CLIENT_ID) VALUES (?, ?, ?)',
                'iii', array($itemTypeID, $itemID, $clientID))) throw new RuntimeException('Unable to insert imported item');
            $failure = 'ERROR0';
            foreach ($writes[$index] as $write) {
                $property = $write['property'];
                // The CSV endpoint historically did not create audit rows.
                if (!_dbInsertItemPropertyValue($itemTypeID, $itemID, $property['id'], $property['type'], $write['value'], $clientID, $overwrite, false)) {
                    throw new RuntimeException('Unable to insert imported property');
                }
            }
            $failure = 'ERROR3';
        }
        if (!RSexecutePreparedUploadWrite('UPDATE rs_item_types SET RS_LAST_ITEM_ID = GREATEST(RS_LAST_ITEM_ID, ?) WHERE RS_CLIENT_ID = ? AND RS_ITEMTYPE_ID = ?',
            'iii', array(max($itemIDs), $clientID, $itemTypeID))) throw new RuntimeException('Unable to update import counter');
        if (!$mysqli->commit()) throw new RuntimeException('Unable to commit import');
        $started = false;
        foreach ($itemIDs as $index => $itemID) {
            foreach ($writes[$index] as $write) {
                if (in_array($write['property']['type'], array('file', 'image'), true)) {
                    try { deleteMediaFile($clientID, $itemID, $write['property']['id']); }
                    catch (Throwable $cacheError) { error_log('RSM: imported media cache invalidation failed'); }
                }
            }
        }
        return array('result' => 'OK', 'itemIDs' => $itemIDs);
    } catch (Throwable $exception) {
        error_log('RSM: item import failed');
        return array('result' => $failure, 'description' => 'Unable to import items');
    } finally {
        if ($started) $mysqli->rollback();
    }
}
