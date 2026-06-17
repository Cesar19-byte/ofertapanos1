<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

$requestNumber = $_GET['request_number'] ?? '';
if ($requestNumber === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_request_number']);
    exit;
}

$result = venopay_request('GET', '/api/consult-transaction?request_number=' . urlencode($requestNumber));

if (empty($result['ok'])) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'gateway_error']);
    exit;
}

echo json_encode(['ok' => true, 'status' => $result['status'] ?? 'pending']);
