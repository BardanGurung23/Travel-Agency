<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Since both files are in the same folder
include("connection.php"); 

$error = "";

// Handle form submission
if (isset($_POST['submit'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $mobile = trim($_POST['mobile']);

    // PHP validation for password confirmation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Insert new user securely
            $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $mobile, $hashed_password);

            if ($stmt->execute()) {
                // Redirect to login page after successful registration
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Travel Agency</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; margin:0; padding:0; background:#f4f6f8; }
header { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:1rem 0; text-align:center; font-size:1.5rem; font-weight:bold; }
.form-container { max-width:400px; margin:4rem auto; background:#fff; padding:2.5rem 2rem; border-radius:20px; box-shadow:0 15px 50px rgba(0,0,0,0.1); text-align:center; }
.form-container h2 { margin-bottom:1.5rem; color:#764ba2; }
input[type="text"], input[type="email"], input[type="password"] { width:100%; padding:12px 15px; margin:10px 0; border:1px solid #ccc; border-radius:12px; transition:0.3s; }
input:focus { border-color:#667eea; outline:none; box-shadow:0 0 5px rgba(102,126,234,0.5); }
button { width:100%; padding:12px; border:none; border-radius:12px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; font-size:16px; font-weight:bold; cursor:pointer; margin-top:10px; transition:all 0.3s ease; }
button:hover { transform:translateY(-2px); box-shadow:0 10px 25px rgba(118,75,162,0.4); }
.error { color:#d8000c; background-color:#ffbaba; padding:10px; margin-bottom:15px; border-radius:12px; }
a { color:#667eea; text-decoration:none; font-weight:bold; }
a:hover { text-decoration:underline; }
@media(max-width:480px){ .form-container { margin:2rem 1rem; padding:2rem 1.5rem; } }
</style>
<script>
function validateForm() {
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirm_password").value;
    let email = document.getElementById("email").value;
    let firstName = document.forms[0]["first_name"].value;
    let lastName = document.forms[0]["last_name"].value;

    // Name validation - letters and spaces only
    let namePattern = /^[a-zA-Z\s]+$/;
    if (!namePattern.test(firstName)) {
        alert("First name can only contain letters!");
        return false;
    }
    if (!namePattern.test(lastName)) {
        alert("Last name can only contain letters!");
        return false;
    }

    // Email validation
    let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}$/;
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address!");
        return false;
    }

    // Password match
    if (password !== confirmPassword) {
        alert("Passwords do not match!");
        return false;
    }

    alert("Your OTP number is: 123456. Validate your mobile number.");
    return true;
}
</script>
</head>
<body>

<header>Travel Agency - Register</header>

<div class="form-container">
<h2>Create Account</h2>

<?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST" onsubmit="return validateForm()">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="email" id="email" name="email" placeholder="Email Address" required>
    <input type="password" id="password" name="password" placeholder="Password" required>
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
    <input type="text" name="mobile" placeholder="Mobile Number">
    <button type="submit" name="submit"><i class="fas fa-user-plus"></i> Register</button>
</form>

<p style="margin-top:15px;">Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>