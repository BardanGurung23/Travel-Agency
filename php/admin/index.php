<?php
// Start at the very top before any output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";

// Fetch statistics
$total_packages = $conn->query("SELECT COUNT(*) as count FROM packages")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as sum FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['sum'] ?? 0;

// Fetch recent bookings
$recent_bookings = $conn->query("SELECT b.*, p.package_name FROM bookings b JOIN packages p ON b.package_id = p.package_id ORDER BY b.booking_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Fetch recent customers
$recent_customers = $conn->query("SELECT * FROM customers ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Travel Agency</title>
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

        nav {
            display: flex;
            gap: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 15px;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card p {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #667eea;
            font-size: 1.8rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            nav {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            nav a {
                justify-content: center;
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
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="packages.php"><i class="fas fa-box"></i> Manage Packages</a>
                    <a href="customers.php"><i class="fas fa-users"></i> Manage Customers</a>
                    <a href="bookings.php"><i class="fas fa-calendar"></i> Manage Bookings</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome, admin</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-box"></i>
                <h3>Total Packages</h3>
                <p><?php echo $total_packages; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3>Total Customers</h3>
                <p><?php echo $total_customers; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Total Bookings</h3>
                <p><?php echo $total_bookings; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill"></i>
                <h3>Total Revenue</h3>
                <p><?php echo 'Rs. ' . number_format($total_revenue, 2); ?></p>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="section">
            <h2>Recent Bookings</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer ID</th>
                            <th>Package Name</th>
                            <th>Price</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['booking_id']; ?></td>
                                <td><?php echo $booking['customer_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['package_name']); ?></td>
                                <td>Rs. <?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
<?php 
if (!empty($booking['booking_date'])) {
    echo date('M d, Y', strtotime($booking['booking_date']));
} else {
    echo 'No date';
}
?>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Customers -->
        <div class="section">
            <h2>Recent Customers</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['customer_id']; ?></td>
                                <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($customer['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>