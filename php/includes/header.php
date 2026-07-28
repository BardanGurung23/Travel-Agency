<?php
// Include database connection
//require_once __DIR__ . '/../includes/connection.php';
require_once __DIR__ . '/../connection.php';  
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple helper functions
function isLoggedIn() {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Travel Agency Management System'; ?></title>
    <link rel="stylesheet" href="../css/style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>🌍 Travel Agency</h1>
                </div>
                <nav>
                    <ul>
                        <!-- Home -->
                        <li><a href="/project/php/index.php">Home</a></li>

                        <!-- Packages -->
                        <li><a href="/project/php/packages.php">Packages</a></li>

                        <?php if (isLoggedIn()): ?>

                            <!-- Customer pages -->
                            <li><a href="/project/php/user/my_bookings.php">My Bookings</a></li>
                            <li><a href="/project/php/user/user_dashboard.php">Dashboard</a></li>
                            <li><a href="/project/php/logout.php">Logout</a></li>

                        <?php elseif (isAdminLoggedIn()): ?>

                            <!-- Admin pages -->
                            <li><a href="/project/php/admin/dashboard.php">Dashboard</a></li>
                            <li><a href="/project/php/admin/packages.php"><i class="fas fa-box"></i> Manage Packages</a></li>
                            <li><a href="/project/php/admin/customers.php"><i class="fas fa-users"></i> Manage Customers</a></li>
                            <li><a href="/project/php/admin/bookings.php"><i class="fas fa-calendar"></i> Manage Bookings</a></li>
                            <li><a href="/project/php/logout.php">Logout</a></li>

                        <?php else: ?>

                            <!-- Auth pages -->
                            <li><a href="/project/php/login.php">Login</a></li>
                            <li><a href="/project/php/register.php">Register</a></li>

                        <?php endif; ?>
                    </ul>
                </nav>

            </div>
        </div>
    </header>