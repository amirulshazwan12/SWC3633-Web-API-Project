<?php
// qrcode.php - Handles Third-Party QR Code API Integration
header("Content-Type: application/json");
include 'db.php'; // Ensures X-API-KEY security and global exception handler are active

// Enforce security middleware layer check
checkApiKey();

$method = $_SERVER['REQUEST_METHOD'];

// Wrap execution flow within a try-catch block to gracefully capture exceptions
try {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method Not Allowed. Please use POST method to generate QR codes."]);
    } else {
        // 1. Capture JSON input payload from the client
        $data = json_decode(file_get_contents("php://input"), true);

        // Validation: Ensure the body contains text or data to encode
        if (empty($data['text_to_convert'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Validation Error: 'text_to_convert' field is required."]);
        } else {
            $text = urlencode($data['text_to_convert']); // Make data string safe for URL passing
            $size = isset($data['size']) ? $data['size'] : '200x200'; // Default dimension fallback

            // 2. THIRD-PARTY API INTEGRATION
            // Construct target query URL for external open-source generator service
            $thirdPartyApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data={$text}";

            // 3. Dispatch structured JSON metadata back to frontend client
            echo json_encode([
                "success" => true,
                "message" => "QR Code successfully generated via Third-Party API!",
                "data" => [
                    "input_text" => $data['text_to_convert'],
                    "qr_image_url" => $thirdPartyApiUrl
                ]
            ]);
        }
    }
} catch (Exception $e) {
    // Route exception straight to global handler inside db.php
    jaringException($e);
}
?>