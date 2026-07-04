<?php
// results.php - Handles CRUD for Results Entity with Constraints
header("Content-Type: application/json");
include 'db.php'; 

// Enforce security middleware layer check
checkApiKey();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $query = "SELECT * FROM results WHERE result_id = $id";
                $result = $conn->query($query);
                
                if ($result->num_rows > 0) {
                    echo json_encode(["success" => true, "data" => $result->fetch_assoc()]);
                } else {
                    http_response_code(404);
                    echo json_encode(["success" => false, "message" => "Result not found."]);
                }
            } else {
                $query = "SELECT * FROM results";
                $result = $conn->query($query);
                $results = [];
                while($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
                echo json_encode(["success" => true, "data" => $results]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['student_id']) || !isset($data['exam_id']) || !isset($data['marks'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing parameters."]);
                break;
            }

            $student_id = intval($data['student_id']);
            $exam_id = intval($data['exam_id']);
            $marks = intval($data['marks']);

            if ($marks < 0 || $marks > 100) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Marks must be between 0 and 100."]);
                break;
            }

            // Server-side calculation
            $grade = 'F';
            if ($marks >= 80) $grade = 'A';
            elseif ($marks >= 70) $grade = 'B';
            elseif ($marks >= 60) $grade = 'C';
            elseif ($marks >= 50) $grade = 'D';

            $query = "INSERT INTO results (student_id, exam_id, marks, grade) VALUES ($student_id, $exam_id, $marks, '$grade')";
            $conn->query($query);
            
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Added! Calculated Grade: $grade", "result_id" => $conn->insert_id]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing ID."]);
                break;
            }
            
            $id = intval($_GET['id']);
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['marks'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Marks score is required."]);
                break;
            }

            $marks = intval($data['marks']);

            if ($marks < 0 || $marks > 100) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Marks out of range."]);
                break;
            }

            // Force recalculate the grade on the server
            $grade = 'F';
            if ($marks >= 80) $grade = 'A';
            elseif ($marks >= 70) $grade = 'B';
            elseif ($marks >= 60) $grade = 'C';
            elseif ($marks >= 50) $grade = 'D';

            // Explicitly update both marks and grade
            $query = "UPDATE results SET marks=$marks, grade='$grade' WHERE result_id=$id";
            $conn->query($query);
            
            // Sending the calculated grade back in the message so you can see it working!
            echo json_encode(["success" => true, "message" => "Updated successfully! Calculated Grade: $grade"]);
            break;

        case 'DELETE':
            $id = intval($_GET['id']);
            $conn->query("DELETE FROM results WHERE result_id = $id");
            echo json_encode(["success" => true, "message" => "Deleted successfully."]);
            break;
    }
} catch (Exception $e) {
    jaringException($e);
} finally {
    if (isset($conn)) $conn->close();
}
?>