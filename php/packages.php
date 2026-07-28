<?php
// packages.php - CORRECTED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "connection.php";

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$sql = "SELECT * FROM packages ORDER BY package_id DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Tour Packages</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Body */
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
}

/* Header */
header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 1000;
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

/* Page Title */
.page-title {
    text-align: center;
    margin: 3rem 0 2rem 0;
    color: white;
}

.page-title h2 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    position: relative;
    display: inline-block;
}

.page-title h2::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

.packages-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    padding: 0 20px;
    margin-bottom: 4rem;
}

.package-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 50px rgba(0,0,0,0.2);
    transition: all 0.4s ease;
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
}

.package-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.package-card:hover .package-image {
    transform: scale(1.05);
}

.package-content {
    padding: 1.5rem;
    position: relative;
}

.package-badge {
    position: absolute;
    top: -10px;
    right: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
}

.package-content h3 {
    color: #764ba2;
    margin-bottom: 0.8rem;
    font-size: 1.3rem;
    transition: color 0.3s ease;
}

.package-content h3:hover {
    color: #667eea;
}

.package-destination, .package-details p {
    font-size: 0.9rem;
    margin: 0.3rem 0;
    color: #555;
}

.package-price {
    font-size: 1.3rem;
    color: #764ba2;
    font-weight: bold;
    text-align: center;
    margin: 1rem 0;
    background: linear-gradient(135deg, #f0f0ff 0%, #e6e0ff 100%);
    padding: 0.6rem;
    border-radius: 10px;
}

.btn {
    display: inline-block;
    width: 100%;
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.8rem 1rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(118, 75, 162, 0.6);
}

footer {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0 2rem;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.footer-content .logo {
    font-size: 1.5rem;
}

.footer-content p {
    margin: 0;
    color: rgba(255,255,255,0.8);
}

.copyright {
    text-align: center;
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
}

@media (max-width:768px){
    .packages-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<!-- Header -->
<header>
<div class="container">
<div class="header-content">
    <div class="logo">
        <i class="fas fa-globe-asia"></i>
        <span>Travel Agency</span>
    </div>
    <nav>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="packages.php"><i class="fas fa-suitcase"></i> Packages</a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
        <!-- Add admin link -->
        <a href="admin/login.php" style="background: rgba(255,255,255,0.1);">
            <i class="fas fa-crown"></i> Admin
        </a>
    </nav>
</div>
</div>
</header>

<div class="container">
    <div class="page-title">
        <h2>All Tour Packages</h2>
    </div>

    <div class="packages-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="package-card">
                    <div class="package-badge"><i class="fas fa-tag"></i> Popular</div>
                    <img src="<?= !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80' ?>" alt="<?= htmlspecialchars($row['package_name'] ?? 'Package Image') ?>" class="package-image">
                    <div class="package-content">
                        <h3><?= htmlspecialchars($row['package_name'] ?? 'No Title') ?></h3>
                        <div class="package-destination">📍 Destination: <?= htmlspecialchars($row['destination'] ?? 'N/A') ?></div>
                        <div class="package-details">⏱ Duration: <?= htmlspecialchars($row['duration_days'] ?? 0) ?> days</div>
                        <div class="package-details">📅 From: <?= htmlspecialchars($row['available_from'] ?? '-') ?></div>
                        <div class="package-details">📅 To: <?= htmlspecialchars($row['available_to'] ?? '-') ?></div>
                        <div class="package-price">Rs. <?= number_format($row['price'] ?? 0) ?></div>
                        <a href="package_details.php?id=<?= $row['package_id'] ?? 0 ?>" class="btn"><i class="fas fa-info-circle"></i> View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="package-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                <i class="fas fa-box-open" style="font-size: 3rem; color: #764ba2; margin-bottom: 1rem;"></i>
                <h3>No Packages Available</h3>
                <p>We're currently updating our travel packages. Please check back soon.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer>
<div class="container">
<div class="footer-content">
    <div class="logo"><i class="fas fa-globe-asia"></i> Travel Agency</div>
    <div style="text-align:right;"><p><i class="fas fa-envelope"></i> contact@travelagency.com<br><i class="fas fa-phone"></i> +977 1-1234567</p></div>
</div>
<div class="copyright">
<p>&copy; 2024 Travel Agency Management System. All rights reserved. Developed by Yasmin Haq</p>
</div>
</div>
</footer>
</body>
</html>
<?php $conn->close(); ?>