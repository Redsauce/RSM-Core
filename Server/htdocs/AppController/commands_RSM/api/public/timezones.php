<?php
header('Access-Control-Allow-Origin: *');
/**
 * Public API endpoint - Retrieve available IANA timezones
 * No authentication required
 * 
 * GET /api/public/timezones.php
 * GET /api/public/timezones.php?group=1
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');

try {
    // Get all available IANA timezones
    $timezones = DateTimeZone::listIdentifiers();
    sort($timezones);
    
    echo json_encode($timezones, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
