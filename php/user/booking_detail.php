<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";
require_once __DIR__ . '/../payment/payment_common.php';
require_once __DIR__ . '/../payment/esewa.php';

$customer_id = $_SESSION['customer_id'];
$booking_id = $_GET['booking_id'] ?? 0;

// Fetch booking details
$query = "SELECT b.*, p.package_name, p.destination, p.description, p.price, p.image_url
          FROM bookings b
          JOIN packages p ON b.package_id = p.package_id
          WHERE b.booking_id = ? AND b.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found!");
}

$payment_flash = $_SESSION['payment_flash'] ?? null;
unset($_SESSION['payment_flash']);

$payment_stmt = $conn->prepare(
    "SELECT payment_id, provider, purchase_order_id, amount_paisa,
            transaction_id, provider_status, status, initiated_at, completed_at
     FROM payments WHERE booking_id = ? ORDER BY payment_id DESC LIMIT 1"
);
$payment_stmt->bind_param("i", $booking_id);
$payment_stmt->execute();
$latest_payment = $payment_stmt->get_result()->fetch_assoc();
$payment_stmt->close();

// eSewa UAT can return its signed success callback before the status endpoint
// settles. Reconcile the latest unpaid eSewa attempt when the customer returns
// to the booking page.
if ($booking['payment_status'] !== 'paid'
    && $latest_payment
    && $latest_payment['provider'] === 'esewa'
    && $latest_payment['status'] !== 'completed') {
    try {
        $expected_amount = esewa_amount((string) $booking['total_amount']);
        $lookup = esewa_status_lookup($latest_payment['purchase_order_id'], $expected_amount);
        $lookup_status = strtoupper(trim((string) ($lookup['status'] ?? 'UNKNOWN')));
        $lookup_amount_paisa = (int) round(
            ((float) ($lookup['total_amount'] ?? $lookup['totalAmount'] ?? 0)) * 100
        );
        $reference = trim((string) ($lookup['ref_id'] ?? $lookup['refId'] ?? ''));

        if ($lookup_status === 'COMPLETE'
            && $lookup_amount_paisa === (int) $latest_payment['amount_paisa']
            && $reference !== '') {
            $conn->begin_transaction();
            $reconcile_stmt = $conn->prepare(
                "UPDATE payments SET status='completed', provider_status='COMPLETE',
                    transaction_id=?, completed_at=COALESCE(completed_at, CURRENT_TIMESTAMP),
                    failure_message=NULL WHERE payment_id=?"
            );
            $reconcile_stmt->bind_param("si", $reference, $latest_payment['payment_id']);
            $reconcile_stmt->execute();
            $reconcile_stmt->close();

            $reconcile_stmt = $conn->prepare(
                "UPDATE bookings SET payment_status='paid',
                    status=IF(status='pending', 'confirmed', status) WHERE booking_id=?"
            );
            $reconcile_stmt->bind_param("i", $booking_id);
            $reconcile_stmt->execute();
            $reconcile_stmt->close();
            $conn->commit();

            $booking['payment_status'] = 'paid';
            if ($booking['status'] === 'pending') {
                $booking['status'] = 'confirmed';
            }
            $latest_payment['status'] = 'completed';
            $latest_payment['provider_status'] = 'COMPLETE';
            $latest_payment['transaction_id'] = $reference;
            $payment_flash = [
                'type' => 'success',
                'message' => 'eSewa payment verified. Transaction: ' . $reference,
            ];
        }
    } catch (Throwable $reconcile_error) {
        if ($conn->errno) {
            $conn->rollback();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Travel Agency</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            color: #333;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background: rgba(255,255,255,0.15);
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .back-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: white;
        }

        .detail-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .package-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: #f0f0f0;
        }

        .detail-content {
            padding: 30px;
        }

        .detail-content h2 {
            color: #667eea;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .destination {
            color: #999;
            font-size: 1.1rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h3 {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
        }

        .sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 30px;
            height: fit-content;
        }

        .sidebar h3 {
            color: #667eea;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
        }

        .payment-paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .price-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .payment-message {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .payment-message.success { background: #d4edda; color: #155724; }
        .payment-message.error { background: #f8d7da; color: #721c24; }
        .btn-esewa { background: #60bb46; color: white; }
        .btn-demo { background: #343a40; color: white; }
        .payment-note { color: #666; font-size: .85rem; margin: 8px 0 16px; line-height: 1.4; }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .price-row:last-child {
            margin-bottom: 0;
        }

        .total-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 2px solid #ddd;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .description {
            color: #666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .detail-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-globe-asia"></i>
                    <span>Travel Agency</span>
                </div>
                <nav>
                    <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="../packages.php"><i class="fas fa-suitcase"></i> Packages</a>
                    <a href="my_bookings.php"><i class="fas fa-calendar"></i> My Bookings</a>
                    <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="page-header">
        <div class="container">
            <a href="my_bookings.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to My Bookings
            </a>
            <h1>Booking Details</h1>
        </div>
    </div>

    <div class="container">
        <div class="detail-container">
            <!-- Main Details -->
            <div class="detail-card">
                <?php if ($booking['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['package_name']); ?>" class="package-image">
                <?php else: ?>
                    <div class="package-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 3rem;">
                        <i class="fas fa-image"></i>
                    </div>
                <?php endif; ?>

                <div class="detail-content">
                    <h2><?php echo htmlspecialchars($booking['package_name']); ?></h2>
                    <div class="destination">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($booking['destination']); ?>
                    </div>
                    <div class="detail-section">
                        <h3>Booking Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Booking ID</span>
                            <span class="detail-value">#<?php echo $booking['booking_id']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Travel Date</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Number of People</span>
                            <span class="detail-value"><?php echo $booking['number_of_people']; ?> Person(s)</span>
                        </div>
                        <div class="detail-row">
                            
                        </div>
                    </div>
                    

                    

                    <div class="detail-section">
                        <h3>Package Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Price per Person</span>
                            <span class="detail-value">Rs. <?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                    </div>

                    <?php if ($booking['special_requests']): ?>
                        <div class="detail-section">
                            <h3>Special Requests</h3>
                            <p class="description"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($booking['description']): ?>
                        <div class="detail-section">
                            <h3>Package Description</h3>
                            <p class="description"><?php echo htmlspecialchars($booking['description']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <h3>Summary</h3>

                <?php if ($payment_flash): ?>
                    <div class="payment-message <?php echo $payment_flash['type'] === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($payment_flash['message']); ?>
                    </div>
                <?php endif; ?>

                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                    Status: <?php echo ucfirst($booking['status']); ?>
                </span>

                <span class="payment-badge payment-<?php echo ($booking['payment_status'] == 'paid') ? 'paid' : 'unpaid'; ?>">
                    Payment: <?php echo ucfirst($booking['payment_status']); ?>
                </span>

                <div class="price-section">
                    <div class="price-row">
                        <span>Price per Person:</span>
                        <span>Rs. <?php echo number_format($booking['price'], 2); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Number of People:</span>
                        <span><?php echo $booking['number_of_people']; ?></span>
                    </div>
                    <div class="total-price">
                        <span>Total Amount:</span>
                        <span>Rs. <?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                </div>

                <?php if ($booking['payment_status'] !== 'paid' && strtolower($booking['status']) !== 'cancelled'): ?>
                    <form method="post" action="../payment/esewa_initiate.php">
                        <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(payment_csrf_token()); ?>">
                        <button type="submit" class="btn btn-esewa">
                            <i class="fas fa-wallet"></i> Pay with eSewa
                        </button>
                    </form>
                    <form method="post" action="../payment/demo_initiate.php">
                        <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(payment_csrf_token()); ?>">
                        <button type="submit" class="btn btn-demo">
                         Pay
                        </button>
                    </form>
                    <p class="payment-note">Secure sandbox checkout. Your booking is confirmed only after the selected provider verifies the payment.</p>
                <?php elseif ($booking['payment_status'] === 'paid' && $latest_payment): ?>
                    <p class="payment-note">
                        <?php echo htmlspecialchars(ucfirst($latest_payment['provider'] ?? 'Payment')); ?> transaction:
                        <strong><?php echo htmlspecialchars($latest_payment['transaction_id'] ?? 'Verified'); ?></strong>
                    </p>
                <?php endif; ?>

                <?php if (strtolower($booking['status']) !== 'cancelled'): ?>
                    <a href="cancel_booking.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">
                        <i class="fas fa-times"></i> Cancel Booking
                    </a>
                <?php endif; ?>

                <a href="my_bookings.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
