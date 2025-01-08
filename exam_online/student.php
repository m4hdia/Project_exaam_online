<?php
session_start();
include 'config.php'; 


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT fillier, group_column, status FROM users WHERE user_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

$status = $student['status'];

if ($status !== 'accepted') {
    die("You are not authorized to access exams. Your status is: " . ucfirst($status));
}

$fillier = $student['fillier'];
$group_column = $student['group_column'];

$stmt = $pdo->prepare("SELECT * FROM exams WHERE filiere_id = ? AND group_id = ? AND status = 'not_started'");
$stmt->execute([$fillier, $group_column]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>This page just for test i will work for it !!!</title>
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
        .exam-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        .exam-card {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .exam-card h3 {
            margin: 0;
            color: #4CAF50;
        }
        .exam-card p {
            margin: 5px 0;
        }
        .start-exam-btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .start-exam-btn:hover {
            background-color: #45a049;
        }
        .message {
            text-align: center;
            color: #f44336;
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php if ($status === 'accepted'): ?>
        <h2>Available Exams</h2>
        <div class="exam-list">
            <?php if (empty($exams)): ?>
                <p>No exams available at the moment.</p>
            <?php else: ?>
                <?php foreach ($exams as $exam): ?>
                    <div class="exam-card">
                        <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                        <p><?php echo htmlspecialchars($exam['description']); ?></p>
                        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($exam['start_date']); ?></p>
                        <p><strong>End Date:</strong> <?php echo htmlspecialchars($exam['end_date']); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam['duration']); ?> minutes</p>
                        <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="start-exam-btn">Start Exam</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="message">
            You are not authorized to access exams. Your status is: <?php echo ucfirst($status); ?>
        </div>
    <?php endif; ?>
</body>
</html>