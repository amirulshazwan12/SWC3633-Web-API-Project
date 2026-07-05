	<?php
	header("Content-Type: application/json");
	include 'db.php'; 
	checkApiKey();

	$method = $_SERVER['REQUEST_METHOD'];

	try {
		switch($method) {
			case 'GET':
				$query = "SELECT user_id, username, email, role FROM users WHERE 1=1";
				
				if (isset($_GET['role']) && $_GET['role'] !== '') {
					$role_filter = $conn->real_escape_string($_GET['role']);
					$query .= " AND role = '$role_filter'";
				}

				if (isset($_GET['search']) && $_GET['search'] !== '') {
					$search_filter = $conn->real_escape_string($_GET['search']);
					$query .= " AND (username LIKE '%$search_filter%' OR email LIKE '%$search_filter%')";
				}

				if (isset($_GET['sort']) && strtolower($_GET['sort']) == 'desc') {
					$query .= " ORDER BY username DESC";
				} else {
					$query .= " ORDER BY username ASC"; // Default layout sorting
				}

				if (isset($_GET['id'])) {
					$id = intval($_GET['id']);
					$query = "SELECT user_id, username, email, role FROM users WHERE user_id = $id";
					$result = $conn->query($query);
					if ($result->num_rows > 0) {
						echo json_encode(["success" => true, "data" => $result->fetch_assoc()]);
					} else {
						http_response_code(404);
						echo json_encode(["success" => false, "message" => "User record not found."]);
					}
				} else {
					$result = $conn->query($query);
					$users = [];
					while($row = $result->fetch_assoc()) {
						$users[] = $row;
					}
					echo json_encode(["success" => true, "data" => $users]);
				}
				break;
				
			case 'POST':
				$data = json_decode(file_get_contents("php://input"), true);
				
				if (empty($data['username']) || empty($data['password']) || empty($data['email']) || empty($data['role'])) {
					http_response_code(400);
					echo json_encode(["success" => false, "message" => "Validation Error: Missing required fields."]);
					break;
				}

				$username = $conn->real_escape_string($data['username']);
				$password = $conn->real_escape_string($data['password']); 
				$email = $conn->real_escape_string($data['email']);
				$role = $conn->real_escape_string($data['role']);

				$query = "INSERT INTO users (username, password, email, role) VALUES ('$username', '$password', '$email', '$role')";
				
				$conn->query($query);
				http_response_code(201);
				echo json_encode(["success" => true, "message" => "User created successfully.", "user_id" => $conn->insert_id]);
				break;

			case 'PUT':
				if (!isset($_GET['id'])) {
					http_response_code(400);
					echo json_encode(["success" => false, "message" => "Missing user ID in parameters."]);
					break;
				}
				
				$id = intval($_GET['id']);
				$data = json_decode(file_get_contents("php://input"), true);
				
				if (empty($data['username']) || empty($data['role'])) {
					http_response_code(400);
					echo json_encode(["success" => false, "message" => "Username and role fields are mandatory for updates."]);
					break;
				}

				$username = $conn->real_escape_string($data['username']);
				$role = $conn->real_escape_string($data['role']);

				$query = "UPDATE users SET username='$username', role='$role' WHERE user_id=$id";
				
				$conn->query($query);
				echo json_encode(["success" => true, "message" => "User updated successfully."]);
				break;

			case 'DELETE':
				if (!isset($_GET['id'])) {
					http_response_code(400);
					echo json_encode(["success" => false, "message" => "Missing user ID in parameters."]);
					break;
				}

				$id = intval($_GET['id']);
				$query = "DELETE FROM users WHERE user_id = $id";

				$conn->query($query);
				echo json_encode(["success" => true, "message" => "User deleted successfully."]);
				break;

			default:
				http_response_code(405);
				echo json_encode(["success" => false, "message" => "Method Not Allowed."]);
				break;
		}
	} catch (Exception $e) {
		jaringException($e);
	} finally {
		if (isset($conn)) {
			$conn->close();
		}
	}
	?>