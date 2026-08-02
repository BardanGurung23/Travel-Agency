<?php

function esewa_config(): array
{
    return [
        'product_code' => getenv('ESEWA_PRODUCT_CODE') ?: 'EPAYTEST',
        'secret_key' => getenv('ESEWA_SECRET_KEY') ?: '8gBm/:&EnhH.1/q',
        'payment_url' => getenv('ESEWA_PAYMENT_URL') ?: 'https://rc.esewa.com.np/api/epay/main/v2/form',
        'status_url' => getenv('ESEWA_STATUS_URL') ?: 'https://rc.esewa.com.np/api/epay/transaction/status/',
        'site_url' => rtrim(getenv('APP_URL') ?: esewa_detect_site_url(), '/'),
    ];
}

function esewa_detect_site_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080');
}

function esewa_sign(string $message, string $secret): string
{
    return base64_encode(hash_hmac('sha256', $message, $secret, true));
}

function esewa_request_signature(string $totalAmount, string $transactionUuid, string $productCode, string $secret): string
{
    $message = 'total_amount=' . $totalAmount
        . ',transaction_uuid=' . $transactionUuid
        . ',product_code=' . $productCode;
    return esewa_sign($message, $secret);
}

function esewa_verify_response_signature(array $response, string $secret): bool
{
    $fieldNames = array_filter(array_map('trim', explode(',', (string) ($response['signed_field_names'] ?? ''))));
    if (!$fieldNames || empty($response['signature'])) {
        return false;
    }

    $pairs = [];
    foreach ($fieldNames as $field) {
        if (!array_key_exists($field, $response) || is_array($response[$field])) {
            return false;
        }
        $pairs[] = $field . '=' . $response[$field];
    }
    return hash_equals(esewa_sign(implode(',', $pairs), $secret), (string) $response['signature']);
}

function esewa_status_lookup(string $transactionUuid, string $totalAmount): array
{
    $config = esewa_config();
    $url = $config['status_url'] . '?' . http_build_query([
        'product_code' => $config['product_code'],
        'total_amount' => $totalAmount,
        'transaction_uuid' => $transactionUuid,
    ]);

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $raw = curl_exec($curl);
    if ($raw === false) {
        $message = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException('Could not connect to eSewa: ' . $message);
    }
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $data = json_decode($raw, true);

    if ($httpCode < 200 || $httpCode >= 300 || !is_array($data)) {
        throw new RuntimeException('eSewa transaction verification failed.');
    }
    return $data;
}

function esewa_amount(string $amount): string
{
    $formatted = number_format((float) str_replace(',', '', $amount), 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}
