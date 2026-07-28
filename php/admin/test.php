<?php
session_start();
include "../connection.php";

echo "<h3>Testing Database Connection</h3>";
echo "Connected: " . ($conn->connect_error ? "NO" : "YES") . "<br>";

echo "<h3>Checking Bookings Table</h3>";
$result = $conn->query("DESCRIBE bookings");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td></tr>";
}
echo "</table>";

$conn->close();
?>