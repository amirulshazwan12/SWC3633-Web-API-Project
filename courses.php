<?php
// courses.php - Handles CRUD for Courses Entity
header("Content-Type: application/json");
include 'db.php'; 

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['code'])) {
            $code = $conn->real_escape_string($_GET['code']);
            $query = "SELECT * FROM courses WHERE course_code = '$code'";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                echo json_encode(["success" => true, "data" => $result->fetch_assoc()]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Course not found."]);
            }
        } else {
            $query = "SELECT * FROM courses";
            $result = $conn->query($query);
            $courses = [];
            while($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
            echo json_encode(["success" => true, "data" => $courses]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['course_code']) || empty($data['course_name']) || !isset($data['credits'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Validation Error: All fields are required."]);
            break;
        }

        $course_code = $conn->real_escape_string($data['course_code']);
        $course_name = $conn->real_escape_string($data['course_name']);
        $credits = intval($data['credits']);

        $query = "INSERT INTO courses (course_code, course_name, credits) VALUES ('$course_code', '$course_name', $credits)";
        
        if ($conn->query($query)) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Course added successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to insert course."]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['code'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing course code parameter."]);
            break;
        }
        
        $code = $conn->real_escape_string($_GET['code']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['course_name']) || !isset($data['credits'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Course name and credits are required for modification."]);
            break;
        }

        $course_name = $conn->real_escape_string($data['course_name']);
        $credits = intval($data['credits']);

        $query = "UPDATE courses SET course_name='$course_name', credits=$credits WHERE course_code='$code'";
        
        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Course details updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Update operation failed."]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['code'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing course code parameter."]);
            break;
        }

        $code = $conn->real_escape_string($_GET['code']);
        $query = "DELETE FROM courses WHERE course_code = '$code'";

        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Course deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Delete operation failed."]);
        }
        break;
}
$conn->close();
?>