	<?php
	header("Content-Type: application/json");
	include 'db.php';

	checkApiKey();

	$method = $_SERVER['REQUEST_METHOD'];

	try {
		if ($method !== 'POST') {
			http_response_code(405);
			echo json_encode(["success" => false, "message" => "Method Not Allowed. Please use POST method to generate QR codes."]);
		} else {
			$data = json_decode(file_get_contents("php://input"), true);

			if (empty($data['text_to_convert'])) {
				http_response_code(400);
				echo json_encode(["success" => false, "message" => "Validation Error: 'text_to_convert' field is required."]);
			} else {
				$text = urlencode($data['text_to_convert']); // Make data string safe for URL passing
				$size = isset($data['size']) ? $data['size'] : '200x200'; // Default dimension fallback

				$thirdPartyApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data={$text}";

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
		jaringException($e);
	}
	?>