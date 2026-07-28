<?php
// check_database.php
include "connection.php";

echo "<h2>📊 Checking Database Structure</h2>";

// Check packages table
echo "<h3>📋 Packages Table Structure:</h3>";
$result = $conn->query("DESCRIBE packages");

if ($result) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}

// Check if table exists
echo "<h3>📊 Checking if tables exist:</h3>";
$tables = ['packages', 'customers', 'bookings', 'admin_users'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    echo $table . ": " . ($check->num_rows > 0 ? "✅ Exists" : "❌ Missing") . "<br>";
}

// Show actual data in packages table
echo "<h3>📦 Current data in packages table:</h3>";
$result = $conn->query("SELECT * FROM packages");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    // Get column names
    $fields = $result->fetch_fields();
    echo "<tr>";
    foreach ($fields as $field) {
        echo "<th>" . $field->name . "</th>";
    }
    echo "</tr>";
    
    // Reset pointer and show data
    $result->data_seek(0);
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found or error: " . ($result ? "Table is empty" : $conn->error);
}

$conn->close();
?>