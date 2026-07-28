<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";

$customer_id = $_SESSION['customer_id'];
$booking_id = $_GET['booking_id'] ?? 0;

// Verify booking belongs to user
$query = "SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found!");
}

// Cancel booking
$update_query = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    $update_stmt->close();
    $conn->close();
    header("Location: my_bookings.php?message=Booking cancelled successfully");
    exit();
} else {
    die("Error cancelling booking: " . $conn->error);
}
?>