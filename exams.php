<?php
// examinations.php - Handles CRUD for Examinations Entity
header("Content-Type: application/json");
include 'db.php'; 

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $query = "SELECT * FROM examinations WHERE exam_id = $id";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                echo json_encode(["success" => true, "data" => $result->fetch_assoc()]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Examination schedule record not found."]);
            }
        } else {
            $query = "SELECT * FROM examinations";
            $result = $conn->query($query);
            $exams = [];
            while($row = $result->fetch_assoc()) {
                $exams[] = $row;
            }
            echo json_encode(["success" => true, "data" => $exams]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['course_code']) || empty($data['exam_date']) || empty($data['start_time']) || empty($data['venue'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Validation Error: Complete scheduling details required."]);
            break;
        }

        $course_code = $conn->real_escape_string($data['course_code']);
        $exam_date = $conn->real_escape_string($data['exam_date']);
        $start_time = $conn->real_escape_string($data['start_time']);
        $venue = $conn->real_escape_string($data['venue']);

        $query = "INSERT INTO examinations (course_code, exam_date, start_time, venue) VALUES ('$course_code', '$exam_date', '$start_time', '$venue')";
        
        if ($conn->query($query)) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Examination scheduled successfully.", "exam_id" => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database insertion failed."]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing exam ID parameter."]);
            break;
        }
        
        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['exam_date']) || empty($data['start_time']) || empty($data['venue'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Date, time, and venue are mandatory for update operations."]);
            break;
        }

        $exam_date = $conn->real_escape_string($data['exam_date']);
        $start_time = $conn->real_escape_string($data['start_time']);
        $venue = $conn->real_escape_string($data['venue']);

        $query = "UPDATE examinations SET exam_date='$exam_date', start_time='$start_time', venue='$venue' WHERE exam_id=$id";
        
        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Examination schedule updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database modification statement failed."]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing exam ID parameter."]);
            break;
        }

        $id = intval($_GET['id']);
        $query = "DELETE FROM examinations WHERE exam_id = $id";

        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Examination canceled and wiped out successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Delete procedure execution failure."]);
        }
        break;
}
$conn->close();
?>