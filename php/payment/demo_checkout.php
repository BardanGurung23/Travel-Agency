<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/payment_common.php';

$paymentId = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);

$stmt = $conn->prepare(
    "SELECT pay.*, b.customer_id, b.payment_status, b.status AS booking_status,
            p.package_name
     FROM payments pay
     JOIN bookings b ON b.booking_id=pay.booking_id
     JOIN packages p ON p.package_id=b.package_id
     WHERE pay.payment_id=? AND pay.provider='demo' AND b.customer_id=?"
);
$stmt->bind_param('ii', $paymentId, $_SESSION['customer_id']);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    http_response_code(404);
    exit('Demo payment not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!verify_payment_csrf($token)) {
        http_response_code(403);
        exit('Invalid payment confirmation.');
    }

    if (isset($_POST['complete_demo']) && $payment['status'] !== 'completed'
        && $payment['payment_status'] !== 'paid' && $payment['booking_status'] !== 'cancelled') {
        $transactionId = 'DEMO-TXN-' . strtoupper(bin2hex(random_bytes(6)));
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "UPDATE payments SET status='completed', provider_status='COMPLETED',
                    transaction_id=?, completed_at=CURRENT_TIMESTAMP, failure_message=NULL
                 WHERE payment_id=?"
            );
            $stmt->bind_param('si', $transactionId, $paymentId);
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
            'message' => 'Payment completed. Transaction: ' . $transactionId,
        ];
    } elseif (isset($_POST['cancel_demo']) && $payment['status'] !== 'completed') {
        $stmt = $conn->prepare(
            "UPDATE payments SET status='cancelled', provider_status='CANCELLED',
                failure_message='Demo payment cancelled by user.' WHERE payment_id=?"
        );
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['payment_flash'] = ['type' => 'error', 'message' => 'Demo payment was cancelled.'];
    }

    header('Location: ../user/booking_detail.php?booking_id=' . (int) $payment['booking_id']);
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo Sandbox Checkout</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; background: #f4f6fb; color: #252a34; }
        .banner { background: #fff3cd; color: #664d03; padding: 12px; text-align: center; font-weight: 700; }
        .card { width: min(480px, calc(100% - 32px)); margin: 55px auto; background: #fff; padding: 32px; border-radius: 18px; box-shadow: 0 12px 35px rgba(0,0,0,.12); }
        h1 { margin-top: 0; color: #5f3dc4; }
        .row { display: flex; justify-content: space-between; gap: 20px; padding: 12px 0; border-bottom: 1px solid #eee; }
        .total { font-size: 1.35rem; font-weight: 700; }
        .notice { background: #eef2ff; padding: 14px; border-radius: 10px; margin: 20px 0; line-height: 1.45; }
        button { width: 100%; border: 0; border-radius: 10px; padding: 14px; margin-top: 10px; font-weight: 700; cursor: pointer; }
        .pay { background: #5f3dc4; color: white; }
        .cancel { background: #e9ecef; color: #343a40; }
    </style>
</head>
<body>
 
    <div class="card">
        <h1>Payment Checkout</h1>
        <div class="row"><span>Package</span><strong><?php echo htmlspecialchars($payment['package_name']); ?></strong></div>
        <div class="row"><span>Booking</span><strong>#<?php echo (int) $payment['booking_id']; ?></strong></div>
        <div class="row total"><span>Total</span><span>Rs. <?php echo number_format($payment['amount_paisa'] / 100, 2); ?></span></div>
        <div class="notice">
            This local gateway demonstrates the complete success and cancellation workflow when third-party UAT services are unavailable.
        </div>
        <form method="post">
            <input type="hidden" name="payment_id" value="<?php echo (int) $paymentId; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(payment_csrf_token()); ?>">
            <button class="pay" type="submit" name="complete_demo">Complete Payment</button>
            <button class="cancel" type="submit" name="cancel_demo">Cancel Payment</button>
        </form>
    </div>
</body>
</html>
