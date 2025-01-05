<?php
require_once 'config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to edit an exam.";
    exit();
}

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($exam_id <= 0) {
    echo "Invalid exam ID.";
    exit();
}

try {
    // Fetch exam details
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        echo "Exam not found.";
        exit();
    }

    // Fetch questions and answers
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
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize questions and answers
    $organized_questions = [];
    foreach ($questions as $row) {
        $question_id = $row['question_id'];
        if (!isset($organized_questions[$question_id])) {
            $organized_questions[$question_id] = [
                'text' => $row['question_text'],
                'points' => $row['points'],
                'type' => $row['type'],
                'answers' => []
            ];
        }
        if ($row['answer_id']) {
            $organized_questions[$question_id]['answers'][] = [
                'id' => $row['answer_id'],
                'text' => $row['answer_text'],
                'correct' => $row['is_correct']
            ];
        }
    }

    // Handle form submission for updating the exam
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();

        // Update exam details
        $title = htmlspecialchars(trim($_POST['examTitle']));
        $description = htmlspecialchars(trim($_POST['examDescription']));
        $start_date = $_POST['examStartDate'];
        $end_date = $_POST['examEndDate'];
        $duration = (int)$_POST['examDuration'];

        if (strtotime($end_date) <= strtotime($start_date)) {
            throw new Exception('End date must be after start date.');
        }

        $stmt = $pdo->prepare("
            UPDATE exams 
            SET title = ?, description = ?, start_date = ?, end_date = ?, duration = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $start_date, $end_date, $duration, $exam_id]);

        // Process questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $question_id => $question) {
                $q_text = htmlspecialchars(trim($question['text']));
                $q_points = (int)$question['points'];
                $q_type = isset($question['answers']) && is_array($question['answers']) ? 'mcq' : 'open';

                // Update or insert question
                if (isset($organized_questions[$question_id])) {
                    $stmt = $pdo->prepare("
                        UPDATE questions 
                        SET question_text = ?, points = ?, type = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$q_text, $q_points, $q_type, $question_id]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO questions (exam_id, question_text, points, type)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$exam_id, $q_text, $q_points, $q_type]);
                    $question_id = $pdo->lastInsertId();
                }

                // Process answers for MCQ
                if ($q_type === 'mcq' && isset($question['answers'])) {
                    foreach ($question['answers'] as $answer_id => $answer) {
                        $a_text = htmlspecialchars(trim($answer['text']));
                        $is_correct = !empty($answer['correct']) ? 1 : 0;

                        if (isset($organized_questions[$question_id]['answers'][$answer_id])) {
                            $stmt = $pdo->prepare("
                                UPDATE answers 
                                SET answer_text = ?, is_correct = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([$a_text, $is_correct, $answer_id]);
                        } else {
                            $stmt = $pdo->prepare("
                                INSERT INTO answers (question_id, answer_text, is_correct)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$question_id, $a_text, $is_correct]);
                        }
                    }
                }
            }
        }

        $pdo->commit();

        // Display success message and redirect
        $_SESSION['success_message'] = "Exam updated successfully!";
        header("Location: teacher.php"); // Redirect to the teacher's page
        exit();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Error updating exam: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="createxam.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Edit Exam</h1>
            <p>Update your assessment</p>
        </header>

        <form id="examForm" class="exam-form" method="POST" action="">
            <section class="card">
                <div class="input-group">
                    <label for="examTitle">Exam Title</label>
                    <input type="text" id="examTitle" name="examTitle" class="input-field" 
                           value="<?= htmlspecialchars($exam['title']) ?>" required>
                </div>

                <div class="input-group">
                    <label for="examDescription">Description</label>
                    <textarea id="examDescription" name="examDescription" class="input-field" rows="4" required>
                        <?= htmlspecialchars($exam['description']) ?>
                    </textarea>
                </div>

                <div class="input-group">
                    <label for="examStartDate">Start Date & Time</label>
                    <input type="datetime-local" id="examStartDate" name="examStartDate" 
                           class="input-field" value="<?= date('Y-m-d\TH:i', strtotime($exam['start_date'])) ?>" required>
                </div>

                <div class="input-group">
                    <label for="examEndDate">End Date & Time</label>
                    <input type="datetime-local" id="examEndDate" name="examEndDate" 
                           class="input-field" value="<?= date('Y-m-d\TH:i', strtotime($exam['end_date'])) ?>" required>
                </div>

                <div class="input-group">
                    <label for="examDuration">Duration (minutes)</label>
                    <input type="number" id="examDuration" name="examDuration" 
                           class="input-field" value="<?= $exam['duration'] ?>" required min="1">
                </div>
            </section>

            <div id="questionsContainer">
                <?php foreach ($organized_questions as $question_id => $question): ?>
                    <div class="card question-card">
                        <div class="question-header">
                            <div class="question-type">
                                <i class="<?= $question['type'] === 'mcq' ? 'fas fa-list-ul' : 'fas fa-paragraph' ?>"></i>
                                <?= $question['type'] === 'mcq' ? 'Multiple Choice' : 'Open Question' ?>
                            </div>
                            <button type="button" class="delete-btn" onclick="removeQuestion(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <div class="input-group">
                            <label>Question Text</label>
                            <textarea name="questions[<?= $question_id ?>][text]" class="input-field" required>
                                <?= htmlspecialchars($question['text']) ?>
                            </textarea>
                        </div>

                        <div class="input-group">
                            <label>Points</label>
                            <input type="number" name="questions[<?= $question_id ?>][points]" 
                                   class="input-field" value="<?= $question['points'] ?>" required min="1">
                        </div>

                        <?php if ($question['type'] === 'mcq'): ?>
                            <div class="answers-container" id="answers_<?= $question_id ?>">
                                <?php foreach ($question['answers'] as $answer): ?>
                                    <div class="answer-option">
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox" class="custom-checkbox" 
                                                   name="questions[<?= $question_id ?>][answers][<?= $answer['id'] ?>][correct]" 
                                                   <?= $answer['correct'] ? 'checked' : '' ?>>
                                        </div>
                                        <input type="text" name="questions[<?= $question_id ?>][answers][<?= $answer['id'] ?>][text]" 
                                               class="input-field" value="<?= htmlspecialchars($answer['text']) ?>" required>
                                        <button type="button" class="delete-btn" onclick="this.closest('.answer-option').remove()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                                <div class="action-buttons" style="margin-top: 1rem;">
                                    <button type="button" class="btn btn-secondary" onclick="addAnswer(<?= $question_id ?>)">
                                        <i class="fas fa-plus"></i>
                                        Add Option
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn btn-secondary" onclick="addQuestion('mcq')">
                    <i class="fas fa-plus-circle"></i>
                    Add Multiple Choice
                </button>
                <button type="button" class="btn btn-secondary" onclick="addQuestion('open')">
                    <i class="fas fa-pen"></i>
                    Add Open Question
                </button>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        function addQuestion(type) {
            const container = document.getElementById('questionsContainer');
            const questionId = Date.now();
            const questionDiv = document.createElement('div');
            questionDiv.className = 'card question-card';
            
            const typeText = type === 'mcq' ? 'Multiple Choice' : 'Open Question';
            const typeIcon = type === 'mcq' ? 'fas fa-list-ul' : 'fas fa-paragraph';

            questionDiv.innerHTML = `
                <div class="question-header">
                    <div class="question-type">
                        <i class="${typeIcon}"></i>
                        ${typeText}
                    </div>
                    <button type="button" class="delete-btn" onclick="removeQuestion(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="input-group">
                    <label>Question Text</label>
                    <textarea name="questions[${questionId}][text]" class="input-field" required></textarea>
                </div>

                <div class="input-group">
                    <label>Points</label>
                    <input type="number" name="questions[${questionId}][points]" class="input-field" required min="1">
                </div>

                ${type === 'mcq' ? `
                    <div class="answers-container" id="answers_${questionId}">
                        <div class="action-buttons" style="margin-top: 1rem;">
                            <button type="button" class="btn btn-secondary" onclick="addAnswer(${questionId})">
                                <i class="fas fa-plus"></i>
                                Add Option
                            </button>
                        </div>
                    </div>
                ` : ''}
            `;

            container.appendChild(questionDiv);
            if (type === 'mcq') {
                addAnswer(questionId);
                addAnswer(questionId);
            }
        }

        function addAnswer(questionId) {
            const container = document.querySelector(`#answers_${questionId}`);
            const answerDiv = document.createElement('div');
            answerDiv.className = 'answer-option';
            
            const answerId = Date.now();
            answerDiv.innerHTML = `
                <div class="checkbox-wrapper">
                    <input type="checkbox" class="custom-checkbox" name="questions[${questionId}][answers][${answerId}][correct]">
                </div>
                <input type="text" name="questions[${questionId}][answers][${answerId}][text]" 
                       class="input-field" placeholder="Enter answer option" required>
                <button type="button" class="delete-btn" onclick="this.closest('.answer-option').remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.insertBefore(answerDiv, container.lastElementChild);
        }

        function removeQuestion(button) {
            button.closest('.question-card').remove();
        }
    </script>
</body>
</html>