<?php
require_once 'config.php';



session_start();
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to create an exam.";
    exit();
}


try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Begin transaction
        $pdo->beginTransaction();

        // Validate and sanitize input
        $title = htmlspecialchars(trim($_POST['examTitle']));
        $description = htmlspecialchars(trim($_POST['examDescription']));
        $start_date = $_POST['examStartDate'];
        $end_date = $_POST['examEndDate'];
        $duration = (int)$_POST['examDuration'];
        $current_timestamp = date('Y-m-d H:i:s');

        // Validate dates
        if (strtotime($end_date) <= strtotime($start_date)) {
            throw new Exception('End date must be after start date.');
        }

        // Insert exam details
        $stmt = $pdo->prepare("
            INSERT INTO exams (
                title, 
                description, 
                start_date, 
                end_date, 
                duration, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$title, $description, $start_date, $end_date, $duration, $current_timestamp, $current_timestamp]);

        $exam_id = $pdo->lastInsertId();

        // Process questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $question) {
                $q_text = htmlspecialchars(trim($question['text']));
                $q_points = (int)$question['points'];
                $q_type = isset($question['answers']) && is_array($question['answers']) ? 'mcq' : 'open';

                // Insert question
                $stmt = $pdo->prepare("
                    INSERT INTO questions (
                        exam_id, 
                        question_text, 
                        points, 
                        type
                    ) VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([$exam_id, $q_text, $q_points, $q_type]);

                $question_id = $pdo->lastInsertId();

                // Insert answers for MCQ
                if ($q_type === 'mcq' && isset($question['answers'])) {
                    foreach ($question['answers'] as $answer) {
                        $a_text = htmlspecialchars(trim($answer['text']));
                        $is_correct = !empty($answer['correct']) ? 1 : 0;

                        $stmt = $pdo->prepare("
                            INSERT INTO answers (
                                question_id, 
                                answer_text, 
                                is_correct
                            ) VALUES (?, ?, ?)
                        ");
                        
                        $stmt->execute([$question_id, $a_text, $is_correct]);
                    }
                }
            }
        }

        // Commit transaction
        $pdo->commit();

        // Display success message
       $_SESSION['success_message'] = "Exam Add successfully!";
        header("Location: teacher.php");
    }
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo 'Error creating exam: ' . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Exam Creator</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="createxam.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Create New Exam</h1>
            <p>Design your perfect assessment</p>
        </header>

        <form id="examForm" class="exam-form" method="POST" action="">
            <section class="card">
                <!-- Previous title and description fields remain -->

                <!-- New date and time fields -->
                <div class="input-group">
                    <label for="examStartDate">Start Date & Time</label>
                    <input type="datetime-local" id="examStartDate" name="examStartDate" 
                           class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examEndDate">End Date & Time</label>
                    <input type="datetime-local" id="examEndDate" name="examEndDate" 
                           class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examDuration">Duration (minutes)</label>
                    <input type="number" id="examDuration" name="examDuration" 
                           class="input-field" required min="1">
                </div>
            </section>
            <section class="card">
                <div class="input-group">
                    <label for="examTitle">Exam Title</label>
                    <input type="text" id="examTitle" name="examTitle" class="input-field" placeholder="Enter exam title" required>
                </div>

                <div class="input-group">
                    <label for="examDescription">Description</label>
                    <textarea id="examDescription" name="examDescription" class="input-field" rows="4" placeholder="Provide exam description" required></textarea>
                </div>
            </section>

            <div id="questionsContainer"></div>

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
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-save"></i>
                    Save Draft
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Publish Exam
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