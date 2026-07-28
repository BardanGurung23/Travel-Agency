<?php
// test_simple.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Database Test</h1>";

$conn = new mysqli("localhost", "yasmin", "yes123", "travel_agency");

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Database connected<br>";

// Check packages
$result = $conn->query("SELECT COUNT(*) as count FROM packages");
$row = $result->fetch_assoc();
echo "📦 Packages in database: " . $row['count'] . "<br>";

if ($row['count'] > 0) {
    echo "<h3>Package List:</h3>";
    $packages = $conn->query("SELECT * FROM packages");
    while($pkg = $packages->fetch_assoc()) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
        echo "<strong>" . $pkg['title'] . "</strong><br>";
        echo "Price: Rs. " . $pkg['price'] . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>❌ DATABASE IS EMPTY!</p>";
    echo "<p><a href='insert_now.php'>Click here to insert packages</a></p>";
}

$conn->close();
?>