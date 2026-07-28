<?php
/**
 * Common Functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Sanitize input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Format price
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

// Format date
function formatDate($date) {
    return date('F d, Y', strtotime($date));
}
?>