<?php
// list_packages.php
include "connection.php";

echo "<h2>Packages in Database</h2>";

$result = $conn->query("SELECT package_id, title, destination, price FROM packages ORDER BY package_id");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Title</th><th>Destination</th><th>Price</th><th>Link</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['package_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['destination']) . "</td>";
        echo "<td>Rs. " . number_format($row['price'], 2) . "</td>";
        echo "<td><a href='package_details.php?id=" . $row['package_id'] . "'>View Details</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No packages found in database!</p>";
}

$conn->close();
?>