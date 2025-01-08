<?php
require_once 'config.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $exam_id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM exams WHERE id = :id");
        $stmt->bindParam(':id', $exam_id, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: teacher.php?message=Exam deleted successfully');
        exit();
    } catch (PDOException $e) {
        header('Location: teacher.php?message=Error deleting exam: ' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: teacher.php?message=No exam ID provided');
    exit();
}
?>