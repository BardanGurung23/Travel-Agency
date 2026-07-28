<?php
echo "<h2>Admin System Check</h2>";

// 1️⃣ Check session
session_start();
echo "<h3>1. Session Status</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 2️⃣ Check database connection
echo "<h3>2. Database Connection</h3>";
require_once __DIR__ . '/../connection.php';

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
echo "✅ Database connected successfully<br>";

// 3️⃣ Check admin_users table
echo "<h3>3. Admin Users Table</h3>";
//$result = $conn->query("SELECT id, username, email FROM admin_users");
$result = $conn->query("SELECT DATABASE() AS db");
$row = $result->fetch_assoc();
echo "<h3>Current Database Used by PHP:</h3>";
echo "<pre>";
print_r($row);
echo "</pre>";


if (!$result) {
    die("❌ Query failed: " . $conn->error);
}

echo "Rows found: " . $result->num_rows . "<br>";

while ($row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// 4️⃣ Test password verification
echo "<h3>4. Password Verification Test</h3>";

$testPassword = "yasmin123";
$passResult = $conn->query("SELECT password FROM admin_users LIMIT 1");
$hash = $passResult->fetch_assoc()['password'];

if (password_verify($testPassword, $hash)) {
    echo "✅ password_verify() WORKS";
} else {
    echo "❌ password_verify() FAILED";
}

$conn->close();
?>
