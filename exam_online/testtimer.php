<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Include previous CSS styles -->
    <style>
        .exam-list {
            display: grid;
            gap: 1.5rem;
        }

        .exam-item {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 1rem;
        }

        .exam-status {
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-upcoming {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-available {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-expired {
            background: #fee2e2;
            color: #dc2626;
        }

        .exam-timer {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>My Exams</h1>
            <p>View and take your scheduled exams</p>
        </header>

        <div class="exam-list">
            <!-- Exam cards will be dynamically inserted here -->
        </div>
    </div>

    <script>
        class ExamList {
            constructor() {
                this.examsList = document.querySelector('.exam-list');
                this.loadExams();
                setInterval(() => this.updateExamStatuses(), 1000);
            }

            loadExams() {
                // Simulated exam data - replace with actual API call
                const exams = [
                    {
                        id: 1,
                        title: 'Sample Exam 1',
                        description: 'This is a sample exam',
                        startDate: new Date(Date.now() + 3600000), // 1 hour from now
                        endDate: new Date(Date.now() + 7200000),   // 2 hours from now
                        duration: 60
                    }
                    // Add more exams
                ];

                exams.forEach(exam => this.renderExamCard(exam));
            }

            renderExamCard(exam) {
                const card = document.createElement('section');
                card.className = 'card exam-item';
                card.dataset.examId = exam.id;

                card.innerHTML = `
                    <div>
                        <h3>${exam.title}</h3>
                        <p>${exam.description}</p>
                        <div class="exam-timer" data-start="${exam.startDate}" 
                             data-end="${exam.endDate}" data-duration="${exam.duration}">
                        </div>
                    </div>
                    <div>
                        <span class="exam-status"></span>
                        <button class="btn btn-primary" style="display:none">
                            Start Exam
                        </button>
                    </div>
                `;

                this.examsList.appendChild(card);
                this.updateExamStatus(card);
            }

            updateExamStatuses() {
                document.querySelectorAll('.exam-item').forEach(card => {
                    this.updateExamStatus(card);
                });
            }

            updateExamStatus(card) {
                const timerEl = card.querySelector('.exam-timer');
                const statusEl = card.querySelector('.exam-status');
                const startBtn = card.querySelector('.btn');
                
                const startDate = new Date(timerEl.dataset.start);
                const endDate = new Date(timerEl.dataset.end);
                const now = new Date();

                if (now < startDate) {
                    // Upcoming exam
                    const timeToStart = this.formatTimeRemaining(startDate - now);
                    statusEl.textContent = 'Upcoming';
                    statusEl.className = 'exam-status status-upcoming';
                    timerEl.textContent = `Starts in: ${timeToStart}`;
                    startBtn.style.display = 'none';
                } else if (now >= startDate && now <= endDate) {
                    // Available exam
                    const timeToEnd = this.formatTimeRemaining(endDate - now);
                    statusEl.textContent = 'Available';
                    statusEl.className = 'exam-status status-available';
                    timerEl.textContent = `Available for: ${timeToEnd}`;
                    startBtn.style.display = 'block';
                } else {
                    // Expired exam
                    statusEl.textContent = 'Expired';
                    statusEl.className = 'exam-status status-expired';
                    timerEl.textContent = 'Exam has ended';
                    startBtn.style.display = 'none';
                }
            }

            formatTimeRemaining(ms) {
                const hours = Math.floor(ms / 3600000);
                const minutes = Math.floor((ms % 3600000) / 60000);
                const seconds = Math.floor((ms % 60000) / 1000);
                return `${hours}h ${minutes}m ${seconds}s`;
            }
        }

        // Initialize exam list
        new ExamList();
    </script>
</body>
</html>