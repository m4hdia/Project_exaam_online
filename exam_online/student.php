<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Include previous CSS styles -->
    <style>
        .exam-header {
            position: sticky;
            top: 0;
            background: white;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .timer-warning {
            animation: pulse 2s infinite;
            color: var(--danger);
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .question-navigation {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .nav-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-dot.active {
            background: var(--primary);
            color: white;
        }

        .nav-dot.answered {
            background: var(--success);
            color: white;
        }
    </style>
</head>
<body>
    <div class="exam-header">
        <h2 id="examTitle">Exam Title</h2>
        <div id="examTimer" class="exam-timer"></div>
    </div>

    <div class="container">
        <div class="question-navigation" id="questionNav"></div>
        <div id="questionContainer" class="card"></div>

        <div class="action-buttons">
            <button type="button" class="btn btn-secondary" id="prevQuestion">
                <i class="fas fa-arrow-left"></i>
                Previous
            </button>
            <button type="button" class="btn btn-secondary" id="nextQuestion">
                Next
                <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn btn-primary" id="submitExam">
                <i class="fas fa-paper-plane"></i>
                Submit Exam
            </button>
        </div>
    </div>

    <script>
        class ExamTaker {
            constructor(examId, duration) {
                this.examId = examId;
                this.duration = duration * 60; // Convert to seconds
                this.currentQuestion = 0;
                this.answers = {};
                this.timeRemaining = this.duration;
                
                this.initializeExam();
                this.startTimer();
                this.setupEventListeners();
            }

            initializeExam() {
                // Load exam data - replace with actual API call
                this.examData = {
                    title: 'Sample Exam',
                    questions: [
                        {
                            id: 1,
                            type: 'mcq',
                            text: 'Sample question 1',
                            options: ['Option 1', 'Option 2', 'Option 3']
                        }
                        // Add more questions
                    ]
                };

                document.getElementById('examTitle').textContent = this.examData.title;
                this.renderQuestionNavigation();
                this.showQuestion(0);
            }

            startTimer() {
                const timerEl = document.getElementById('examTimer');
                
                const updateTimer = () => {
                    if (this.timeRemaining <= 0) {
                        this.submitExam();
                        return;
                    }

                    const minutes = Math.floor(this.timeRemaining / 60);
                    const seconds = this.timeRemaining % 60;
                    timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (this.timeRemaining <= 300) { // 5 minutes warning
                        timerEl.classList.add('timer-warning');
                    }
                    
                    this.timeRemaining--;
                };

                updateTimer();
                this.timer = setInterval(updateTimer, 1000);
            }

            renderQuestionNavigation() {
                const nav = document.getElementById('questionNav');
                nav.innerHTML = '';
                
                this.examData.questions.forEach((_, index) => {
                    const dot = document.createElement('div');
                    dot.className = `nav-dot ${index === this.currentQuestion ? 'active' : ''} 
                                   ${this.answers[index] ? 'answered' : ''}`;
                    dot.textContent = index + 1;
                    dot.onclick = () => this.showQuestion(index);
                    nav.appendChild(dot);
                });
            }

            showQuestion(index) {
                const question = this.examData.questions[index];
                const container = document.getElementById('questionContainer');
                
                container.innerHTML = `
                    <h3>Question ${index + 1}</h3>
                    <p>${question.text}</p>
                    ${question.type === 'mcq' ? this.renderMCQOptions(question, index) : 
                      this.renderOpenQuestion(index)}
                `;

                this.currentQuestion = index;
                this.updateNavigation();
            }

            renderMCQOptions(question, index) {
                return `
                    <div class="options-container">
                        ${question.options.map((option, i) => `
                            <div class="answer-option">
                                <input type="radio" name="q${index}" value="${i}"
                                    ${this.answers[index] === i ? 'checked' : ''}>
                                <label>${option}</label>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            renderOpenQuestion(index) {
                return `
                    <textarea class="input-field" rows="4"
                        >${this.answers[index] || ''}</textarea>
                `;
            }

            updateNavigation() {
                document.querySelectorAll('.nav-dot').forEach((dot, index) => {
                    dot.classList.toggle('active', index === this.currentQuestion);
                    dot.classList.toggle('answered', this.answers[index] !== undefined);
                });
            }