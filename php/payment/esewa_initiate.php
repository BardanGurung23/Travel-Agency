<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/payment_common.php';
require_once __DIR__ . '/esewa.php';

$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$csrf = (string) ($_POST['csrf_token'] ?? '');
$backUrl = '../user/booking_detail.php?booking_id=' . (int) $bookingId;

if (!$bookingId || !verify_payment_csrf($csrf)) {
    $_SESSION['payment_flash'] = ['type' => 'error', 'message' => 'Invalid payment request. Please try again.'];
    header('Location: ' . $backUrl);
    exit();
}

$stmt = $conn->prepare(
    "SELECT b.booking_id, b.total_amount, b.payment_status, b.status, b.customer_id, p.package_name
     FROM bookings b JOIN packages p ON p.package_id = b.package_id
     WHERE b.booking_id = ? AND b.customer_id = ?"
);
$stmt->bind_param('ii', $bookingId, $_SESSION['customer_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking || $booking['payment_status'] === 'paid' || $booking['status'] === 'cancelled') {
    $_SESSION['payment_flash'] = ['type' => 'error', 'message' => 'This booking cannot be paid.'];
    header('Location: ' . $backUrl);
    exit();
}

$config = esewa_config();
$totalAmount = esewa_amount($booking['total_amount']);
$transactionUuid = 'BOOKING-' . $bookingId . '-' . strtoupper(bin2hex(random_bytes(6)));
$amountPaisa = (int) round(((float) $booking['total_amount']) * 100);
$signature = esewa_request_signature($totalAmount, $transactionUuid, $config['product_code'], $config['secret_key']);

try {
    $stmt = $conn->prepare(
        "INSERT INTO payments
            (booking_id, provider, purchase_order_id, amount_paisa, status, provider_status)
         VALUES (?, 'esewa', ?, ?, 'initiated', 'PENDING')"
    );
    $stmt->bind_param('isi', $bookingId, $transactionUuid, $amountPaisa);
    $stmt->execute();
    $stmt->close();
} catch (Throwable $error) {
    $_SESSION['payment_flash'] = ['type' => 'error', 'message' => 'Could not create the eSewa payment request.'];
    header('Location: ' . $backUrl);
    exit();
}

$fields = [
    'amount' => $totalAmount,
    'tax_amount' => '0',
    'total_amount' => $totalAmount,
    'transaction_uuid' => $transactionUuid,
    'product_code' => $config['product_code'],
    'product_service_charge' => '0',
    'product_delivery_charge' => '0',
    'success_url' => $config['site_url'] . '/payment/esewa_callback.php',
    'failure_url' => $config['site_url'] . '/payment/esewa_callback.php?failure=1&transaction_uuid=' . rawurlencode($transactionUuid),
    'signed_field_names' => 'total_amount,transaction_uuid,product_code',
    'signature' => $signature,
];
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Redirecting to eSewa</title></head>
<body>
    <p>Redirecting securely to eSewa sandbox…</p>
    <form id="esewa-payment" action="<?php echo htmlspecialchars($config['payment_url']); ?>" method="post">
        <?php foreach ($fields as $name => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
        <noscript><button type="submit">Continue to eSewa</button></noscript>
    </form>
    <script>document.getElementById('esewa-payment').submit();</script>
</body>
</html>
