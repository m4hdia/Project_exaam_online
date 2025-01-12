<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

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

    // Handle form submission
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

        // Update or insert questions
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $question_id => $question) {
                $q_text = htmlspecialchars(trim($question['text']));
                $q_points = (int)$question['points'];
                $q_type = isset($question['answers']) && is_array($question['answers']) ? 'mcq' : 'open';

                if (isset($organized_questions[$question_id])) {
                    // Update the question
                    $stmt = $pdo->prepare("
                        UPDATE questions 
                        SET question_text = ?, points = ?, type = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$q_text, $q_points, $q_type, $question_id]);

                    // Delete existing answers for this question (if MCQ)
                    if ($q_type === 'mcq') {
                        $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
                        $stmt->execute([$question_id]);
                    }
                } else {
                    // Insert new question
                    $stmt = $pdo->prepare("
                        INSERT INTO questions (exam_id, question_text, points, type)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$exam_id, $q_text, $q_points, $q_type]);
                    $question_id = $pdo->lastInsertId();
                }

                // Insert new answers (only for MCQ questions)
                if ($q_type === 'mcq' && isset($question['answers'])) {
                    foreach ($question['answers'] as $answer) {
                        $a_text = htmlspecialchars(trim($answer['text']));
                        $is_correct = !empty($answer['correct']) ? 1 : 0;

                        $stmt = $pdo->prepare("
                            INSERT INTO answers (question_id, answer_text, is_correct)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$question_id, $a_text, $is_correct]);
                    }
                }
            }
        }

        $pdo->commit();

        $_SESSION['success_message'] = "Exam updated successfully!";
        header("Location: teacher.php");
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
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #4f46e5;
    --primary-hover: #4338ca;
    --danger-color: #ef4444;
    --danger-hover: #dc2626;
    --success-color: #22c55e;
    --background-color: #f8fafc;
    --card-background: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --radius-md: 0.5rem;
    --radius-sm: 0.375rem;
}

body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background-color: var(--background-color);
    color: var(--text-primary);
    line-height: 1.5;
}

.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.header p {
    color: var(--text-secondary);
    font-size: 1.125rem;
}

.card {
    background: var(--card-background);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
}

.question-card {
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.question-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.05);
}

.input-group {
    margin-bottom: 1.5rem;
}

.input-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.input-field {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.input-field:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

select.input-field {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    appearance: none;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.answers-container {
    margin-top: 1rem;
}

.answer-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: var(--background-color);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
}

.custom-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.25rem;
    border: 2px solid var(--border-color);
    cursor: pointer;
    transition: all 0.2s ease;
}

.custom-checkbox:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
}

.btn-secondary {
    background-color: white;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background-color: var(--background-color);
}

.delete-btn {
    background: none;
    border: none;
    color: var(--danger-color);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
}

.delete-btn:hover {
    background-color: #fee2e2;
    color: var(--danger-hover);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}

/* File input styling */
input[type="file"] {
    padding: 0.5rem 0;
}

input[type="file"]::file-selector-button {
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    background-color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-right: 1rem;
}

input[type="file"]::file-selector-button:hover {
    background-color: var(--background-color);
}

/* Small text styling */
small {
    color: var(--text-secondary);
    display: block;
    margin-top: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container {
        padding: 0 1rem;
        margin: 1rem auto;
    }

    .header h1 {
        font-size: 2rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .answer-option {
        flex-direction: column;
        gap: 0.5rem;
    }
}
/* Style for the return button */
.btn-return {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: var(--primary-color);
    color: white;
    text-decoration: none; /* Remove underline */
    border: none;
    font-size: 1rem;
}

.btn-return:hover {
    background-color: var(--primary-hover);
    text-decoration: none; /* Ensure no underline on hover */
}
</style>
<!DOCTYPE html>
<html lang="en">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Edit Exam</h1>
            <p>Update your assessment with ease</p>
        </header>

        <form id="examForm" class="exam-form" method="POST" action="">
            <!-- Exam Details Section -->
            <section class="card exam-details">
                <h2>Exam Details</h2>
                <div class="form-grid">
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
                </div>
            </section>

            <!-- Questions Section -->
            <section class="card questions-section">
                <h2>Questions</h2>
                <div id="questionsContainer">
                    <?php foreach ($organized_questions as $question_id => $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <div class="question-type">
                                    <select name="questions[<?= $question_id ?>][type]" class="input-field" onchange="changeQuestionType(this, <?= $question_id ?>)">
                                        <option value="mcq" <?= $question['type'] === 'mcq' ? 'selected' : '' ?>>Multiple Choice</option>
                                        <option value="open" <?= $question['type'] === 'open' ? 'selected' : '' ?>>Open Question</option>
                                    </select>
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
                                    <div class="action-buttons">
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
            </section>

            <!-- Save Changes Button -->
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
            questionDiv.className = 'question-card';
            
            const typeText = type === 'mcq' ? 'Multiple Choice' : 'Open Question';
            const typeIcon = type === 'mcq' ? 'fas fa-list-ul' : 'fas fa-paragraph';

            questionDiv.innerHTML = `
                <div class="question-header">
                    <div class="question-type">
                        <select name="questions[${questionId}][type]" class="input-field" onchange="changeQuestionType(this, ${questionId})">
                            <option value="mcq" ${type === 'mcq' ? 'selected' : ''}>Multiple Choice</option>
                            <option value="open" ${type === 'open' ? 'selected' : ''}>Open Question</option>
                        </select>
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
                        <div class="action-buttons">
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

        function changeQuestionType(selectElement, questionId) {
            const questionCard = selectElement.closest('.question-card');
            const answersContainer = questionCard.querySelector('.answers-container');

            if (selectElement.value === 'open') {
                // Remove all answer inputs if the type is changed to Open Question
                if (answersContainer) {
                    answersContainer.innerHTML = '';
                }
            } else if (selectElement.value === 'mcq') {
                // Add default answer inputs if the type is changed to MCQ
                if (answersContainer) {
                    answersContainer.innerHTML = `
                        <div class="action-buttons">
                            <button type="button" class="btn btn-secondary" onclick="addAnswer(${questionId})">
                                <i class="fas fa-plus"></i>
                                Add Option
                            </button>
                        </div>
                    `;
                    addAnswer(questionId);
                    addAnswer(questionId);
                }
            }
        }
    </script>
</body>
</html>