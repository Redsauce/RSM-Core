<?php

// Remote read benchmark for RSM API environments.
// It discovers readable item types with getTypes, then uses those types to run
// reproducible read-only get/count requests against production or dev.
// The token is read from RSM_REMOTE_TOKEN and is never printed by this script.

$baseUrl = rtrim((string) (getenv('RSM_REMOTE_BASE_URL') ?: 'https://rsm1.redsauce.net/AppController/commands_RSM'), '/');
$token = (string) getenv('RSM_REMOTE_TOKEN');
$iterations = max(1, intval(getenv('RSM_REMOTE_ITERATIONS') ?: 3));
$maxTypes = max(1, intval(getenv('RSM_REMOTE_MAX_TYPES') ?: 5));
$maxProperties = max(1, intval(getenv('RSM_REMOTE_MAX_PROPERTIES') ?: 8));

if ($token === '') {
    fwrite(STDERR, "RSM_REMOTE_TOKEN is required\n");
    exit(1);
}

function failRemote(string $message): void {
    fwrite(STDERR, "FAIL: " . $message . "\n");
    exit(1);
}

function httpJsonRequest(string $method, string $url, string $token, ?array $body = null): array {
    $ch = curl_init($url);
    $headers = array('Authorization: ' . $token, 'Accept: application/json');
    $payload = null;

    if ($body !== null) {
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($payload);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);

    $start = microtime(true);
    $response = curl_exec($ch);
    $timeMs = (microtime(true) - $start) * 1000;
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($response === false) {
        $response = '';
    }

    return array(
        'status' => $status,
        'timeMs' => $timeMs,
        'size' => strlen($response),
        'hash' => hash('sha256', $response),
        'contentType' => $contentType,
        'body' => $response,
        'error' => $error,
    );
}


function httpFormRequest(string $method, string $url, string $token, array $fields): array {
    // API v1 reads $_POST, so send the token and parameters as form fields.
    // The token is included in the request but never printed in reports.
    $fields['RStoken'] = $token;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json, text/xml, */*'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);

    $start = microtime(true);
    $response = curl_exec($ch);
    $timeMs = (microtime(true) - $start) * 1000;
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($response === false) {
        $response = '';
    }

    return array(
        'status' => $status,
        'timeMs' => $timeMs,
        'size' => strlen($response),
        'hash' => hash('sha256', $response),
        'contentType' => $contentType,
        'body' => $response,
        'error' => $error,
    );
}

function median(array $values): float {
    sort($values);
    return $values[(int) floor(count($values) / 2)];
}

function runCase(string $name, string $method, string $url, string $token, ?array $body, int $iterations): array {
    $times = array();
    $last = null;
    for ($i = 0; $i < $iterations; $i++) {
        $last = httpJsonRequest($method, $url, $token, $body);
        $times[] = $last['timeMs'];
    }

    $rows = countRowsFromBody($last['body']);
    $countValue = countValueFromBody($last['body']);
    $resultStatus = resultStatusFromBody($last['body']);
    $hasPhpOutput = preg_match('/(Fatal error|Parse error|Deprecated|Warning|Notice):/i', $last['body']) === 1;

    return array(
        'case' => $name,
        'status' => $last['status'],
        'medianMs' => median($times),
        'minMs' => min($times),
        'maxMs' => max($times),
        'size' => $last['size'],
        'hash' => $last['hash'],
        'rows' => $rows,
        'count' => $countValue,
        'result' => $resultStatus,
        'contentType' => $last['contentType'],
        'phpOutput' => $hasPhpOutput,
        'error' => $last['error'],
    );
}


function runFormCase(string $name, string $method, string $url, string $token, array $fields, int $iterations): array {
    $times = array();
    $last = null;
    for ($i = 0; $i < $iterations; $i++) {
        $last = httpFormRequest($method, $url, $token, $fields);
        $times[] = $last['timeMs'];
    }

    $rows = countRowsFromBody($last['body']);
    $countValue = countValueFromBody($last['body']);
    $resultStatus = resultStatusFromBody($last['body']);
    $hasPhpOutput = preg_match('/(Fatal error|Parse error|Deprecated|Warning|Notice):/i', $last['body']) === 1;

    return array(
        'case' => $name,
        'status' => $last['status'],
        'medianMs' => median($times),
        'minMs' => min($times),
        'maxMs' => max($times),
        'size' => $last['size'],
        'hash' => $last['hash'],
        'rows' => $rows,
        'count' => $countValue,
        'result' => $resultStatus,
        'contentType' => $last['contentType'],
        'phpOutput' => $hasPhpOutput,
        'error' => $last['error'],
    );
}

function selectReadableTypes(array $types, int $maxTypes): array {
    usort($types, function ($a, $b) {
        return count($b['properties'] ?? array()) <=> count($a['properties'] ?? array());
    });

    $selected = array();
    foreach ($types as $type) {
        if (!isset($type['itemTypeID']) || empty($type['properties']) || !is_array($type['properties'])) {
            continue;
        }
        $selected[] = $type;
        if (count($selected) >= $maxTypes) break;
    }
    return $selected;
}

function propertyIDs(array $type, int $maxProperties): array {
    return array_slice(array_keys($type['properties'] ?? array()), 0, $maxProperties);
}

function firstRowsFromBody(string $body): array {
    $decoded = json_decode($body, true);
    return is_array($decoded) && array_is_list($decoded) ? $decoded : array();
}


function countRowsFromBody(string $body): ?int {
    // v2 devuelve arrays JSON y v1 devuelve XML con nodos <row>.
    // Contamos ambos formatos para poder comparar que leen las mismas filas.
    $decoded = json_decode($body, true);
    if (is_array($decoded) && array_is_list($decoded)) {
        return count($decoded);
    }

    if (preg_match('/<row[>\s]/', $body)) {
        return preg_match_all('/<row[>\s]/', $body);
    }

    return null;
}

function countValueFromBody(string $body): ?int {
    // Los endpoints de count no devuelven filas, sino un total.
    // v2 usa JSON {"count": n}; v1 usa XML con columnas de resultado.
    $decoded = json_decode($body, true);
    if (is_array($decoded) && isset($decoded['count']) && is_numeric($decoded['count'])) {
        return intval($decoded['count']);
    }

    if (preg_match('/<column name="total"><!\[CDATA\[([0-9]+)\]\]><\/column>/', $body, $matches)) {
        return intval($matches[1]);
    }

    return null;
}

function resultStatusFromBody(string $body): ?string {
    // Algunas rutas antiguas devuelven HTTP 200 aunque el XML diga NOK.
    // Guardamos ese estado para no confundir errores logicos con filas validas.
    $decoded = json_decode($body, true);
    if (is_array($decoded) && isset($decoded['result']) && is_scalar($decoded['result'])) {
        return (string) $decoded['result'];
    }

    if (preg_match('/<column name="result"><!\[CDATA\[([^\]]*)\]\]><\/column>/', $body, $matches)) {
        return (string) $matches[1];
    }

    return null;
}

function findFiltersFromRows(array $rows, array $properties, int $limit): array {
    $filters = array();
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        foreach ($properties as $propertyID) {
            if (isset($filters[$propertyID]) || !array_key_exists($propertyID, $row)) continue;
            $value = $row[$propertyID];
            if (is_scalar($value) && trim((string) $value) !== '') {
                $filters[$propertyID] = array('propertyID' => $propertyID, 'value' => (string) $value, 'operation' => '=');
                if (count($filters) >= $limit) return array_values($filters);
            }
        }
    }
    return array_values($filters);
}

function identifierProperties(array $type, int $maxProperties): array {
    $out = array();
    foreach (($type['propertyTypes'] ?? array()) as $propertyID => $propertyType) {
        if (strpos((string) $propertyType, 'identifier') === 0) {
            $out[] = (string) $propertyID;
            if (count($out) >= $maxProperties) break;
        }
    }
    return $out;
}

$getTypesUrl = $baseUrl . '/api/v2/items/getTypes.php';
$getUrl = $baseUrl . '/api/v2/items/get.php';
$getCountUrl = $baseUrl . '/api/v2/items/getCount.php';
$v1GetItemsUrl = $baseUrl . '/api/api_getItems.php';
$v1GetItemsCountUrl = $baseUrl . '/api/api_getItemsCount.php';
$results = array();

$getTypesCase = runCase('getTypes.all', 'GET', $getTypesUrl, $token, null, $iterations);
$results[] = $getTypesCase;
if ($getTypesCase['status'] !== 200 || $getTypesCase['phpOutput']) {
    failRemote('getTypes.all failed');
}

$types = json_decode(httpJsonRequest('GET', $getTypesUrl, $token, null)['body'], true);
if (!is_array($types)) {
    failRemote('getTypes did not return JSON array');
}

$selectedTypes = selectReadableTypes($types, $maxTypes);
foreach ($selectedTypes as $type) {
    $itemTypeID = (string) $type['itemTypeID'];
    $props = propertyIDs($type, $maxProperties);
    if (count($props) === 0) continue;

    $baseBody = array('itemTypeID' => $itemTypeID, 'propertyIDs' => $props);
    $basic = runCase('items.get.type.' . $itemTypeID . '.basic', 'GET', $getUrl, $token, $baseBody, $iterations);
    $results[] = $basic;

    $basicBody = httpJsonRequest('GET', $getUrl, $token, $baseBody)['body'];
    $rows = firstRowsFromBody($basicBody);
    $ids = array();
    foreach ($rows as $row) {
        if (isset($row['ID'])) $ids[] = (string) $row['ID'];
        if (count($ids) >= 5) break;
    }

    if (count($ids) > 0) {
        $idsBody = $baseBody;
        $idsBody['IDs'] = $ids;
        $results[] = runCase('items.get.type.' . $itemTypeID . '.ids', 'GET', $getUrl, $token, $idsBody, $iterations);
    }

    $filters = findFiltersFromRows($rows, $props, 2);
    if (count($filters) > 0) {
        $filterBody = $baseBody;
        $filterBody['filterRules'] = array($filters[0]);
        $results[] = runCase('items.get.type.' . $itemTypeID . '.filterEquals', 'GET', $getUrl, $token, $filterBody, $iterations);
        $results[] = runCase('items.count.type.' . $itemTypeID . '.filterEquals', 'GET', $getCountUrl, $token, $filterBody, $iterations);
    }

    if (count($filters) > 1) {
        $multiFilterBody = $baseBody;
        $multiFilterBody['filterRules'] = $filters;
        $results[] = runCase('items.get.type.' . $itemTypeID . '.twoFilters', 'GET', $getUrl, $token, $multiFilterBody, $iterations);
        $results[] = runCase('items.count.type.' . $itemTypeID . '.twoFilters', 'GET', $getCountUrl, $token, $multiFilterBody, $iterations);
    }

    $identifierProps = identifierProperties($type, 3);
    if (count($identifierProps) > 0) {
        // Para comparar v1 y v2 usamos exactamente las mismas propiedades base.
        // Algunas propiedades identificadoras extra aparecen en getTypes pero v1 las rechaza.
        $translateBody = array('itemTypeID' => $itemTypeID, 'propertyIDs' => $props, 'translateIDs' => true);
        $results[] = runCase('items.get.type.' . $itemTypeID . '.translateIDs', 'GET', $getUrl, $token, $translateBody, $iterations);
    }

    $v1Fields = array('propertyIDs' => implode(',', $props));
    $results[] = runFormCase('v1.api_getItems.type.' . $itemTypeID . '.basic', 'POST', $v1GetItemsUrl, $token, $v1Fields, $iterations);

    if (count($ids) > 0) {
        $v1IdsFields = $v1Fields;
        $v1IdsFields['IDs'] = implode(',', $ids);
        $results[] = runFormCase('v1.api_getItems.type.' . $itemTypeID . '.ids', 'POST', $v1GetItemsUrl, $token, $v1IdsFields, $iterations);
    }

    if (count($filters) > 0) {
        $v1Filter = $filters[0]['propertyID'] . ';' . base64_encode($filters[0]['value']) . ';' . $filters[0]['operation'];
        $v1FilterFields = $v1Fields;
        $v1FilterFields['filterRules'] = $v1Filter;
        $results[] = runFormCase('v1.api_getItems.type.' . $itemTypeID . '.filterEquals', 'POST', $v1GetItemsUrl, $token, $v1FilterFields, $iterations);
        $results[] = runFormCase('v1.api_getItemsCount.type.' . $itemTypeID . '.filterEquals', 'POST', $v1GetItemsCountUrl, $token, array('itemTypeID' => $itemTypeID, 'filterRules' => $v1Filter), $iterations);
    }

    if (count($filters) > 1) {
        $v1TwoFilters = array();
        foreach ($filters as $filter) {
            $v1TwoFilters[] = $filter['propertyID'] . ';' . base64_encode($filter['value']) . ';' . $filter['operation'];
        }
        $v1TwoFilterFields = $v1Fields;
        $v1TwoFilterFields['filterRules'] = implode(',', $v1TwoFilters);
        $results[] = runFormCase('v1.api_getItems.type.' . $itemTypeID . '.twoFilters', 'POST', $v1GetItemsUrl, $token, $v1TwoFilterFields, $iterations);
        $results[] = runFormCase('v1.api_getItemsCount.type.' . $itemTypeID . '.twoFilters', 'POST', $v1GetItemsCountUrl, $token, array('itemTypeID' => $itemTypeID, 'filterRules' => implode(',', $v1TwoFilters)), $iterations);
    }

    if (count($identifierProps) > 0) {
        // Mantener las mismas columnas que v2 hace que la comparacion de filas sea directa.
        $v1TranslateFields = array('propertyIDs' => implode(',', $props), 'translateIDs' => 'true');
        $results[] = runFormCase('v1.api_getItems.type.' . $itemTypeID . '.translateIDs', 'POST', $v1GetItemsUrl, $token, $v1TranslateFields, $iterations);
    }

    $categoryBody = array('IDs' => array($itemTypeID), 'includeCategories' => true);
    $results[] = runCase('getTypes.type.' . $itemTypeID . '.categories', 'GET', $getTypesUrl, $token, $categoryBody, $iterations);
}

$logicalFailures = array();
$resultsByCase = array();
foreach ($results as $result) {
    $resultsByCase[$result['case']] = $result;
    if ($result['status'] !== 200 || $result['phpOutput'] || $result['result'] === 'NOK' || $result['error'] !== '') {
        $logicalFailures[] = $result['case'];
    }
}

$rowMismatches = array();
foreach ($results as $result) {
    if (preg_match('/^v1\.api_getItems\.type\.([^.]+)\.(.+)$/', $result['case'], $matches) !== 1) {
        continue;
    }

    $v2Case = 'items.get.type.' . $matches[1] . '.' . $matches[2];
    if (!isset($resultsByCase[$v2Case])) {
        continue;
    }

    // La comparacion que nos interesa aqui es el numero de filas leidas.
    // El contenido puede venir en XML o JSON, pero las filas deben cuadrar.
    $v2Rows = $resultsByCase[$v2Case]['rows'];
    if ($result['rows'] !== null && $v2Rows !== null && $result['rows'] !== $v2Rows) {
        $rowMismatches[] = $result['case'] . ' rows=' . $result['rows'] . ' v2=' . $v2Rows;
    }
}

$countMismatches = array();
foreach ($results as $result) {
    if (preg_match('/^v1\.api_getItemsCount\.type\.([^.]+)\.(.+)$/', $result['case'], $matches) !== 1) {
        continue;
    }

    $v2Case = 'items.count.type.' . $matches[1] . '.' . $matches[2];
    if (!isset($resultsByCase[$v2Case])) {
        continue;
    }

    // En count comparamos el total, no la fila XML que envuelve el resultado de v1.
    $v2Count = $resultsByCase[$v2Case]['count'];
    if ($result['count'] !== null && $v2Count !== null && $result['count'] !== $v2Count) {
        $countMismatches[] = $result['case'] . ' count=' . $result['count'] . ' v2=' . $v2Count;
    }
}

printf("Remote read benchmark base=%s iterations=%d selectedTypes=%d\n", $baseUrl, $iterations, count($selectedTypes));
printf("%-42s %6s %10s %10s %10s %10s %8s %8s %8s %s\n", 'case', 'status', 'medianMs', 'minMs', 'maxMs', 'size', 'rows', 'count', 'result', 'php');
foreach ($results as $result) {
    printf(
        "%-42s %6d %10.2f %10.2f %10.2f %10d %8s %8s %8s %s\n",
        $result['case'],
        $result['status'],
        $result['medianMs'],
        $result['minMs'],
        $result['maxMs'],
        $result['size'],
        $result['rows'] === null ? '-' : (string) $result['rows'],
        $result['count'] === null ? '-' : (string) $result['count'],
        $result['result'] === null ? '-' : (string) $result['result'],
        $result['phpOutput'] ? 'yes' : 'no'
    );
}

printf("Summary failures=%d rowMismatches=%d countMismatches=%d\n", count($logicalFailures), count($rowMismatches), count($countMismatches));
foreach (array_merge($logicalFailures, $rowMismatches, $countMismatches) as $problem) {
    printf("CHECK %s\n", $problem);
}

$reportPath = getenv('RSM_REMOTE_REPORT_PATH');
if ($reportPath !== false && trim((string) $reportPath) !== '') {
    file_put_contents($reportPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
