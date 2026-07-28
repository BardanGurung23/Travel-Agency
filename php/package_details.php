<?php
// package_details.php - CORRECTED VERSION
session_start();
include "connection.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: packages.php");
    exit();
}

$package_id = intval($_GET['id']);

// Fetch package details
$stmt = $conn->prepare("SELECT * FROM packages WHERE package_id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();

if (!$package) {
    header("Location: packages.php");
    exit();
}

// Set defaults to avoid errors
$package['package_name'] = $package['package_name'] ?? 'Unknown Package';
$package['destination'] = $package['destination'] ?? 'Unknown';
$package['description'] = $package['description'] ?? 'No description available';
$package['price'] = $package['price'] ?? 0;
$package['duration_days'] = $package['duration_days'] ?? 1;
$package['available_slots'] = $package['available_slots'] ?? 0;
$package['available_from'] = $package['available_from'] ?? date('Y-m-d');
$package['available_to'] = $package['available_to'] ?? date('Y-m-d', strtotime('+30 days'));
$package['status'] = $package['status'] ?? 'inactive';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($package['package_name']); ?> - Travel Agency</title>
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
        
        .package-details {
            padding: 40px 0;
        }
        
        .package-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .package-header h1 {
            color: #764ba2;
            margin-bottom: 10px;
            font-size: 2.2rem;
        }
        
        .package-meta {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
        }
        
        .meta-item i {
            color: #667eea;
        }
        
        .package-price {
            font-size: 2rem;
            color: #764ba2;
            font-weight: bold;
            background: linear-gradient(135deg, #f0f0ff 0%, #e6e0ff 100%);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
        }
        
        .package-description {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .package-description h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .package-description p {
            line-height: 1.8;
            color: #555;
            margin-bottom: 15px;
        }
        
        .booking-form-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .booking-form-container h2 {
            color: #667eea;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(118, 75, 162, 0.6);
        }
        
        .login-prompt {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        
        .login-prompt a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-prompt a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #ffe6e6;
            color: #dc3545;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 50px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .copyright {
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .package-meta {
                flex-direction: column;
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
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="packages.php"><i class="fas fa-suitcase"></i> Packages</a>
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="user/user_dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php else: ?>
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                    <?php endif; ?>
                    <!-- Add admin link -->
                    <a href="admin/login.php" style="background: rgba(255,255,255,0.1);">
                        <i class="fas fa-crown"></i> Admin
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="package-details">
            <div class="package-header">
                <h1><?php echo htmlspecialchars($package['package_name']); ?></h1>
                <p style="color: #666; font-size: 1.1rem; margin-bottom: 15px;"><?php echo htmlspecialchars($package['destination']); ?></p>
                
                <div class="package-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo htmlspecialchars($package['duration_days']); ?> Days</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo htmlspecialchars($package['available_slots']); ?> Slots Available</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>From: <?php echo date('M d, Y', strtotime($package['available_from'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>To: <?php echo date('M d, Y', strtotime($package['available_to'])); ?></span>
                    </div>
                </div>
                
                <div class="package-price">
                    Rs. <?php echo number_format($package['price'], 2); ?> 
                    <span style="font-size: 1rem; color: #666;">per person</span>
                </div>
            </div>
            
            <div class="package-description">
                <h2>Package Description</h2>
                <p><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
                
                <?php if ($package['available_slots'] > 0 && $package['status'] == 'active'): ?>
                    <p style="color: #28a745; font-weight: 500; margin-top: 20px;">
                        <i class="fas fa-check-circle"></i> This package is available for booking!
                    </p>
                <?php else: ?>
                    <p style="color: #dc3545; font-weight: 500; margin-top: 20px;">
                        <i class="fas fa-times-circle"></i> This package is currently <?php echo $package['status'] == 'inactive' ? 'inactive' : 'sold out'; ?>.
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="booking-form-container">
                <h2>Book This Package</h2>
                
                <?php if (!isset($_SESSION['customer_id'])): ?>
                    <div class="login-prompt">
                        <p>You need to login to book this package.</p>
                        <a href="login.php">Click here to login</a> or 
                        <a href="register.php">register if you don't have an account</a>
                    </div>
                <?php elseif ($package['available_slots'] <= 0 || $package['status'] != 'active'): ?>
                    <div class="login-prompt" style="background: #ffe6e6; border-left-color: #dc3545;">
                        <p>Sorry, this package is currently <?php echo $package['status'] == 'inactive' ? 'inactive' : 'sold out'; ?>. Please check back later or explore other packages.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $success = '';
                    $error = '';
                    
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_now'])) {
                        $number_of_people = intval($_POST['number_of_people']);
                        $travel_date = $_POST['travel_date'];
                        $special_requests = $conn->real_escape_string($_POST['special_requests']);
                        
                        // Validate travel date is within package date range
                        if (strtotime($travel_date) < strtotime($package['available_from']) || 
                            strtotime($travel_date) > strtotime($package['available_to'])) {
                            $error = "Travel date must be between " . date('M d, Y', strtotime($package['available_from'])) . 
                                     " and " . date('M d, Y', strtotime($package['available_to']));
                        } elseif ($number_of_people > $package['available_slots']) {
                            $error = "Only " . $package['available_slots'] . " slots available!";
                        } else {
                            $total_amount = $number_of_people * $package['price'];
                            
                            // Insert booking
                            $stmt = $conn->prepare("INSERT INTO bookings (customer_id, package_id, number_of_people, total_amount, travel_date, special_requests, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
                            $stmt->bind_param("iiidss", $_SESSION['customer_id'], $package_id, $number_of_people, $total_amount, $travel_date, $special_requests);
                            
                            if ($stmt->execute()) {
                                // Update available slots
                                $new_slots = $package['available_slots'] - $number_of_people;
                                $update_stmt = $conn->prepare("UPDATE packages SET available_slots = ? WHERE package_id = ?");
                                $update_stmt->bind_param("ii", $new_slots, $package_id);
                                $update_stmt->execute();
                                
                                $success = "Booking successful! Your booking ID is: " . $stmt->insert_id . ". Please check your bookings in the dashboard.";
                            } else {
                                $error = "Booking failed: " . $conn->error;
                            }
                        }
                    }
                    ?>
                    
                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($success); ?>
                            <p><a href="user/my_bookings.php" style="color: #155724; text-decoration: underline;">View My Bookings</a></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="number_of_people">Number of People</label>
                                <select id="number_of_people" name="number_of_people" required>
                                    <?php for ($i = 1; $i <= min(10, $package['available_slots']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> person<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="travel_date">Travel Date</label>
                                <input type="date" id="travel_date" name="travel_date" 
                                       min="<?php echo $package['available_from']; ?>" 
                                       max="<?php echo $package['available_to']; ?>" 
                                       required>
                                <small style="color: #666;">Available between <?php echo date('M d, Y', strtotime($package['available_from'])); ?> and <?php echo date('M d, Y', strtotime($package['available_to'])); ?></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_requests">Special Requests (Optional)</label>
                                <textarea id="special_requests" name="special_requests" rows="4" placeholder="Any special requirements, dietary restrictions, etc."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Total Amount</label>
                                <div id="total_amount_display" style="font-size: 1.2rem; font-weight: bold; color: #764ba2;">
                                    Rs. <?php echo number_format($package['price'], 2); ?>
                                </div>
                                <small style="color: #666;">Price per person: Rs. <?php echo number_format($package['price'], 2); ?></small>
                            </div>
                            
                            <button type="submit" name="book_now" class="btn">
                                <i class="fas fa-calendar-check"></i> Book Now
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="logo"><i class="fas fa-globe-asia"></i> Travel Agency</div>
                <div style="text-align:right;">
                    <p><i class="fas fa-envelope"></i> contact@travelagency.com<br>
                    <i class="fas fa-phone"></i> +977 1-1234567</p>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2024 Travel Agency Management System. All rights reserved. Developed by Yasmin Haq</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Update total amount when number of people changes
        document.getElementById('number_of_people').addEventListener('change', function() {
            const pricePerPerson = <?php echo $package['price']; ?>;
            const numberOfPeople = this.value;
            const totalAmount = pricePerPerson * numberOfPeople;
            document.getElementById('total_amount_display').textContent = 
                'Rs. ' + totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
        });
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        const travelDateInput = document.getElementById('travel_date');
        if (travelDateInput.min < today) {
            travelDateInput.min = today;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>