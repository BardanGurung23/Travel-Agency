<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../connection.php';

$success = '';
$error = '';

// Handle Status Update
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    
    $update_sql = "UPDATE bookings SET status=?, payment_status=? WHERE booking_id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $status, $payment_status, $booking_id);
    
    if ($stmt->execute()) {
        $success = "Booking updated successfully!";
    } else {
        $error = "Error updating booking: " . $conn->error;
    }
    $stmt->close();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];
    $delete_sql = "DELETE FROM bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $success = "Booking deleted successfully!";
    } else {
        $error = "Error deleting booking: " . $conn->error;
    }
    $stmt->close();
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = "";
if ($filter != 'all') {
    $where_clause = "WHERE b.status = '$filter'";
}

// Get all bookings with customer and package details
// Changed: c.full_name → CONCAT(c.first_name, ' ', c.last_name)
// Changed: p.title → p.package_name
$bookings_sql = "SELECT b.*, 
                 CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
                 c.email as customer_email, 
                 c.phone as customer_phone, 
                 p.package_name, 
                 p.destination, 
                 p.price 
                 FROM bookings b 
                 JOIN customers c ON b.customer_id = c.customer_id 
                 JOIN packages p ON b.package_id = p.package_id 
                 $where_clause
                 ORDER BY b.booking_date DESC";
$bookings_result = $conn->query($bookings_sql);

if (!$bookings_result) {
    $error = "Error fetching bookings: " . $conn->error;
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
    SUM(total_amount) as total_revenue
    FROM bookings";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f6fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .header-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .header-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }

        .stat-pending { border-left: 4px solid #ffc107; }
        .stat-confirmed { border-left: 4px solid #28a745; }
        .stat-cancelled { border-left: 4px solid #dc3545; }
        .stat-completed { border-left: 4px solid #17a2b8; }
        .stat-revenue { border-left: 4px solid #667eea; }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .bookings-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .bookings-table h2 {
            padding: 20px 30px;
            background: #f8f9fa;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }

        .payment-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .payment-paid { background: #d4edda; color: #155724; }
        .payment-unpaid { background: #f8d7da; color: #721c24; }
        .payment-refunded { background: #d1ecf1; color: #0c5460; }

        .action-btns {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-view:hover { background: #138496; }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete:hover { background: #c82333; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-calendar-check"></i> Manage Bookings</h1>
        <div class="header-links">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="packages.php"><i class="fas fa-box"></i> Packages</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card stat-pending">
                <h3>Pending Bookings</h3>
                <p><?php echo $stats['pending'] ?? 0; ?></p>
            </div>
            <div class="stat-card stat-confirmed">
                <h3>Confirmed Bookings</h3>
                <p><?php echo $stats['confirmed'] ?? 0; ?></p>
            </div>
            <div class="stat-card stat-completed">
                <h3>Completed Bookings</h3>
                <p><?php echo $stats['completed'] ?? 0; ?></p>
            </div>
            <div class="stat-card stat-cancelled">
                <h3>Cancelled Bookings</h3>
                <p><?php echo $stats['cancelled'] ?? 0; ?></p>
            </div>
            <div class="stat-card stat-revenue">
                <h3>Total Revenue</h3>
                <p>NPR <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <strong>Filter:</strong>
            <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?filter=confirmed" class="filter-btn <?php echo $filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
            <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="?filter=cancelled" class="filter-btn <?php echo $filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <!-- Bookings Table -->
        <div class="bookings-table">
            <h2>All Bookings (<?php echo $bookings_result ? $bookings_result->num_rows : 0; ?>)</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
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
                        <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                            <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['package_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($booking['destination']); ?></small>
                                    </td>
                                    
                                    <td><?php echo isset($booking['travel_date']) ? date('M d, Y', strtotime($booking['travel_date'])) : 'N/A'; ?></td>
                                    <td><?php echo $booking['number_of_people'] ?? 0; ?></td>
                                    <td>NPR <?php echo number_format($booking['total_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-btns">
                                        <button class="btn btn-edit" onclick="editBooking(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['status']; ?>', '<?php echo $booking['payment_status']; ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?delete=<?php echo $booking['booking_id']; ?>" class="btn btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this booking?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                                    No bookings found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Booking Status</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="booking_id" id="edit_booking_id">
                
                <div class="form-group">
                    <label>Booking Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status" id="edit_payment_status" required>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>

                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>

    <script>
        function editBooking(id, status, paymentStatus) {
            document.getElementById('edit_booking_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_payment_status').value = paymentStatus;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>