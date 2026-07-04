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
            // ==================== MULA LOGIK FILTERING & PAGINATION ====================
            
            // 1. Sediakan Array untuk menampung syarat-syarat WHERE
            $where_clauses = [];

            // Ciri Tapis 1: Tapis mengikut bilangan 'credits' (Contoh: courses.php?credits=3)
            if (isset($_GET['credits']) && $_GET['credits'] !== '') {
                $credits_filter = intval($_GET['credits']);
                $where_clauses[] = "credits = $credits_filter";
            }

            // Ciri Tapis 2: Tapis mengikut nama kursus / Search (Contoh: courses.php?search=programming)
            if (isset($_GET['search']) && $_GET['search'] !== '') {
                $search_filter = $conn->real_escape_string($_GET['search']);
                $where_clauses[] = "course_name LIKE '%$search_filter%'";
            }

            // Bina klausa WHERE secara dinamik jika ada tapisan yang dipilih
            $where_sql = "";
            if (count($where_clauses) > 0) {
                $where_sql = " WHERE " . implode(" AND ", $where_clauses);
            }


            // 2. Ambil nilai 'page' dan 'limit' untuk Pagination
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

            if ($page < 1) { $page = 1; }
            if ($limit < 1) { $limit = 10; }

            $offset = ($page - 1) * $limit;


            // 3. Dapatkan jumlah keseluruhan rekod yang LEPAS TAPISAN (Gunakan $where_sql)
            $total_query = "SELECT COUNT(*) as total FROM courses" . $where_sql;
            $total_result = $conn->query($total_query);
            $total_row = $total_result->fetch_assoc();
            $total_rows = intval($total_row['total']);

            $total_pages = ceil($total_rows / $limit);


            // 4. Query data courses dengan gabungan WHERE, LIMIT dan OFFSET
            $query = "SELECT * FROM courses" . $where_sql . " LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);
            
            $courses = [];
            while($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }


            // 5. Hantar respon beserta Metadata
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
            
            // ==================== TAMAT LOGIK FILTERING & PAGINATION ====================
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