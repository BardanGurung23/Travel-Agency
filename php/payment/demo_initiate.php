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

$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$csrf = (string) ($_POST['csrf_token'] ?? '');
$backUrl = '../user/booking_detail.php?booking_id=' . (int) $bookingId;

if (!$bookingId || !verify_payment_csrf($csrf)) {
    $_SESSION['payment_flash'] = ['type' => 'error', 'message' => 'Invalid demo payment request.'];
    header('Location: ' . $backUrl);
    exit();
}

$stmt = $conn->prepare(
    "SELECT b.booking_id, b.total_amount, b.payment_status, b.status, p.package_name
     FROM bookings b JOIN packages p ON p.package_id=b.package_id
     WHERE b.booking_id=? AND b.customer_id=?"
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

$orderId = 'DEMO-' . $bookingId . '-' . strtoupper(bin2hex(random_bytes(5)));
$amountPaisa = (int) round(((float) $booking['total_amount']) * 100);
$stmt = $conn->prepare(
    "INSERT INTO payments
        (booking_id, provider, purchase_order_id, amount_paisa, status, provider_status)
     VALUES (?, 'demo', ?, ?, 'initiated', 'AWAITING_CONFIRMATION')"
);
$stmt->bind_param('isi', $bookingId, $orderId, $amountPaisa);
$stmt->execute();
$paymentId = $stmt->insert_id;
$stmt->close();

header('Location: demo_checkout.php?payment_id=' . $paymentId);
exit();
