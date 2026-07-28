<?php
// index.php - FINAL UPDATED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once __DIR__ . "/connection.php";

// Get packages
$query = "SELECT * FROM packages ORDER BY package_id DESC LIMIT 4";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Travel Agency</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Body */
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
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

/* Hero Section */
.hero {
    background: linear-gradient(rgba(102, 126, 234, 0.7), rgba(118, 75, 162, 0.7)), 
                url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: white;
    text-align: center;
    padding: 6rem 20px;
    margin-bottom: 3rem;
    border-radius: 0 0 20px 20px;
}

.hero h1, .hero p {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.btn {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: bold;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(118, 75, 162, 0.4);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(118, 75, 162, 0.6);
}

/* Packages Section */
.section-title {
    text-align: center;
    margin-bottom: 2rem;
    color: white;
}

.section-title h2 {
    font-size: 2.2rem;
    margin-bottom: 0.5rem;
    position: relative;
    display: inline-block;
}

.section-title h2::after {
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

.section-title p {
    color: #e0e0e0;
    font-size: 1rem;
}

.packages-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-bottom: 4rem;
    padding: 0 20px;
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

.package-destination {
    display: flex;
    align-items: center;
    color: #666;
    margin-bottom: 0.8rem;
    font-size: 0.9rem;
}

.package-destination i {
    color: #667eea;
    margin-right: 6px;
}

.package-description {
    color: #555;
    font-size: 0.85rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.package-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #777;
    margin-bottom: 1rem;
}

.package-price {
    font-size: 1.3rem;
    color: #764ba2;
    font-weight: bold;
    text-align: center;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #f0f0ff 0%, #e6e0ff 100%);
    padding: 0.6rem;
    border-radius: 10px;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.2);
}

.empty-state i {
    font-size: 4rem;
    color: #764ba2;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #764ba2;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #666;
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

/* Footer */
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

/* Responsive */
@media (max-width: 768px) {
    .packages-grid {
        grid-template-columns: 1fr;
    }
    .hero h1 {
        font-size: 2rem;
    }
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    nav a {
        margin-left: 0.5rem;
        margin-right: 0.5rem;
    }
    .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
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
    </nav>
</div>
</div>
</header>

<!-- Hero Section -->
<section class="hero">
<div class="container">
<h1>Discover the Beauty of Nepal</h1>
<p>Experience breathtaking Himalayan adventures, ancient cultural heritage, and unforgettable journeys through the land of Everest and Buddha.</p>
<a href="packages.php" class="btn">
<i class="fas fa-compass"></i> Explore All Packages
</a>
</div>
</section>

<!-- Featured Packages -->
<section class="container">
<div class="section-title">
<h2>Featured Tour Packages</h2>
<p>Handpicked experiences curated for your perfect Himalayan adventure</p>
</div>

<div class="packages-grid">
<?php if ($result->num_rows > 0): ?>
    <?php while($package = $result->fetch_assoc()): ?>
    <div class="package-card">
        <div class="package-badge"><i class="fas fa-tag"></i> Popular</div>
        <img src="<?php echo !empty($package['image_url']) ? htmlspecialchars($package['image_url']) : 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'; ?>" alt="<?php echo htmlspecialchars($package['package_name']); ?>" class="package-image">
        <div class="package-content">
            <h3><?php echo htmlspecialchars($package['package_name']); ?></h3>
            <div class="package-destination"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($package['destination'] ?? 'Nepal'); ?></div>
            <p class="package-description"><?php $desc = $package['description'] ?? ''; echo htmlspecialchars(substr($desc, 0, 120)) . (strlen($desc) > 120 ? '...' : ''); ?></p>
            <div class="package-details">
                <div>Duration: <?php echo $package['duration_days'] ?? 1; ?> days</div>
                <div>From: <?php echo $package['available_from'] ?? 'Flexible'; ?></div>
                <div>To: <?php echo $package['available_to'] ?? 'Flexible'; ?></div>
            </div>
            <div class="package-price">Rs. <?php echo number_format($package['price'], 2); ?><br><small>per person</small></div>
            <a href="package_details.php?id=<?php echo $package['package_id']; ?>" class="btn" style="width:100%; text-align:center;"><i class="fas fa-info-circle"></i> View Details & Book</a>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
<div class="empty-state">
<i class="fas fa-box-open"></i>
<h3>No Packages Available</h3>
<p>We're currently updating our travel packages. Please check back soon or contact us for custom tour arrangements.</p>
<a href="update_packages_data.php" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);"><i class="fas fa-plus-circle"></i> Add Sample Packages</a>
</div>
<?php endif; ?>
</div>
</section>

<!-- Footer -->
<footer>
<div class="container">
<div class="footer-content">
    <div class="logo"><i class="fas fa-globe-asia"></i> Travel Agency</div>
    <div style="text-align:right;"><p><i class="fas fa-envelope"></i> contact@travelagency.com<br><i class="fas fa-phone"></i> +977 1-1234567</p></div>
</div>
<div class="copyright">
<p>&copy; 2026 Travel Agency Management System. All rights reserved. Developed by Yasmin Haq</p>
</div>
</div>
</footer>
</body>
</html>
<?php $conn->close(); ?>
