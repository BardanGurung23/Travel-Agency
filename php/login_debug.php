<?php
// login_debug.php - Debug version
session_start();
require_once __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    echo "<h2>Debug Info:</h2>";
    echo "<p>Email: $email</p>";
    echo "<p>Password entered: $password</p>";
    
    $sql = "SELECT * FROM customers WHERE email = '$email'";
    $result = $conn->query($sql);
    
    echo "<p>SQL Query: $sql</p>";
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        echo "<h3>User Found:</h3>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        echo "<p>Stored password hash: " . $user['password'] . "</p>";
        
        // Try password_verify first
        if (password_verify($password, $user['password'])) {
            echo "<h2 style='color: green;'>✓ password_verify() SUCCESS!</h2>";
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            header("Location: user/user_dashboard.php");
            exit();
        } else {
            echo "<p style='color: red;'>✗ password_verify() failed</p>";
            
            // Try other hash methods
            if (md5($password) === $user['password']) {
                echo "<p style='color: green;'>✓ MD5 match!</p>";
            } elseif (sha1($password) === $user['password']) {
                echo "<p style='color: green;'>✓ SHA1 match!</p>";
            } else {
                echo "<p style='color: red;'>✗ No hash method matched</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>User not found!</p>";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Debug</title>
</head>
<body>
    <h1>Login Debug Page</h1>
    <form method="POST">
        Email: <input type="email" name="email" value="test@example.com" required><br><br>
        Password: <input type="password" name="password" value="yasmin123" required><br><br>
        <button type="submit">Login with Debug</button>
    </form>
    
    <p><a href="debug_password.php">Check Password Debug</a></p>
    <p><a href="login.php">Back to Normal Login</a></p>
</body>
</html>
