	<?php
	$host = "localhost";
	$user = "root";       
	$password = "";       
	$database = "exam_management"; 

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	$conn = new mysqli($host, $user, $password, $database);

	if ($conn->connect_error) {
		header('Content-Type: application/json');
		http_response_code(500);
		echo json_encode([
			"success" => false, 
			"message" => "Database connection failed: " . $conn->connect_error
		]);
		exit();
	}

	function jaringException($exception) {
		header('Content-Type: application/json');
		http_response_code(500); 
		echo json_encode([
			"success" => false,
			"message" => "System detected an internal server error: " . $exception->getMessage()
		]);
		exit();
	}

	set_exception_handler('jaringException');

	define('SECRET_API_KEY', 'RahasiaSangatAman123!');

	function checkApiKey() {
		$headers = getallheaders();
		$apiKeyHeader = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : (isset($headers['x-api-key']) ? $headers['x-api-key'] : null);

		if (!$apiKeyHeader) {
			header('Content-Type: application/json');
			http_response_code(401); 
			echo json_encode([
				"success" => false,
				"message" => "Access Denied. API Key not found in the Request Headers."
			]);
			exit(); 
		}

		if ($apiKeyHeader !== SECRET_API_KEY) {
			header('Content-Type: application/json');
			http_response_code(403); 
			echo json_encode([
				"success" => false,
				"message" => "Access Denied. Invalid or expired API Key."
			]);
			exit(); 
		}
	}
	?>