<?php
// insert_packages.php
$conn = new mysqli("localhost", "yasmin", "yes123", "travel_agency");

// Clear existing
$conn->query("DELETE FROM packages");

// Insert Nepal packages
$sql = "INSERT INTO packages (title, destination, description, price, duration_days, start_date, end_date) VALUES
('Kathmandu Heritage Tour', 'Kathmandu Valley', 'Explore ancient temples and UNESCO sites', 15000.00, 3, '2024-06-01', '2024-06-30'),
('Pokhara Adventure', 'Pokhara', 'Paragliding and boating at Phewa Lake', 25000.00, 5, '2024-06-01', '2024-06-30'),
('Everest Base Camp Trek', 'Solukhumbu', 'Trek to Everest Base Camp', 125000.00, 14, '2024-07-01', '2024-07-30'),
('Chitwan Jungle Safari', 'Chitwan', 'Wildlife safari with elephants', 18000.00, 2, '2024-06-10', '2024-06-11')";

if ($conn->query($sql)) {
    echo "<h2 style='color: green;'>✅ Packages inserted successfully!</h2>";
    echo "<p><a href='index.php'>Go to Homepage</a></p>";
} else {
    echo "<h2 style='color: red;'>❌ Error: " . $conn->error . "</h2>";
}
?>