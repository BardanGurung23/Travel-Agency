<?php
// user/my_bookings.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";

// Fetch customer bookings
$customer_id = $_SESSION['customer_id'];
$query = "SELECT b.*, p.package_name, p.destination, p.image_url 
          FROM bookings b
          JOIN packages p ON b.package_id = p.package_id
          WHERE b.customer_id = ?
          ORDER BY b.booking_date DESC";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Travel Agency</title>
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
            transform: translateY(-2px);
        }
        
        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin: 30px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .dashboard-header h1 {
            color: #764ba2;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .bookings-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .bookings-container h2 {
            color: #667eea;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bookings-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .bookings-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .bookings-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .payment-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .payment-unpaid {
            background-color: #ffe6e6;
            color: #dc3545;
        }
        
        .payment-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .payment-refunded {
            background-color: #e6f7ff;
            color: #0066cc;
        }
        
        .no-bookings {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        .no-bookings i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }
        
        .btn-view {
            background: #28a745;
        }
        
        .btn-cancel {
            background: #dc3545;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 50px;
        }
        
        .copyright {
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .bookings-table {
                display: block;
                overflow-x: auto;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav a {
                margin: 5px;
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
                    <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h1>My Bookings</h1>
            <p>View and manage all your travel bookings</p>
        </div>
        
        <div class="bookings-container">
            <h2>Booking History</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Package</th>
                            <th>Travel Date</th>
                            <th>People</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['booking_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['package_name'] ?? 'N/A'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($booking['destination'] ?? 'N/A'); ?></small>
                                </td>
                                <td><?php echo isset($booking['travel_date']) ? date('M d, Y', strtotime($booking['travel_date'])) : 'N/A'; ?></td>
                                <td><?php echo $booking['number_of_people'] ?? 0; ?></td>
                                <td>Rs. <?php echo number_format($booking['total_amount'] ?? 0, 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-status payment-<?php echo $booking['payment_status'] ?? 'unpaid'; ?>">
                                        <?php echo ucfirst($booking['payment_status'] ?? 'unpaid'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="booking_detail.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="cancel_booking.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-cancel"
                                               onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any bookings yet. Explore our packages and start your adventure!</p>
                    <a href="../packages.php" class="btn" style="margin-top: 20px;">
                        <i class="fas fa-suitcase"></i> Browse Packages
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="copyright">
                <p>&copy; 2024 Travel Agency Management System. All rights reserved. Developed by Yasmin Haq</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>