<?php
// insert_packages_now.php - RUN THIS ONCE
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 INSERTING NEPAL PACKAGES NOW</h1>";

require_once __DIR__ . '/connection.php';

// Step 1: Clear any existing packages
echo "<h3>Step 1: Clearing old data...</h3>";
$conn->query("DELETE FROM packages");
echo "✅ Cleared old packages<br>";

// Step 2: Insert Nepal packages
echo "<h3>Step 2: Inserting Nepal packages...</h3>";

$sql = "INSERT INTO packages (title, destination, description, price, duration_days, start_date, end_date) VALUES
('Kathmandu Heritage Tour', 'Kathmandu Valley', 'Explore ancient temples and UNESCO World Heritage Sites', 15000.00, 3, '2024-06-01', '2024-06-30'),
('Pokhara Adventure Package', 'Pokhara', 'Experience paragliding and boating at Phewa Lake', 25000.00, 5, '2024-06-01', '2024-06-30'),
('Everest Base Camp Trek', 'Solukhumbu', 'Trek to the base of Mount Everest', 125000.00, 14, '2024-07-01', '2024-07-30'),
('Chitwan Jungle Safari', 'Chitwan National Park', 'Wildlife safari with elephant rides', 18000.00, 2, '2024-06-10', '2024-06-11'),
('Lumbini Pilgrimage Tour', 'Lumbini', 'Visit the birthplace of Buddha', 12000.00, 2, '2024-06-15', '2024-06-16')";

if ($conn->query($sql)) {
    echo "✅ Inserted " . $conn->affected_rows . " Nepal packages<br>";
} else {
    echo "❌ Insert error: " . $conn->error . "<br>";
}

// Step 3: Verify
echo "<h3>Step 3: Verification...</h3>";
$result = $conn->query("SELECT * FROM packages");
echo "Total packages now: <strong>" . $result->num_rows . "</strong><br>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='margin-top: 20px;'>
            <tr style='background: #f2f2f2;'>
                <th>ID</th>
                <th>Title</th>
                <th>Destination</th>
                <th>Price</th>
            </tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['package_id'] . "</td>
                <td><strong>" . $row['title'] . "</strong></td>
                <td>" . $row['destination'] . "</td>
                <td>Rs. " . number_format($row['price'], 2) . "</td>
              </tr>";
    }
    echo "</table>";
}

echo "<h3 style='color: green; margin-top: 30px;'>✅ PACKAGES INSERTED SUCCESSFULLY!</h3>";
echo "<p><a href='verify.php'>Check database again</a> | <a href='index.php'>Go to Homepage</a></p>";

$conn->close();
?>
