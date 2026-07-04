<?php
// results.php - Handles CRUD for Results Entity with Constraints
header("Content-Type: application/json");
include 'db.php'; 

// Enforce security middleware layer check
checkApiKey();

$method = $_SERVER['REQUEST_METHOD'];

// Wrap execution flow within a try-catch block to gracefully capture exceptions
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
                    echo json_encode(["success" => false, "message" => "Result record target not found."]);
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
            
            if (!isset($data['student_id']) || !isset($data['exam_id']) || !isset($data['marks']) || empty($data['grade'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "All parameters are mandatory."]);
                break;
            }

            $student_id = intval($data['student_id']);
            $exam_id = intval($data['exam_id']);
            $marks = intval($data['marks']);
            $grade = $conn->real_escape_string($data['grade']);

            // Data Range Validation (Replicates your SQL CHECK Constraint)
            if ($marks < 0 || $marks > 100) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Validation Error: Marks score must fall strictly within 0 and 100."]);
                break;
            }

            $query = "INSERT INTO results (student_id, exam_id, marks, grade) VALUES ($student_id, $exam_id, $marks, '$grade')";
            $conn->query($query);
            
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Student academic mark logged successfully.", "result_id" => $conn->insert_id]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing result instance reference ID."]);
                break;
            }
            
            $id = intval($_GET['id']);
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['marks']) || empty($data['grade'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Both modified marks score and grades string tracking are required."]);
                break;
            }

            $marks = intval($data['marks']);
            $grade = $conn->real_escape_string($data['grade']);

            // Data Range Validation
            if ($marks < 0 || $marks > 100) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Validation Error: Marks out of range."]);
                break;
            }

            $query = "UPDATE results SET marks=$marks, grade='$grade' WHERE result_id=$id";
            $conn->query($query);
            
            echo json_encode(["success" => true, "message" => "Result evaluation modified successfully."]);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing unique entry reference key."]);
                break;
            }

            $id = intval($_GET['id']);
            $query = "DELETE FROM results WHERE result_id = $id";
            $conn->query($query);
            
            echo json_encode(["success" => true, "message" => "Academic assessment instance deleted successfully."]);
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