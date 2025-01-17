
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Exam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .neo-gradient {
            background: linear-gradient(120deg, #1a365d 0%, #2d3748 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-2px);
        }
        .answer-section {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .grade-input {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: white;
            padding: 0.5rem;
            width: 100px;
            transition: all 0.3s ease;
        }
        .grade-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #4299e1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        .save-button {
            background: #4299e1;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .save-button:hover {
            background: #3182ce;
            transform: translateY(-1px);
        }
        .save-button:active {
            transform: translateY(0);
        }
        .status-indicator {
            transition: all 0.3s ease;
            opacity: 0;
        }
        .status-indicator.show {
            opacity: 1;
        }
        .correct-answer {
            border-left: 4px solid #48bb78;
            background: rgba(72, 187, 120, 0.1);
        }
        .incorrect-answer {
            border-left: 4px solid #f56565;
            background: rgba(245, 101, 101, 0.1);
        }
    </style>
</head>
<body class="neo-gradient min-h-screen text-gray-100 p-6">
    <div class="container mx-auto max-w-4xl">
        <div id="studentInfo" class="glass-card p-6 mb-8 rounded-xl">
            <h1 class="text-3xl font-bold mb-4">Grading Exam</h1>
            <div id="studentDetails" class="text-gray-300"></div>
        </div>
        <div id="answersContainer" class="space-y-6"></div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const studentId = urlParams.get('student_id');
        const examId = urlParams.get('exam_id');

        function loadExamData() {
            fetch(`get_student_answers.php?student_id=${studentId}&exam_id=${examId}`)
                .then(response => response.json())
                .then(data => {
                    // Display student info
                    const studentDetails = document.getElementById('studentDetails');
                    studentDetails.innerHTML = `
                        <p class="text-xl">${data.student.first_name} ${data.student.last_name}</p>
                        <p class="text-sm">${data.student.fillier} - ${data.student.group_column}</p>
                    `;

                    // Display answers
                    const answersContainer = document.getElementById('answersContainer');
                    answersContainer.innerHTML = '';

                    data.answers.forEach((answer, index) => {
                        const answerCard = document.createElement('div');
                        answerCard.className = 'glass-card p-6 rounded-xl';
                        
                        let answerContent = `
                            <h3 class="text-xl font-semibold mb-4">Question ${index + 1}</h3>
                            <p class="mb-2">${answer.question_text}</p>
                            <p class="text-sm text-gray-400 mb-4">Type: ${answer.type.toUpperCase()} - Max Points: ${answer.points}</p>
                            <div class="answer-section">
                                <p class="text-sm text-gray-400">Student's Answer:</p>
                                <p class="mt-1">${answer.student_answer}</p>
                            </div>
                        `;

                        if (answer.type === 'mcq') {
                            const isCorrect = answer.is_correct;
                            answerContent += `
                                <div class="${isCorrect ? 'correct-answer' : 'incorrect-answer'} p-4 mt-4">
                                    <p class="flex items-center">
                                        <i class="fas fa-${isCorrect ? 'check text-green-500' : 'times text-red-500'} mr-2"></i>
                                        ${isCorrect ? 'Correct Answer' : 'Incorrect Answer'}
                                    </p>
                                    <p class="text-sm mt-2">Points Awarded: ${answer.points_awarded}/${answer.points}</p>
                                </div>
                            `;
                        } else {
                            answerContent += `
                                <div class="mt-6 flex items-center space-x-4">
                                    <input type="number" 
                                           class="grade-input" 
                                           min="0" 
                                           max="${answer.points}" 
                                           value="${answer.given_points || ''}"
                                           placeholder="0"
                                           onchange="saveGrade(${answer.submission_id}, this)">
                                    <button onclick="saveGrade(${answer.submission_id}, this.previousElementSibling)"
                                            class="save-button">
                                        <i class="fas fa-save mr-2"></i>Save
                                    </button>
                                    <span class="status-indicator" id="status-${answer.submission_id}">
                                        <i class="fas fa-check text-green-500"></i> Saved
                                    </span>
                                </div>
                            `;
                        }

                        answerCard.innerHTML = answerContent;
                        answersContainer.appendChild(answerCard);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        function saveGrade(submissionId, input) {
            const points = input.value;
            const statusIndicator = document.getElementById(`status-${submissionId}`);

            fetch('save_grade.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ submission_id: submissionId, points: points })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusIndicator.classList.add('show');
                    setTimeout(() => {
                        statusIndicator.classList.remove('show');
                    }, 2000);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', loadExamData);
    </script>
</body>
</html>
