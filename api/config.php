<?php
// Credenciais da VenoPay — geradas em Dashboard -> API
// SUBSTITUA pelos valores reais antes de subir para produção.
define('VENOPAY_BASE_URL', 'https://venopagments.com');
define('VENOPAY_CLIENT_ID', 'np_e661d260461dc3be89d1cef2');
define('VENOPAY_CLIENT_SECRET', 'npsec_bf68f7df0b2366dc75d4c2174f0706f5e23237d60d15089d');

function venopay_request($method, $path, $body = null) {
    $ch = curl_init(VENOPAY_BASE_URL . $path);

    $headers = [
        'X-Client-Id: ' . VENOPAY_CLIENT_ID,
        'X-Client-Secret: ' . VENOPAY_CLIENT_SECRET,
        'Content-Type: application/json',
    ];

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => $error, 'http_code' => 0];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'invalid_json', 'http_code' => $httpCode];
    }

    $data['http_code'] = $httpCode;
    return $data;
}
