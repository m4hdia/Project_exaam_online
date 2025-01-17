<?php
// get_exam.php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['exam_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Exam ID required']);
    exit;
}

$exam_id = intval($_GET['exam_id']);
$student_id = $_SESSION['user_id'];

try {
    // Get exam details
    $stmt = $pdo->prepare("
        SELECT id, title, description, duration, start_date, end_date
        FROM exams
        WHERE id = ? AND status = 'not_started'
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        throw new Exception('Exam not found or not available');
    }

    // Check if exam time is valid
    $now = new DateTime();
    $start = new DateTime($exam['start_date']);
    $end = new DateTime($exam['end_date']);

    if ($now < $start || $now > $end) {
        throw new Exception('Exam is not currently available');
    }

    // Get questions
    $stmt = $pdo->prepare("
        SELECT id, question_text, points, type
        FROM questions
        WHERE exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get answers for MCQ questions
    foreach ($questions as &$question) {
        if ($question['type'] === 'mcq') {
            $stmt = $pdo->prepare("
                SELECT id, answer_text
                FROM answers
                WHERE question_id = ?
            ");
            $stmt->execute([$question['id']]);
            $question['answers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $exam['questions'] = $questions;
    echo json_encode($exam);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>