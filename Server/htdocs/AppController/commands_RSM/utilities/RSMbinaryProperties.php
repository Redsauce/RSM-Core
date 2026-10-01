<?php
// Legacy transport: [filename:]hexadecimal, optionally prefixed with 0x.
// Validate before decoding; malformed uploads must never fall back to defaults.
function RSparseBinaryPropertyValue($value, $name = null)
{
    global $RSmaxBinaryPropertyBytes;
    $limit = $RSmaxBinaryPropertyBytes ?? (32 * 1024 * 1024);
    if (!is_int($limit) || $limit <= 0 || $limit > intdiv(PHP_INT_MAX - 1024, 2)) {
        throw new RuntimeException('Invalid binary property size limit');
    }
    if (!is_string($value) || ($name !== null && !is_string($name))) {
        throw new InvalidArgumentException('File/image values must be strings');
    }
    if (strlen($value) > 2 * $limit + 1024) {
        throw new LengthException('File/image exceeds the configured size limit');
    }
    if ($name === null) {
        $separator = strpos($value, ':');
        $name = $separator === false ? '' : substr($value, 0, $separator);
        $value = $separator === false ? $value : substr($value, $separator + 1);
    }
    if (strlen($name) > 1020 || preg_match('/\A[^:\x00\r\n]{0,255}\z/u', $name) !== 1) {
        throw new InvalidArgumentException('Invalid file/image filename');
    }
    if (substr($value, 0, 2) === '0x' || substr($value, 0, 2) === '0X') {
        $value = substr($value, 2);
    }
    $length = strlen($value);
    if ($length > 2 * $limit) {
        throw new LengthException('File/image exceeds the configured size limit');
    }
    if ($length % 2 !== 0 || preg_match('/\A[0-9a-fA-F]*\z/', $value) !== 1) {
        throw new InvalidArgumentException('File/image content must be hexadecimal with an even length');
    }
    return array('name' => $name, 'hex' => $value, 'size' => intdiv($length, 2));
}

function RSdecodeBinaryPropertyValue($parsed)
{
    global $RSmaxBinaryPropertyBytes;
    $data = hex2bin($parsed['hex']);
    if ($data === false || strlen($data) > ($RSmaxBinaryPropertyBytes ?? (32 * 1024 * 1024))) {
        throw new LengthException('Invalid or oversized decoded file/image');
    }
    return $data;
}

// Send an empty chunk too: an empty BLOB is different from SQL NULL.
function RSsendBinaryStatementData($statement, $parameterIndex, $data)
{
    $offset = 0;
    do {
        if (!$statement->send_long_data($parameterIndex, substr($data, $offset, 65536))) return false;
        $offset += 65536;
    } while ($offset < strlen($data));
    return true;
}

function RSstoreBinaryPropertyValue($operation, $clientID, $itemTypeID, $itemID, $propertyID, $propertyType, $parsed, $data)
{
    global $mysqli, $propertiesTables, $RSmaxBinaryPropertyBytes;
    if (!in_array($operation, array('INSERT', 'REPLACE'), true)
        || !in_array($propertyType, array('file', 'image'), true)
        || strlen($data) > ($RSmaxBinaryPropertyBytes ?? (32 * 1024 * 1024))) return false;
    return RSexecutePreparedUploadWrite($operation . ' INTO ' . $propertiesTables[$propertyType]
        . ' (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_NAME, RS_SIZE, RS_DATA) VALUES (?, ?, ?, ?, ?, ?, ?)',
        'iiiisib', array($clientID, $itemTypeID, $itemID, $propertyID, $parsed['name'], strlen($data), null), array(6 => $data));
}

function RSinsertBinaryPropertyAudit($clientID, $itemTypeID, $itemID, $propertyID, $propertyType, $userID, $token, $parsed, $data)
{
    global $mysqli, $auditTrailPropertiesTables;
    if (!in_array($propertyType, array('file', 'image'), true)) return false;
    return RSexecutePreparedUploadWrite('INSERT INTO ' . $auditTrailPropertiesTables[$propertyType]
        . ' (RS_CLIENT_ID, RS_ITEMTYPE_ID, RS_ITEM_ID, RS_PROPERTY_ID, RS_USER_ID, RS_TOKEN, RS_CHANGED_DATE,'
        . ' RS_INITIAL_NAME, RS_INITIAL_SIZE, RS_INITIAL_VALUE, RS_FINAL_NAME, RS_FINAL_SIZE, RS_FINAL_VALUE)'
        . " VALUES (?, ?, ?, ?, ?, ?, ?, '', 0, '', ?, ?, ?)", 'iiiiisssib',
        array($clientID, $itemTypeID, $itemID, $propertyID, $userID, (string)$token, date('Y-m-d H:i:s'), $parsed['name'], strlen($data), null), array(9 => $data));
}

// SQL is supplied only by trusted storage helpers, never by request parameters.
function RSexecutePreparedUploadWrite($sql, $types, $values, $binaryValues = array())
{
    global $mysqli, $RSmaxBinaryPropertyBytes;
    foreach ($binaryValues as $index => $data) {
        if (!is_string($data) || !isset($types[$index]) || $types[$index] !== 'b'
            || strlen($data) > ($RSmaxBinaryPropertyBytes ?? (32 * 1024 * 1024))) return false;
    }
    $statement = null;
    try {
        $statement = $mysqli->prepare($sql);
        if (!$statement) return false;
        $arguments = array($types);
        foreach ($values as $index => &$value) $arguments[] = &$value;
        unset($value);
        if (!call_user_func_array(array($statement, 'bind_param'), $arguments)) return false;
        foreach ($binaryValues as $index => $data) {
            if (!RSsendBinaryStatementData($statement, $index, $data)) return false;
        }
        return $statement->execute();
    } catch (mysqli_sql_exception $exception) {
        error_log('RSM: prepared upload write failed');
        return false;
    } finally {
        if ($statement) $statement->close();
    }
}
