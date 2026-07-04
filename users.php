<?php
// users.php - Handles CRUD for Users Entity
header("Content-Type: application/json");
include 'db.php'; // Includes your XAMPP MySQL connection string

// Enforce security middleware layer check
checkApiKey();

$method = $_SERVER['REQUEST_METHOD'];

// Wrap execution flow within a try-catch block to gracefully capture exceptions
try {
    switch($method) {
        // =========================================================================
        // READ (GET) - Enhanced with Advanced Features: Filtering & Sorting
        // =========================================================================
        case 'GET':
            // Base structure query
            $query = "SELECT user_id, username, email, role FROM users WHERE 1=1";
            
            // [ADVANCED FEATURE: FILTERING] Filter entries by role (e.g., ?role=Student)
            if (isset($_GET['role'])) {
                $role_filter = $conn->real_escape_string($_GET['role']);
                $query .= " AND role = '$role_filter'";
            }

            // [ADVANCED FEATURE: SORTING] Order records alphabetically (e.g., ?sort=desc)
            if (isset($_GET['sort']) && strtolower($_GET['sort']) == 'desc') {
                $query .= " ORDER BY username DESC";
            } else {
                $query .= " ORDER BY username ASC"; // Default layout sorting
            }

            // Execution structure
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

        // ==========================================
        // 2. CREATE (POST) - Add a new user with validation
        // ==========================================
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Data Validation Check (Checks if fields are missing)
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

        // ==========================================
        // 3. UPDATE (PUT) - Edit details of an existing user
        // ==========================================
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

        // ==========================================
        // 4. DELETE (DELETE) - Remove a user entirely
        // ==========================================
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
    // Route exception straight to global handler inside db.php
    jaringException($e);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>