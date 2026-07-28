<?php
session_start();
require_once __DIR__ . "/connection.php";

if (isset($_SESSION['customer_id'])) {
    header("Location: user/user_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM customers WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        
        $full_name = $user['first_name'] . ' ' . $user['last_name'];
        
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['full_name'] = $full_name;
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            
            header("Location: user/user_dashboard.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "User not found!";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Travel Agency</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        /* Add header styles */
        .site-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .site-logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .site-nav a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .site-nav a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }
        
        /* Add padding to body to account for fixed header */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
            padding-top: 80px; /* Space for fixed header */
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
        }
        
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-left h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .login-right {
            flex: 1;
            padding: 60px 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h2 {
            color: #667eea;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-form h3 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
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
        
        .form-group input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .error-message {
            background: #ffe6e6;
            color: #ff3333;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ff9999;
            text-align: center;
        }
        
        .register-link {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .test-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 25px;
            border-left: 4px solid #667eea;
        }
        
        .test-credentials h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .test-credentials p {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
            
            .site-nav a {
                margin-left: 10px;
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            
            .site-logo {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .header-content {
                flex-direction: column;
                gap: 10px;
            }
            
            .site-nav a {
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Add Header with Navigation -->
    <header class="site-header">
        <div class="header-container">
            <div class="header-content">
                <div class="site-logo">
                    <i class="fas fa-globe-asia"></i>
                    <span>Travel Agency</span>
                </div>
                <nav class="site-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="packages.php"><i class="fas fa-suitcase"></i> Packages</a>
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="user/user_dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php else: ?>
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                    <?php endif; ?>
                    <!-- Admin link always visible -->
                    <a href="admin/login.php" style="background: rgba(255,255,255,0.1);">
                        <i class="fas fa-crown"></i> Admin
                    </a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="login-wrapper">
            <div class="login-left">
                <h1>Welcome Back!</h1>
                <p>Login to access your travel bookings, manage your profile, and explore exciting travel packages tailored just for you.</p>
                <p style="margin-top: 20px;">Not a member yet? Join thousands of travelers who trust us with their adventures.</p>
            </div>
            
            <div class="login-right">
                <div class="logo">
                    <h2>TravelExplorer</h2>
                    <p>Your gateway to unforgettable journeys</p>
                </div>
                
                <div class="login-form">
                    <h3>Sign In to Your Account</h3>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'test@example.com'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required 
                                   value="yasmin123">
                        </div>
                        
                        <button type="submit" class="btn-login">Sign In</button>
                    </form>
                    
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</body>
</html>
