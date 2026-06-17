<?php
// Recebe notificacoes de mudanca de status da VenoPay (cash in / cash out)
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (is_array($data)) {
    $logLine = date('Y-m-d H:i:s') . ' ' . json_encode($data) . PHP_EOL;
    @file_put_contents(__DIR__ . '/webhook-log.txt', $logLine, FILE_APPEND);
}

http_response_code(200);
echo json_encode(['ok' => true]);
