<!DOCTYPE html>
<html lang="en">
<!-- Previous head content remains the same -->
<body>
    <div class="container">
        <header class="header">
            <h1>Create New Exam</h1>
            <p>Design your perfect assessment</p>
        </header>

        <form class="exam-form">
            <section class="card">
                <div class="input-group">
                    <label for="examTitle">Exam Title</label>
                    <input type="text" id="examTitle" class="input-field" placeholder="Enter exam title" required>
                </div>

                <div class="input-group">
                    <label for="examDescription">Description</label>
                    <textarea id="examDescription" class="input-field" rows="4" placeholder="Provide exam description" required></textarea>
                </div>

                <!-- New date and time fields -->
                <div class="input-group">
                    <label for="examStartDate">Start Date & Time</label>
                    <input type="datetime-local" id="examStartDate" class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examEndDate">End Date & Time</label>
                    <input type="datetime-local" id="examEndDate" class="input-field" required>
                </div>

                <div class="input-group">
                    <label for="examDuration">Duration (minutes)</label>
                    <input type="number" id="examDuration" class="input-field" required min="1">
                </div>
            </section>

            <!-- Rest of the form remains the same -->
        </form>
    </div>

    <script>
        // Previous JavaScript remains the same
        
        // Add validation for dates
        document.querySelector('.exam-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const startDate = new Date(document.getElementById('examStartDate').value);
            const endDate = new Date(document.getElementById('examEndDate').value);
            const duration = parseInt(document.getElementById('examDuration').value);
            
            if (endDate <= startDate) {
                alert('End date must be after start date');
                return;
            }
            
            const durationInMs = duration * 60 * 1000;
            const availableTime = endDate - startDate;
            
            if (durationInMs > availableTime) {
                alert('Exam duration cannot be longer than the available time window');
                return;
            }
            
            // Submit form logic here
        });
    </script>
</body>
</html>