<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['answers'] as $question_id => $answer) {
        $stmt = $pdo->prepare("
            INSERT INTO student_answers 
            (student_id, exam_id, question_id, answer_text, submitted_at) 
            VALUES (:student_id, :exam_id, :question_id, :answer_text, NOW())
            ON DUPLICATE KEY UPDATE answer_text = :answer_text, submitted_at = NOW()
        ");
        $stmt->execute([
            'student_id' => $_SESSION['user_id'],
            'exam_id' => $_POST['exam_id'],
            'question_id' => $question_id,
            'answer_text' => $answer
        ]);
    }

    echo json_encode(['success' => true]);
    exit();
}