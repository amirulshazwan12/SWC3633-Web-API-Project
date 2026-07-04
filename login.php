<?php
// login.php - Handles User Authentication
header("Content-Type: application/json");
include 'db.php'; 

// Enforce security middleware layer check
checkApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed."]);
    exit();
}

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['username']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Username and password are required."]);
        exit();
    }

    $username = $conn->real_escape_string($data['username']);
    $password = $conn->real_escape_string($data['password']);

    // Note: In a production app, passwords should be hashed using password_hash() and verified with password_verify()
    $query = "SELECT user_id, username, email, role FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "message" => "Login successful.", 
            "user" => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid username or password."]);
    }
} catch (Exception $e) {
    jaringException($e);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>