
<?php
// update_packages_data.php - Update existing packages
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📝 Update Package Data</h1>";

$conn = new mysqli("localhost", "yasmin", "yes123", "travel_agency");

// Update existing packages
$updates = [
    1 => [
        'package_name' => 'Kathmandu Heritage Tour',
        'description' => 'Explore ancient temples and UNESCO World Heritage Sites',
        'price' => 15000.00,
        'available_from' => '2024-06-01',
        'available_to' => '2024-06-30'
    ],
    2 => [
        'package_name' => 'Pokhara Adventure Package',
        'description' => 'Experience paragliding and boating at Phewa Lake',
        'price' => 25000.00,
        'available_from' => '2024-06-01',
        'available_to' => '2024-06-30'
    ]
];

foreach ($updates as $id => $data) {
    $sql = "UPDATE packages SET 
            package_name = '" . $conn->real_escape_string($data['package_name']) . "',
            description = '" . $conn->real_escape_string($data['description']) . "',
            price = " . $data['price'] . ",
            available_from = '" . $data['available_from'] . "',
            available_to = '" . $data['available_to'] . "'
            WHERE package_id = " . $id;
    
    if ($conn->query($sql)) {
        echo "✅ Updated package " . $id . ": " . $data['package_name'] . "<br>";
    } else {
        echo "❌ Error updating " . $id . ": " . $conn->error . "<br>";
    }
}

// Add more packages if needed
echo "<h3>Adding more packages...</h3>";

$new_packages = [
    [
        'package_name' => 'Everest Base Camp Trek',
        'description' => 'Trek to the base of Mount Everest',
        'price' => 125000.00,
        'available_from' => '2024-07-01',
        'available_to' => '2024-07-30'
    ],
    [
        'package_name' => 'Chitwan Jungle Safari',
        'description' => 'Wildlife safari with elephant rides',
        'price' => 18000.00,
        'available_from' => '2024-06-10',
        'available_to' => '2024-06-11'
    ]
];

foreach ($new_packages as $pkg) {
    $sql = "INSERT INTO packages (package_name, description, price, available_from, available_to) 
            VALUES (
                '" . $conn->real_escape_string($pkg['package_name']) . "',
                '" . $conn->real_escape_string($pkg['description']) . "',
                " . $pkg['price'] . ",
                '" . $pkg['available_from'] . "',
                '" . $pkg['available_to'] . "'
            )";
    
    if ($conn->query($sql)) {
        echo "✅ Added: " . $pkg['package_name'] . "<br>";
    }
}

echo "<h3 style='color: green;'>✅ PACKAGES UPDATED!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='check_columns.php'>Check Database</a></p>";

$conn->close();
?>
