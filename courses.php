<?php
// courses.php - Handles CRUD for Courses Entity
header("Content-Type: application/json");
include 'db.php'; 

// Enforce security middleware layer check
checkApiKey();

$method = $_SERVER['REQUEST_METHOD'];

// Wrap execution flow within a try-catch block to gracefully capture exceptions
try {
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
                // ==================== ADVANCED FEATURES: FILTERING, SEARCH & PAGINATION ====================
                
                // 1. Prepare array to store dynamic WHERE clauses
                $where_clauses = [];

                // Feature: Filter by credits count (e.g., courses.php?credits=3)
                if (isset($_GET['credits']) && $_GET['credits'] !== '') {
                    $credits_filter = intval($_GET['credits']);
                    $where_clauses[] = "credits = $credits_filter";
                }

                // Feature: Filter by partial name search (e.g., courses.php?search=programming)
                if (isset($_GET['search']) && $_GET['search'] !== '') {
                    $search_filter = $conn->real_escape_string($_GET['search']);
                    $where_clauses[] = "course_name LIKE '%$search_filter%'";
                }

                // Construct WHERE sql dynamically if arrays are populated
                $where_sql = "";
                if (count($where_clauses) > 0) {
                    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
                }

                // 2. Extract values for Pagination tracking
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

                if ($page < 1) { $page = 1; }
                if ($limit < 1) { $limit = 10; }

                $offset = ($page - 1) * $limit;

                // 3. Count total records matching the current filters
                $total_query = "SELECT COUNT(*) as total FROM courses" . $where_sql;
                $total_result = $conn->query($total_query);
                $total_row = $total_result->fetch_assoc();
                $total_rows = intval($total_row['total']);

                $total_pages = ceil($total_rows / $limit);

                // 4. Query tailored dataset using WHERE constraints, LIMIT, and OFFSET
                $query = "SELECT * FROM courses" . $where_sql . " LIMIT $limit OFFSET $offset";
                $result = $conn->query($query);
                
                $courses = [];
                while($row = $result->fetch_assoc()) {
                    $courses[] = $row;
                }

                // 5. Send unified payload with descriptive pagination metadata
                echo json_encode([
                    "success" => true,
                    "meta" => [
                        "current_page" => $page,
                        "per_page" => $limit,
                        "total_rows" => $total_rows,
                        "total_pages" => $total_pages,
                        "filters_applied" => [
                            "credits" => isset($_GET['credits']) ? $_GET['credits'] : null,
                            "search" => isset($_GET['search']) ? $_GET['search'] : null
                        ]
                    ],
                    "data" => $courses
                ]);
                // ==================== END OF ADVANCED API MANAGEMENT LOGIC ====================
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
            $conn->query($query);
            
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Course added successfully."]);
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
            $conn->query($query);
            
            echo json_encode(["success" => true, "message" => "Course details updated successfully."]);
            break;

        case 'DELETE':
            if (!isset($_GET['code'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing course code parameter."]);
                break;
            }

            $code = $conn->real_escape_string($_GET['code']);
            $query = "DELETE FROM courses WHERE course_code = '$code'";
            $conn->query($query);
            
            echo json_encode(["success" => true, "message" => "Course deleted successfully."]);
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