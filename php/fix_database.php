<?php
// fix_database.php - Fix database structure
include "connection.php";

echo "<h2>🔧 Fixing Database Structure</h2>";

// 1. Add missing columns
echo "<h3>1. Adding missing columns...</h3>";
$add_columns = [
    "ALTER TABLE packages ADD COLUMN IF NOT EXISTS available_slots INT DEFAULT 50 AFTER available_to",
    "ALTER TABLE packages ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active' AFTER available_slots"
];

foreach ($add_columns as $sql) {
    if ($conn->query($sql)) {
        echo "✅ Added column<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// 2. Remove duplicate data (keep only IDs 3 and 4)
echo "<h3>2. Removing duplicate packages...</h3>";
$conn->query("DELETE FROM packages WHERE package_id > 4");
echo "✅ Removed duplicate packages (IDs 5-10)<br>";

// 3. Update the remaining packages with complete data
echo "<h3>3. Updating packages with complete data...</h3>";
$updates = [
    3 => [
        'destination' => 'Solukhumbu',
        'description' => 'Trek to the base of the world\'s highest mountain with experienced guides and sherpa support. Includes accommodation, meals, and permits.',
        'duration_days' => 14,
        'available_slots' => 10,
        'status' => 'active'
    ],
    4 => [
        'destination' => 'Chitwan National Park',
        'description' => 'Wildlife safari including elephant rides, jungle walks, canoe rides, bird watching, and traditional Tharu cultural programs.',
        'duration_days' => 2,
        'available_slots' => 30,
        'status' => 'active'
    ]
];

foreach ($updates as $id => $data) {
    $sql = "UPDATE packages SET 
            destination = '" . $conn->real_escape_string($data['destination']) . "',
            description = '" . $conn->real_escape_string($data['description']) . "',
            duration_days = " . $data['duration_days'] . ",
            available_slots = " . $data['available_slots'] . ",
            status = '" . $data['status'] . "'
            WHERE package_id = " . $id;
    
    if ($conn->query($sql)) {
        echo "✅ Updated package ID $id<br>";
    }
}

// 4. Add more sample packages
echo "<h3>4. Adding more sample packages...</h3>";
$new_packages = [
    [
        'package_name' => 'Kathmandu Heritage Tour',
        'destination' => 'Kathmandu Valley',
        'description' => 'Explore ancient temples and UNESCO World Heritage Sites including Pashupatinath, Swayambhunath, and Durbar Squares with expert guides.',
        'price' => 15000.00,
        'duration_days' => 3,
        'available_from' => '2024-06-01',
        'available_to' => '2024-06-30',
        'available_slots' => 20,
        'status' => 'active'
    ],
    [
        'package_name' => 'Pokhara Adventure Package',
        'destination' => 'Pokhara',
        'description' => 'Experience paragliding over the Himalayas, boating at Phewa Lake, visit World Peace Pagoda, and explore caves with stunning mountain views.',
        'price' => 25000.00,
        'duration_days' => 5,
        'available_from' => '2024-06-01',
        'available_to' => '2024-06-30',
        'available_slots' => 15,
        'status' => 'active'
    ],
    [
        'package_name' => 'Lumbini Pilgrimage Tour',
        'destination' => 'Lumbini',
        'description' => 'Visit the birthplace of Buddha, explore ancient monasteries, Maya Devi Temple, and the sacred garden with meditation sessions.',
        'price' => 12000.00,
        'duration_days' => 2,
        'available_from' => '2024-06-15',
        'available_to' => '2024-06-16',
        'available_slots' => 25,
        'status' => 'active'
    ]
];

foreach ($new_packages as $pkg) {
    $sql = "INSERT INTO packages (package_name, destination, description, price, duration_days, available_from, available_to, available_slots, status) 
            VALUES (
                '" . $conn->real_escape_string($pkg['package_name']) . "',
                '" . $conn->real_escape_string($pkg['destination']) . "',
                '" . $conn->real_escape_string($pkg['description']) . "',
                " . $pkg['price'] . ",
                " . $pkg['duration_days'] . ",
                '" . $pkg['available_from'] . "',
                '" . $pkg['available_to'] . "',
                " . $pkg['available_slots'] . ",
                '" . $pkg['status'] . "'
            )";
    
    if ($conn->query($sql)) {
        echo "✅ Added: " . $pkg['package_name'] . " (ID: " . $conn->insert_id . ")<br>";
    }
}

// 5. Show final result
echo "<h3>5. Final packages in database:</h3>";
$result = $conn->query("SELECT package_id, package_name, destination, price, available_slots, status FROM packages ORDER BY package_id");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Destination</th><th>Price</th><th>Slots</th><th>Status</th><th>Link</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['package_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['package_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['destination']) . "</td>";
    echo "<td>Rs. " . number_format($row['price'], 2) . "</td>";
    echo "<td>" . $row['available_slots'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td><a href='package_details.php?id=" . $row['package_id'] . "'>View</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='color: green;'>✅ DATABASE FIXED SUCCESSFULLY!</h3>";
echo "<p>Now you have packages with IDs: 3, 4, 11, 12, 13</p>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='packages.php'>View All Packages</a></p>";

$conn->close();
?>