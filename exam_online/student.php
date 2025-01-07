<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    echo "You must be logged in as a student to access this page.";
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch student status
$stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Student not found.";
    exit();
}

$status = $student['status'];

// Fetch exam data if student is accepted
$exam = null;
$questions = [];
if ($status === 'accepted') {
    // Fetch the latest exam
    $stmt = $pdo->prepare("SELECT * FROM exams ORDER BY start_date DESC LIMIT 1");
    $stmt->execute();
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exam) {
        // Fetch questions and answers for the exam
        $stmt = $pdo->prepare("
            SELECT 
                q.id AS question_id,
                q.question_text,
                q.points,
                q.type,
                a.id AS answer_id,
                a.answer_text,
                a.is_correct
            FROM questions q
            LEFT JOIN answers a ON q.id = a.question_id
            WHERE q.exam_id = ?
        ");
        $stmt->execute([$exam['id']]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .exam-section {
            margin-bottom: 20px;
        }
        .exam-section h2 {
            margin-bottom: 10px;
        }
        .exam-section p {
            margin: 5px 0;
        }
        .exam-section .read-only {
            color: #888;
        }
        .exam-section .interactive {
            color: #28a745;
        }
        .exam-section .disabled {
            color: #dc3545;
        }
        .exam-section button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .exam-section button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .mini-game {
            text-align: center;
            margin-top: 20px;
        }
        .mini-game button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, Student!</h1>

        <?php if ($status === 'rejected'): ?>
            <!-- Rejected Student: Mini-Game and Rejection Message -->
            <div class="exam-section">
                <h2>Your Status: <span class="disabled">Rejected</span></h2>
                <p>You cannot take the exam. Here's a mini-game to pass the time!</p>
            </div>
            <div class="mini-game">
                <p>Click the button to play:</p>
                <button onclick="playMiniGame()">Play Mini-Game</button>
                <p id="gameResult"></p>
            </div>
        <?php elseif ($status === 'pending'): ?>
            <!-- Pending Student: Waiting Message -->
            <div class="exam-section">
                <h2>Your Status: <span class="read-only">Pending</span></h2>
                <p>Your account is under review. Please wait for approval to take the exam.</p>
            </div>
        <?php elseif ($status === 'accepted' && $exam): ?>
            <!-- Accepted Student: Exam Interface -->
            <div class="exam-section">
                <h2>Exam: <?= htmlspecialchars($exam['title']) ?></h2>
                <p><strong>Description:</strong> <?= htmlspecialchars($exam['description']) ?></p>
                <p><strong>Duration:</strong> <?= $exam['duration'] ?> minutes</p>
                <p><strong>Status:</strong> <span class="interactive">Accepted (You can take the exam)</span></p>
            </div>

            <div class="exam-section">
                <h2>Questions</h2>
                <form id="examForm">
                    <?php foreach ($questions as $question): ?>
                        <div class="question">
                            <p><strong>Question:</strong> <?= htmlspecialchars($question['question_text']) ?></p>
                            <?php if ($question['type'] === 'mcq'): ?>
                                <!-- Multiple Choice Question -->
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
                                $stmt->execute([$question['question_id']]);
                                $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($answers as $answer): ?>
                                    <label>
                                        <input type="radio" name="question_<?= $question['question_id'] ?>" value="<?= $answer['id'] ?>">
                                        <?= htmlspecialchars($answer['answer_text']) ?>
                                    </label><br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Open-Ended Question -->
                                <textarea name="question_<?= $question['question_id'] ?>" placeholder="Your answer"></textarea>
                            <?php endif; ?>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                    <button type="button" onclick="submitExam()">Submit Exam</button>
                </form>
            </div>
        <?php else: ?>
            <!-- No Exam Available -->
            <div class="exam-section">
                <h2>No Exam Available</h2>
                <p>There are no exams available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Mini-Game Functionality
        function playMiniGame() {
            const result = Math.random() < 0.5 ? "You win!" : "You lose!";
            document.getElementById("gameResult").textContent = result;
        }

        // Submit Exam Functionality
        function submitExam() {
            const form = document.getElementById("examForm");
            const formData = new FormData(form);

            // Simulate submitting the exam (replace with actual API call)
            alert("Exam submitted! Your answers have been recorded.");
            console.log("Submitted Answers:", Object.fromEntries(formData.entries()));
        }
    </script>
</body>
</html>