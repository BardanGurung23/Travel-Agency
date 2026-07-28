
<?php
// reset_password.php
require_once __DIR__ . "/connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $new_password = $_POST['new_password'];
    
    // Hash with bcrypt
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
    $sql = "UPDATE customers SET password = '$hashed_password' WHERE email = '$email'";
    
    if ($conn->query($sql)) {
        echo "<h2>Password Reset Successful!</h2>";
        echo "<p>Password for $email has been reset to: <strong>$new_password</strong></p>";
        echo "<p>Hashed value: $hashed_password</p>";
        echo "<p><a href='login.php'>Go to Login</a></p>";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Invalid request!";
}

$conn->close();
?>
