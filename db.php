<?php
// db.php - Local XAMPP MySQL Database Connection
$host = "localhost";
$user = "root";       // Default XAMPP username
$password = "";       // Default XAMPP password is empty
$database = "exam_management"; // Make sure this matches your database name in phpMyAdmin

$conn = new mysqli($host, $user, $password, $database);

// Check if connection failed
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}
?>