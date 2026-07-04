<?php
// db.php - Local XAMPP MySQL Database Connection
$host = "localhost";
$user = "root";       // Default XAMPP username
$password = "";       // Default XAMPP password is empty
$database = "exam_management"; 

// Mengaktifkan mod ralat MySQLi supaya ralat database dilempar ke blok catch (SANGAT PENTING)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli($host, $user, $password, $database);

// 1. ERROR HANDLING: Kegagalan Sambungan Database
if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// 2. MIDDLEWARE ERROR HANDLING GLOBAL (Fungsi yang hilang sebelum ini)
function jaringException($exception) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "success" => false,
        "message" => "Sistem mengesan ralat dalaman: " . $exception->getMessage()
    ]);
    exit();
}

// 3. API KEY AUTHENTICATION
define('SECRET_API_KEY', 'RahasiaSangatAman123!');

function checkApiKey() {
    $headers = getallheaders();
    $apiKeyHeader = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : (isset($headers['x-api-key']) ? $headers['x-api-key'] : null);

    if (!$apiKeyHeader) {
        header('Content-Type: application/json');
        http_response_code(401); 
        echo json_encode([
            "success" => false,
            "message" => "Akses ditolak. API Key tidak ditemukan dalam Header."
        ]);
        exit(); 
    }

    if ($apiKeyHeader !== SECRET_API_KEY) {
        header('Content-Type: application/json');
        http_response_code(403); 
        echo json_encode([
            "success" => false,
            "message" => "API Key tidak valid atau sudah kedaluwarsa."
        ]);
        exit(); 
    }
}
?>