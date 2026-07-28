<?php
include "connection.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $query = "UPDATE customers SET password=? WHERE email=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $new_password, $email);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Password updated successfully!";
    } else {
        $message = "Email not found!";
    }
}
?>
<form method="POST">
    <h2>Forgot Password</h2>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>New Password</label>
    <input type="password" name="new_password" required>

    <button type="submit">Reset Password</button>

    <p><?php echo $message; ?></p>
</form>
<a href="forgot_password.php">Forgot Password?</a>