<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

$status = $student['status'];


if ($status !== 'accepted') {
    die("You are not authorized to access exams. Your status is: " . ucfirst($status));
}

if (!isset($_GET['exam_id'])) {
    header("Location: student_exams.php");
    exit();
}

$exam_id = $_GET['exam_id'];


$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam not found.");
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    die("No questions found for this exam.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #333;
            text-align: center;
        }
        .question-card {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }
        .question-card h3 {
            margin: 0;
            color: #4CAF50;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            margin: 20px auto;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Exam: <?php echo htmlspecialchars($exam['title']); ?></h2>
    <form action="submit_exam.php" method="POST">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        <?php foreach ($questions as $question): ?>
            <div class="question-card">
                <h3>Question: <?php echo htmlspecialchars($question['question_text']); ?></h3>
                <?php if ($question['type'] === 'mcq'): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
                    $stmt->execute([$question['id']]);
                    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php foreach ($answers as $answer): ?>
                        <label>
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="<?php echo $answer['id']; ?>">
                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <textarea name="question_<?php echo $question['id']; ?>" rows="4" cols="50" required></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="submit-btn">Submit Exam</button>
    </form>
</body>
</html>