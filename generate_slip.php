	<?php
	header("Content-Type: application/json");
	include 'db.php';
	checkApiKey();

	$method = $_SERVER['REQUEST_METHOD'];

	try {
		if ($method !== 'GET') {
			http_response_code(405);
			echo json_encode(["success" => false, "message" => "Method Not Allowed. Please use GET method to generate exam slips."]);
		} else if (!isset($_GET['student_id'])) {
			http_response_code(400);
			echo json_encode(["success" => false, "message" => "Missing student_id parameter."]);
		} else {
			$student_id = intval($_GET['student_id']);

			$query = "SELECT username, email, role FROM users WHERE user_id = $student_id";
			$result = $conn->query($query);

			if ($result->num_rows == 0) {
				http_response_code(404);
				echo json_encode(["success" => false, "message" => "Student record not found."]);
			} else {
				$student = $result->fetch_assoc();

				if ($student['role'] !== 'Student') {
					http_response_code(403);
					echo json_encode(["success" => false, "message" => "Access Denied: Only students can generate exam slips."]);
				} else 
					$dataToEncode = "Student Name: " . $student['username'] . " | Email: " . $student['email'] . " | Authorized for Exam Hall";

					$thirdPartyApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($dataToEncode);

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
	} catch (Exception $e) 
		jaringException($e);
	} finally {
		if (isset($conn)) {
			$conn->close();
		}
	}
	?>