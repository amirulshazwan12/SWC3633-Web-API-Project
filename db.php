<?php
// db.php - Local XAMPP MySQL Database Connection
$host = "localhost";
$user = "root";       // Default XAMPP username
$password = "";       // Default XAMPP password is empty
$database = "exam_management"; 

// Enable MySQLi error reporting to throw database exceptions to the catch block (CRITICAL)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli($host, $user, $password, $database);

// 1. ERROR HANDLING: Database Connection Failure Check
if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// 2. MIDDLEWARE: Global Exception Error Handling Middleware
function jaringException($exception) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "success" => false,
        "message" => "System detected an internal server error: " . $exception->getMessage()
    ]);
    exit();
}

// Set the global exception handler to use our custom function
set_exception_handler('jaringException');

// 3. SECURITY: API KEY AUTHENTICATION
define('SECRET_API_KEY', 'RahasiaSangatAman123!');

function checkApiKey() {
    $headers = getallheaders();
    // Support both case-sensitive and case-insensitive header formats
    $apiKeyHeader = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : (isset($headers['x-api-key']) ? $headers['x-api-key'] : null);

    if (!$apiKeyHeader) {
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode([
            "success" => false,
            "message" => "Access Denied. API Key not found in the Request Headers."
        ]);
        exit(); 
    }

    if ($apiKeyHeader !== SECRET_API_KEY) {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode([
            "success" => false,
            "message" => "Access Denied. Invalid or expired API Key."
        ]);
        exit(); 
    }
}
?>