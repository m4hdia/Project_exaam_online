<?php
// api/update_student_status.php
require_once 'config.php';
session_start();

// Verify teacher authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);
$studentId = filter_var($data['student_id'] ?? null, FILTER_VALIDATE_INT);
$status = in_array($data['status'], ['accepted', 'rejected']) ? $data['status'] : null;

if (!$studentId || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND user_type = 'student'");
    $success = $stmt->execute([$status, $studentId]);
    
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

// api/create_exam.php
require_once 'config.php';
session_start();

// Verify teacher authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate and sanitize input
$title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
$description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
$startDate = filter_var($_POST['start_date'], FILTER_SANITIZE_STRING);
$endDate = filter_var($_POST['end_date'], FILTER_SANITIZE_STRING);
$status = in_array($_POST['status'], ['draft', 'published']) ? $_POST['status'] : 'draft';

if (!$title || !$description || !$startDate || !$endDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO exams (teacher_id, title, description, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $_SESSION['user_id'],
        $title,
        $description,
        $startDate,
        $endDate,
        $status
    ]);
    
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

// api/update_exam.php
require_once 'config.php';
session_start();

// Verify teacher authentication and exam ownership
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$examId = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if (!$examId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit();
}

try {
    // Verify exam ownership
    $stmt = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$examId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Update exam
    $stmt = $pdo->prepare("
        UPDATE exams 
        SET title = ?, description = ?, start_date = ?, end_date = ?, status = ?
        WHERE id = ? AND teacher_id = ?
    ");
    
    $success = $stmt->execute([
        filter_var($_POST['title'], FILTER_SANITIZE_STRING),
        filter_var($_POST['description'], FILTER_SANITIZE_STRING),
        filter_var($_POST['start_date'], FILTER_SANITIZE_STRING),
        filter_var($_POST['end_date'], FILTER_SANITIZE_STRING),
        in_array($_POST['status'], ['draft', 'published']) ? $_POST['status'] : 'draft',
        $examId,
        $_SESSION['user_id']
    ]);
    
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

// api/delete_exam.php
require_once 'config.php';
session_start();

// Verify teacher authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$examId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$examId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ? AND teacher_id = ?");
    $success = $stmt->execute([$examId, $_SESSION['user_id']]);
    
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}