<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_save'])) {
    $response = ['success' => false];
    
    try {
        foreach ($_POST['answers'] as $question_id => $answer) {
            $answer_text = is_array($answer) ? implode('|||', $answer) : $answer;
            
            $stmt = $pdo->prepare("
                INSERT INTO student_answers 
                (student_id, exam_id, question_id, answer_text, is_draft)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    answer_text = ?,
                    is_draft = 1,
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['exam_id'],
                $question_id,
                $answer_text,
                $answer_text
            ]);
        }
        
        $response['success'] = true;
    } catch (Exception $e) {
        error_log($e->getMessage());
        $response['error'] = "Erreur lors de la sauvegarde automatique";
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>