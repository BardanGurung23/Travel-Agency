<?php
session_start();

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/esewa.php';

$transactionUuid = trim((string) ($_GET['transaction_uuid'] ?? ''));
$encodedData = trim((string) ($_GET['data'] ?? ''));
$response = null;

if ($encodedData !== '') {
    $decoded = base64_decode($encodedData, true);
    $response = $decoded === false ? null : json_decode($decoded, true);
    if (is_array($response) && !empty($response['transaction_uuid'])) {
        $transactionUuid = (string) $response['transaction_uuid'];
    }
}
if ($transactionUuid === '') {
    http_response_code(400);
    exit('Missing eSewa transaction identifier.');
}

$stmt = $conn->prepare(
    "SELECT pay.*, b.customer_id
     FROM payments pay JOIN bookings b ON b.booking_id = pay.booking_id
     WHERE pay.provider = 'esewa' AND pay.purchase_order_id = ?"
);
$stmt->bind_param('s', $transactionUuid);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    http_response_code(404);
    exit('Payment record not found.');
}

$bookingUrl = '../user/booking_detail.php?booking_id=' . (int) $payment['booking_id'];
$config = esewa_config();
$expectedAmount = number_format(((int) $payment['amount_paisa']) / 100, 2, '.', '');

try {
    if (isset($_GET['failure'])) {
        $lookup = esewa_status_lookup($transactionUuid, $expectedAmount);
        $providerStatus = strtoupper((string) ($lookup['status'] ?? 'FAILED'));
        $localStatus = $providerStatus === 'PENDING' ? 'pending' : 'failed';
        $message = 'eSewa payment status: ' . $providerStatus;
    } else {
        if (!is_array($response) || !esewa_verify_response_signature($response, $config['secret_key'])) {
            throw new RuntimeException('The eSewa response signature is invalid.');
        }
        $responseProductCode = trim((string) ($response['product_code'] ?? ''));
        $responseUuid = trim((string) ($response['transaction_uuid'] ?? ''));
        $responseStatus = strtoupper(trim((string) ($response['status'] ?? '')));
        $responseAmountPaisa = (int) round(
            ((float) str_replace(',', '', (string) ($response['total_amount'] ?? '0'))) * 100
        );

        $mismatches = [];
        if ($responseProductCode !== trim($config['product_code'])) {
            $mismatches[] = 'product code';
        }
        if ($responseUuid !== trim($transactionUuid)) {
            $mismatches[] = 'transaction ID';
        }
        if ($responseAmountPaisa !== (int) $payment['amount_paisa']) {
            $mismatches[] = 'amount';
        }
        if ($responseStatus !== 'COMPLETE') {
            $mismatches[] = 'status (' . ($responseStatus ?: 'missing') . ')';
        }
        if ($mismatches) {
            throw new RuntimeException(
                'The eSewa response has a mismatched ' . implode(', ', $mismatches) . '.'
            );
        }

        $lookup = esewa_status_lookup($transactionUuid, $expectedAmount);
        $providerStatus = strtoupper((string) ($lookup['status'] ?? 'UNKNOWN'));
        for ($attempt = 0; $attempt < 15 && $providerStatus === 'PENDING'; $attempt++) {
            sleep(1);
            $lookup = esewa_status_lookup($transactionUuid, $expectedAmount);
            $providerStatus = strtoupper((string) ($lookup['status'] ?? 'UNKNOWN'));
        }
        $lookupAmount = esewa_amount((string) ($lookup['totalAmount'] ?? $lookup['total_amount'] ?? '0'));
        if ($providerStatus !== 'COMPLETE' || $lookupAmount !== $expectedAmount) {
            throw new RuntimeException('eSewa has not verified this payment as complete.');
        }

        $transactionCode = (string) ($response['transaction_code'] ?? $lookup['ref_id'] ?? $lookup['refId'] ?? '');
        if ($transactionCode === '') {
            throw new RuntimeException('eSewa did not return a transaction code.');
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "UPDATE payments SET status='completed', provider_status=?, transaction_id=?,
                    completed_at=COALESCE(completed_at, CURRENT_TIMESTAMP), failure_message=NULL
                 WHERE payment_id=?"
            );
            $stmt->bind_param('ssi', $providerStatus, $transactionCode, $payment['payment_id']);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare(
                "UPDATE bookings SET payment_status='paid',
                    status=IF(status='pending', 'confirmed', status) WHERE booking_id=?"
            );
            $stmt->bind_param('i', $payment['booking_id']);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
        } catch (Throwable $error) {
            $conn->rollback();
            throw $error;
        }
        $_SESSION['payment_flash'] = [
            'type' => 'success',
            'message' => 'eSewa payment completed successfully. Transaction: ' . $transactionCode,
        ];
        $message = null;
    }

    if (isset($message)) {
        $stmt = $conn->prepare(
            "UPDATE payments SET status=?, provider_status=?, failure_message=? WHERE payment_id=?"
        );
        $stmt->bind_param('sssi', $localStatus, $providerStatus, $message, $payment['payment_id']);
        $stmt->execute();
        $stmt->close();
        $_SESSION['payment_flash'] = ['type' => 'error', 'message' => $message];
    }
} catch (Throwable $error) {
    $message = $error->getMessage();
    $stmt = $conn->prepare("UPDATE payments SET status='failed', failure_message=? WHERE payment_id=?");
    $stmt->bind_param('si', $message, $payment['payment_id']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['payment_flash'] = ['type' => 'error', 'message' => $message];
}

if (!isset($_SESSION['customer_id']) || (int) $_SESSION['customer_id'] !== (int) $payment['customer_id']) {
    header('Location: ../login.php');
    exit();
}
header('Location: ' . $bookingUrl);
exit();
