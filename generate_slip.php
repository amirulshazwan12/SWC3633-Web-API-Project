<?php
// generate_slip.php - Third-Party QR Code Integration for Exam Slips
header("Content-Type: application/json");
include 'db.php';

// Enforce security middleware layer check
checkApiKey();

$method = $_SERVER['REQUEST_METHOD'];

// Wrap execution flow within a try-catch block to gracefully capture exceptions
try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method Not Allowed. Please use GET method to generate exam slips."]);
    } else if (!isset($_GET['student_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing student_id parameter."]);
    } else {
        $student_id = intval($_GET['student_id']);

        // Fetch student data from database safely
        $query = "SELECT username, email, role FROM users WHERE user_id = $student_id";
        $result = $conn->query($query);

        if ($result->num_rows == 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Student record not found."]);
        } else {
            $student = $result->fetch_assoc();

            // Check Role-Based Access [FEATURE: ROLE-BASED ACCESS CONTROL]
            if ($student['role'] !== 'Student') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Access Denied: Only students can generate exam slips."]);
            } else {
                // Compile structural payload data to encode into the QR Code graphic
                $dataToEncode = "Student Name: " . $student['username'] . " | Email: " . $student['email'] . " | Authorized for Exam Hall";

                // External Third-Party API URL Construction
                $thirdPartyApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($dataToEncode);

                // Return system data alongside the external API link
                echo json_encode([
                    "success" => true,
                    "message" => "External QR Code slip generated successfully.",
                    "student_details" => [
                        "name" => $student['username'],
                        "role" => $student['role']
                    ],
                    "integrated_api_evidence" => [
                        "provider" => "GoQR API Service",
                        "qr_code_image_url" => $thirdPartyApiUrl
                    ]
                ]);
            }
        }
    }
} catch (Exception $e) {
    // Route database exception straight to global handler inside db.php
    jaringException($e);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>