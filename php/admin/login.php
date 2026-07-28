<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../connection.php';

    if ($conn->connect_error) {
        $error = "Database connection failed: " . $conn->connect_error;
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter username and password.';
        } else {
            // Search by username ONLY (no email column in admin_users)
            $sql = "SELECT * FROM admin_users WHERE username = ? LIMIT 1";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                $error = "DB prepare error: " . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    $stored = $admin['password'] ?? '';

                    // Try password_verify first, fallback to plaintext for debug
                    $authenticated = false;
                    if ($stored !== '' && password_verify($password, $stored)) {
                        $authenticated = true;
                    } elseif ($password === $stored) { // plaintext fallback (debug only)
                        $authenticated = true;
                    } elseif (md5($password) === $stored) { // md5 fallback
                        $authenticated = true;
                    }

                    if ($authenticated) {
                        session_regenerate_id(true);
                        
                        // Clear customer session
                        unset($_SESSION['customer_id']);
                        unset($_SESSION['full_name']);
                        unset($_SESSION['first_name']);
                        unset($_SESSION['last_name']);
                        unset($_SESSION['email']);

                        // Set admin session
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'] ?? $admin['username'];

                        header("Location: index.php");
                        exit();
                    } else {
                        $error = "Invalid password!";
                    }
                } else {
                    $error = "Admin user not found!";
                }

                $stmt->close();
            }

            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login – Travel Agency</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
            font-family: Arial, sans-serif;
        }
        .login-box {
            background: #fff;
            padding: 35px;
            width: 100%;
            max-width: 420px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,.2);
        }
        h1 {
            text-align: center;
            color: #764ba2;
            margin-bottom: 5px;
        }
        p {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #667eea;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #5a6edc;
        }
        .error {
            background: #ffe5e5;
            color: #b10000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        .info {
            background: #f4f6ff;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
        .back {
            text-align: center;
            margin-top: 18px;
        }
        .back a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="login-box">
    <h1><i class="fa-solid fa-crown"></i> Admin Login</h1>
    <p>Travel Agency Management System</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Username</label>
        <input type="text" name="username" required placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Enter your password">
        <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
    </form>

    <div class="back">
        <a href="../index.php">← Back to website</a>
    </div>
</div>
</body>
</html>