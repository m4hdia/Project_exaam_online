<?php
// save_correction.php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once 'config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $exam_id = $data['exam_id'];
    $student_id = $data['student_id'];
    $score = $data['score'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if result already exists
    $check_sql = "SELECT id FROM results WHERE exam_id = :exam_id AND student_id = :student_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([
        'exam_id' => $exam_id,
        'student_id' => $student_id
    ]);
    
    if ($check_stmt->rowCount() > 0) {
        // Update existing result
        $sql = "UPDATE results 
                SET score = :score, 
                    submitted_at = CURRENT_TIMESTAMP 
                WHERE exam_id = :exam_id 
                AND student_id = :student_id";
    } else {
        // Insert new result
        $sql = "INSERT INTO results (exam_id, student_id, score) 
                VALUES (:exam_id, :student_id, :score)";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'exam_id' => $exam_id,
        'student_id' => $student_id,
        'score' => $score
    ]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>