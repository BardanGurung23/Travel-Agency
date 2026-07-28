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
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current password hash
    $stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = "User not found!";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE customers SET password = ? WHERE customer_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $customer_id);
        
        if ($update_stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Error updating password: " . $conn->error;
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Travel Agency</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .header p {
            color: #999;
            font-size: 0.9rem;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #ffe5e5;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }

        .alert-success {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
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

        .password-strength {
            font-size: 0.85rem;
            margin-top: 5px;
            color: #999;
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
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
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .info-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            font-size: 0.9rem;
            color: #666;
        }

        .info-box i {
            color: #667eea;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-lock"></i>
            <h1>Change Password</h1>
            <p>Update your account password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Password Requirements:</strong>
            <ul style="margin-left: 25px; margin-top: 8px;">
                <li>Minimum 6 characters</li>
                <li>Mix of uppercase and lowercase recommended</li>
                <li>Include numbers and symbols for better security</li>
            </ul>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">
                    <i class="fas fa-lock"></i> Current Password
                </label>
                <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">
            </div>

            <div class="form-group">
                <label for="new_password">
                    <i class="fas fa-lock"></i> New Password
                </label>
                <input type="password" id="new_password" name="new_password" required placeholder="Enter your new password">
                <div class="password-strength">Minimum 6 characters required</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i> Confirm New Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password">
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Update Password
                </button>
                <a href="user_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <?php $conn->close(); ?>
</body>
</html>