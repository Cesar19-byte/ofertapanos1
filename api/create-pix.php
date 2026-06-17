<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_request']);
    exit;
}

$name        = trim((string)($input['name'] ?? ''));
$email       = trim((string)($input['email'] ?? ''));
$document    = preg_replace('/\D/', '', (string)($input['document'] ?? ''));
$cep         = preg_replace('/\D/', '', (string)($input['cep'] ?? ''));
$endereco    = trim((string)($input['endereco'] ?? ''));
$numero      = trim((string)($input['numero'] ?? ''));
$complemento = trim((string)($input['complemento'] ?? ''));
$bairro      = trim((string)($input['bairro'] ?? ''));
$cidadeUf    = trim((string)($input['cidade_uf'] ?? ''));
$kit         = (string)($input['kit'] ?? '');
$model       = (string)($input['model'] ?? '');
$desconto    = !empty($input['desconto']);

// Precos fixos no servidor - nunca confiar no valor enviado pelo cliente
$precos = [
    '10' => 14.67,
    '20' => 24.67,
    '30' => 34.67,
    '50' => 49.67,
];
$precosDesconto = [
    '10' => 13.67,
    '20' => 21.67,
    '30' => 30.67,
    '50' => 41.67,
];

if (strlen($name) < 3) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'nome_invalido']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'email_invalido']);
    exit;
}
if (strlen($document) !== 11) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'cpf_invalido']);
    exit;
}
if (strlen($cep) !== 8) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'cep_invalido']);
    exit;
}
if ($endereco === '' || $numero === '' || $bairro === '' || $cidadeUf === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'endereco_invalido']);
    exit;
}
if (!isset($precos[$kit])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'kit_invalido']);
    exit;
}

$amount = $desconto ? $precosDesconto[$kit] : $precos[$kit];
$modelLabel = $model === 'atoalhado' ? 'Atoalhado' : 'Pe de Galinha';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$webhookUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/api/webhook.php';

$body = [
    'amount'      => $amount,
    'name'        => $name,
    'document'    => $document,
    'description' => 'Kit ' . $kit . ' Panos de Prato - ' . $modelLabel,
    'webhook_url' => $webhookUrl,
];

$result = venopay_request('POST', '/api/cashin', $body);

// LOG TEMPORARIO DE DEBUG - remover depois de confirmar os nomes dos campos do QR code
@file_put_contents(__DIR__ . '/debug-pix.txt', date('Y-m-d H:i:s') . ' ' . json_encode($result) . PHP_EOL, FILE_APPEND);

if (empty($result['ok'])) {
    http_response_code(502);
    echo json_encode([
        'ok'    => false,
        'error' => 'gateway_error',
        'message' => $result['message'] ?? $result['error'] ?? 'Falha ao gerar cobranca PIX',
    ]);
    exit;
}

// Guarda os dados do pedido (endereco de entrega) associados ao request_number,
// para conferencia na hora de despachar apos a confirmacao do pagamento.
$order = [
    'created_at'     => date('Y-m-d H:i:s'),
    'request_number' => $result['request_number'] ?? null,
    'transaction_id' => $result['transaction_id'] ?? null,
    'kit'            => $kit,
    'model'          => $modelLabel,
    'amount'         => $amount,
    'name'           => $name,
    'email'          => $email,
    'document'       => $document,
    'cep'            => $cep,
    'endereco'       => $endereco,
    'numero'         => $numero,
    'complemento'    => $complemento,
    'bairro'         => $bairro,
    'cidade_uf'      => $cidadeUf,
];
@file_put_contents(__DIR__ . '/orders-log.txt', json_encode($order) . PHP_EOL, FILE_APPEND);

// Tenta varios nomes de campo possiveis para o QR code e o codigo copia-e-cola,
// ja que cada gateway PIX nomeia esses campos de um jeito diferente.
$qrImg = $result['qr_img']
    ?? $result['qrcode']
    ?? $result['qr_code']
    ?? $result['qrCode']
    ?? $result['qrcode_image']
    ?? $result['qr_code_image']
    ?? $result['qrCodeImage']
    ?? $result['qrcodeBase64']
    ?? $result['qr_code_base64']
    ?? null;

if ($qrImg && strpos($qrImg, 'data:image') !== 0 && strpos($qrImg, 'http') !== 0) {
    // Valor parece ser base64 puro, sem o prefixo data URI - adiciona o prefixo.
    $qrImg = 'data:image/png;base64,' . $qrImg;
}

$copyPaste = $result['copyPaste']
    ?? $result['copy_paste']
    ?? $result['code']
    ?? $result['pix_code']
    ?? $result['pixCode']
    ?? $result['emv']
    ?? $result['brcode']
    ?? $result['qrcode_text']
    ?? $result['qr_code_text']
    ?? null;

echo json_encode([
    'ok'             => true,
    'qr_img'         => $qrImg,
    'copyPaste'      => $copyPaste,
    'request_number' => $result['request_number'] ?? null,
    'amount'         => $amount,
]);
