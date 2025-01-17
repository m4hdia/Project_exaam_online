<?php
// get_student_exams.php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once 'config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if student_id is set in the GET request
    if (!isset($_GET['student_id'])) {
        exit(json_encode(['success' => false, 'message' => 'Student ID is required']));
    }
    
    $student_id = $_GET['student_id'];
    
    // Get all exams and answers for the student
    $sql = "SELECT e.id as exam_id, e.title, e.description, sa.submitted_at,
            q.id as question_id, q.question_text, q.points, q.type,
            sa.answer_text as student_answer,
            GROUP_CONCAT(CASE WHEN a.is_correct = 1 THEN a.answer_text END) as correct_answers
            FROM exams e
            JOIN questions q ON e.id = q.exam_id
            LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_id = :student_id
            LEFT JOIN answers a ON q.id = a.question_id
            WHERE sa.student_id = :student_id
            GROUP BY e.id, q.id
            ORDER BY e.id, q.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $results]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>R