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

$customer_id = $_SESSION['customer_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get booking count
$booking_count_result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE customer_id = $customer_id");
$booking_count = $booking_count_result->fetch_assoc()['count'];

// Create full name from first and last name
$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';
$first_name_only = $user['first_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Travel Agency</title>
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

        .dashboard-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        .dashboard-sidebar {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: fit-content;
        }

        .profile-avatar {
            text-align: center;
            margin-bottom: 20px;
        }

        .avatar-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 15px;
        }

        .dashboard-sidebar h2 {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 5px;
            text-align: center;
        }

        .member-since {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .account-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .account-info h3 {
            color: #667eea;
            font-size: 0.9rem;
            margin-bottom: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .status-active {
            color: #10b981;
            font-weight: 600;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .dashboard-main {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .dashboard-main h2 {
            color: #667eea;
            font-size: 1.8rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 0.9rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card p {
            font-size: 2rem;
            font-weight: bold;
        }

        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .welcome-section h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .welcome-section p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            nav {
                flex-direction: column;
            }

            nav a {
                margin-left: 0;
                margin-bottom: 10px;
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
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-container">
            <!-- Sidebar -->
            <div class="dashboard-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <h2><?php echo htmlspecialchars($full_name); ?></h2>
                <p class="member-since">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>

                <div class="account-info">
                    <h3>Account Information</h3>
                    <div class="info-item">
                        <span class="info-label">Customer ID</span>
                        <span class="info-value">#<?php echo $user['customer_id']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Status</span>
                        <span class="info-value status-active"><?php echo ucfirst($user['status']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Joined Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Bookings</span>
                        <span class="info-value"><?php echo $booking_count; ?></span>
                    </div>
                </div>

                <a href="my_bookings.php" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> View My Bookings
                </a>
                <a href="profile.php" class="btn btn-primary">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <a href="change_password.php" class="btn btn-secondary">
                    <i class="fas fa-lock"></i> Change Password
                </a>
            </div>

            <!-- Main Content -->
            <div class="dashboard-main">
                <div class="welcome-section">
                    <h1>Welcome Back, <?php echo htmlspecialchars($first_name_only); ?>! 👋</h1>
                    <p>Ready for your next adventure? Explore our amazing travel packages and book your dream trip today!</p>
                </div>

                <h2><i class="fas fa-chart-bar"></i> Your Dashboard Overview</h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Total Bookings</h3>
                        <p><?php echo $booking_count; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user-circle"></i>
                        <h3>Account Status</h3>
                        <p><?php echo ucfirst($user['status']); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-envelope"></i>
                        <h3>Email</h3>
                        <p style="font-size: 0.9rem; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; margin-top: 20px;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-lightbulb"></i> Quick Tips
                    </h3>
                    <ul style="line-height: 1.8; color: #666;">
                        <li>✓ Browse our latest packages to find your perfect trip</li>
                        <li>✓ Check your bookings to see all your upcoming adventures</li>
                        <li>✓ Update your profile to keep your information current</li>
                        <li>✓ Contact us if you have any questions about our packages</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>