<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exams_list.php');
    exit;
}

$exam_id = $_POST['exam_id'];
$student_id = $_SESSION['user_id']; // Assuming you have user session
$answers = $_POST['answers'];

try {
    $pdo->beginTransaction();

    foreach ($answers as $question_id => $answer) {
        $answer_text = is_array($answer) ? implode('|||', $answer) : $answer;
        
        $stmt = $pdo->prepare("
            INSERT INTO student_answers (student_id, exam_id, question_id, answer_text)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $exam_id, $question_id, $answer_text]);
    }

    // Calculate score (basic implementation)
    $score = 0;
    $stmt = $pdo->prepare("
        SELECT q.id, q.points, a.is_correct 
        FROM questions q 
        LEFT JOIN answers a ON q.id = a.question_id 
        WHERE q.exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll();

    foreach ($questions as $question) {
        if (isset($answers[$question['id']])) {
            if ($question['is_correct']) {
                $score += $question['points'];
            }
        }
    }

    // Save final result
    $stmt = $pdo->prepare("
        INSERT INTO exam_results (student_id, exam_id, score)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$student_id, $exam_id, $score]);

    $pdo->commit();
    
    header('Location: exam_result.php?exam_id=' . $exam_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "An error occurred while submitting your exam.";
    header('Location: take_exam.php?exam_id=' . $exam_id);
    exit;
}
?>