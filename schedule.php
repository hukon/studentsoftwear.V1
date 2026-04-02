<?php
require_once __DIR__ . '/config.php';
requireLogin();

// Errors logged server-side, not displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $pdo = getDB();
} catch(PDOException $e) {
    die("Connection failed. Please try again later.");
}

// Handle CRUD operations
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("INSERT INTO schedules (class_id, day_of_week, start_time, end_time, subject, teacher, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['class_id'],
                        $_POST['day_of_week'],
                        $_POST['start_time'],
                        $_POST['end_time'],
                        $_POST['subject'],
                        $_POST['teacher'] ?: null,
                        $_POST['room'] ?: null
                    ]);
                    $message = "Schedule entry added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding entry: " . $e->getMessage();
                }
                break;
            
            case 'edit':
                try {
                    $stmt = $pdo->prepare("UPDATE schedules SET class_id=?, day_of_week=?, start_time=?, end_time=?, subject=?, teacher=?, room=? WHERE id=?");
                    $stmt->execute([
                        $_POST['class_id'],
                        $_POST['day_of_week'],
                        $_POST['start_time'],
                        $_POST['end_time'],
                        $_POST['subject'],
                        $_POST['teacher'] ?: null,
                        $_POST['room'] ?: null,
                        $_POST['schedule_id']
                    ]);
                    $message = "Schedule entry updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating entry: " . $e->getMessage();
                }
                break;
            
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
                    $stmt->execute([$_POST['schedule_id']]);
                    $message = "Schedule entry deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting entry: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Get schedules for selected class
$selectedClassId = isset($_GET['class_id']) ? $_GET['class_id'] : (count($classes) > 0 ? $classes[0]['id'] : null);
$schedules = [];
if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE class_id = ? ORDER BY day_of_week, start_time");
    $stmt->execute([$selectedClassId]);
    $schedules = $stmt->fetchAll();
}

// Days of the week
$daysOfWeek = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi'];

// Time slots (8:00 to 18:00)
$timeSlots = [];
for ($hour = 8; $hour <= 17; $hour++) {
    $timeSlots[] = sprintf("%02d:00", $hour);
    $timeSlots[] = sprintf("%02d:30", $hour);
}

// Organize schedules by day and time
$scheduleGrid = [];
foreach ($schedules as $schedule) {
    $day = $schedule['day_of_week'];
    $startTime = substr($schedule['start_time'], 0, 5);
    $endTime = substr($schedule['end_time'], 0, 5);
    
    if (!isset($scheduleGrid[$day])) {
        $scheduleGrid[$day] = [];
    }
    
    $scheduleGrid[$day][$startTime] = [
        'id' => $schedule['id'],
        'subject' => $schedule['subject'],
        'teacher' => $schedule['teacher'],
        'room' => $schedule['room'],
        'end_time' => $endTime
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Organizer - Schedule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
            color: #2c3e50;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #ffffff;
            padding: 40px;
            border: 1px solid #e9ecef;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .header p {
            color: #6c757d;
            font-size: 1rem;
            margin: 0;
        }

        .card {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e9ecef;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .class-selector {
            margin-bottom: 20px;
        }

        .class-selector select {
            padding: 10px 14px;
            border: 2px solid #d1d5db;
            font-size: 15px;
            background: #ffffff;
            width: 220px;
            cursor: pointer;
            color: #374151;
        }

        .class-selector select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            background: #3b82f6;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            background: #2563eb;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.25);
        }

        .btn-danger {
            background: #dc2626;
        }

        .btn-danger:hover {
            background: #b91c1c;
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.25);
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #f0f9ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #d1d5db;
            font-size: 15px;
            background: #ffffff;
            color: #374151;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .schedule-grid {
            overflow-x: auto;
            margin-top: 20px;
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
            border: 1px solid #d1d5db;
            background: #ffffff;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #d1d5db;
            padding: 12px 8px;
            text-align: center;
            vertical-align: top;
            font-size: 13px;
        }

        .timetable th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid #d1d5db;
        }

        .timetable .time-slot {
            background: #f9fafb;
            font-weight: 600;
            width: 90px;
            position: sticky;
            left: 0;
            z-index: 5;
            color: #6b7280;
            font-size: 12px;
        }

        .schedule-cell {
            position: relative;
            height: 45px;
            background: #ffffff;
            min-width: 140px;
        }

        .schedule-item {
            background: #3b82f6;
            color: #ffffff;
            padding: 6px 8px;
            font-size: 11px;
            line-height: 1.3;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            border: 1px solid #2563eb;
        }

        .schedule-item .subject {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .schedule-item .details {
            font-size: 10px;
            opacity: 0.9;
        }

        .schedule-actions {
            position: absolute;
            top: 2px;
            right: 2px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .schedule-item:hover .schedule-actions {
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #e5e7eb;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h2 {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .close {
            color: #9ca3af;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            padding: 5px;
        }

        .close:hover {
            color: #374151;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .timetable {
                min-width: 600px;
            }

            .schedule-grid {
                margin: 20px -20px;
                padding: 0 20px;
            }

            .mobile-day-view {
                display: block;
            }

            .desktop-grid-view {
                display: none;
            }
        }

        @media (min-width: 769px) {
            .mobile-day-view {
                display: none;
            }

            .desktop-grid-view {
                display: block;
            }
        }

        .day-schedule {
            background: #ffffff;
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .day-header {
            background: #f9fafb;
            color: #374151;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .day-content {
            padding: 15px;
        }

        .time-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .time-entry:last-child {
            border-bottom: none;
        }

        .time-label {
            font-weight: 600;
            color: #6b7280;
            min-width: 100px;
            font-size: 13px;
        }

        .entry-details {
            flex: 1;
            margin-left: 15px;
        }

        .entry-subject {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .entry-meta {
            font-size: 12px;
            color: #6b7280;
        }

        .no-schedule {
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            padding: 30px;
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📅 Schedule Management</h1>
            <p>Manage class schedules for Student Organizer</p>
            <a href="teacher_schedule.php" class="btn">📚 Emploi du temps Enseignants</a>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Class Selector -->
        <div class="card">
            <div class="class-selector">
                <label for="classSelect">Select Class:</label>
                <select id="classSelect" onchange="changeClass()">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $selectedClassId == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" onclick="showAddModal()">➕ Add Schedule</button>
            </div>
        </div>

        <?php if ($selectedClassId && $schedules): ?>
        <!-- Desktop Grid View -->
        <div class="card desktop-grid-view">
            <h2>Weekly Schedule</h2>
            <div class="schedule-grid">
                <table class="timetable">
                    <thead>
                        <tr>
                            <th class="time-slot">Time</th>
                            <?php foreach ($daysOfWeek as $day): ?>
                                <th><?php echo $day; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $timeSlot): ?>
                            <tr>
                                <td class="time-slot"><?php echo $timeSlot; ?></td>
                                <?php foreach ($daysOfWeek as $day): ?>
                                    <td class="schedule-cell">
                                        <?php if (isset($scheduleGrid[$day][$timeSlot])): ?>
                                            <?php $item = $scheduleGrid[$day][$timeSlot]; ?>
                                            <div class="schedule-item">
                                                <div class="subject"><?php echo htmlspecialchars($item['subject']); ?></div>
                                                <div class="details">
                                                    <?php if ($item['teacher']): ?>
                                                        👨‍🏫 <?php echo htmlspecialchars($item['teacher']); ?><br>
                                                    <?php endif; ?>
                                                    <?php if ($item['room']): ?>
                                                        🏫 <?php echo htmlspecialchars($item['room']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="schedule-actions">
                                                    <button class="btn btn-small" onclick="editSchedule(<?php echo $item['id']; ?>)">✏️</button>
                                                    <button class="btn btn-danger btn-small" onclick="deleteSchedule(<?php echo $item['id']; ?>)">🗑️</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Day View -->
        <div class="mobile-day-view">
            <?php 
            $schedulesByDay = [];
            foreach ($schedules as $schedule) {
                $day = $schedule['day_of_week'];
                if (!isset($schedulesByDay[$day])) {
                    $schedulesByDay[$day] = [];
                }
                $schedulesByDay[$day][] = $schedule;
            }
            
            foreach ($daysOfWeek as $day): 
            ?>
                <div class="day-schedule">
                    <div class="day-header"><?php echo $day; ?></div>
                    <div class="day-content">
                        <?php if (isset($schedulesByDay[$day]) && count($schedulesByDay[$day]) > 0): ?>
                            <?php foreach ($schedulesByDay[$day] as $schedule): ?>
                                <div class="time-entry">
                                    <div class="time-label">
                                        <?php echo substr($schedule['start_time'], 0, 5); ?> - <?php echo substr($schedule['end_time'], 0, 5); ?>
                                    </div>
                                    <div class="entry-details">
                                        <div class="entry-subject"><?php echo htmlspecialchars($schedule['subject']); ?></div>
                                        <div class="entry-meta">
                                            <?php if ($schedule['teacher']): ?>
                                                👨‍🏫 <?php echo htmlspecialchars($schedule['teacher']); ?>
                                            <?php endif; ?>
                                            <?php if ($schedule['room']): ?>
                                                🏫 <?php echo htmlspecialchars($schedule['room']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-small" onclick="editSchedule(<?php echo $schedule['id']; ?>)">✏️</button>
                                        <button class="btn btn-danger btn-small" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">🗑️</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-schedule">No classes scheduled for this day</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($selectedClassId): ?>
        <div class="card">
            <div class="no-schedule">
                <h3>No schedules found for this class</h3>
                <p>Click "Add Schedule" to create the first entry.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Schedule</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="scheduleForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="schedule_id" id="scheduleId">
                <input type="hidden" name="class_id" id="classId" value="<?php echo $selectedClassId; ?>">
                
                <div class="form-group">
                    <label for="dayOfWeek">Day of Week:</label>
                    <select name="day_of_week" id="dayOfWeek" required>
                        <?php foreach ($daysOfWeek as $day): ?>
                            <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="startTime">Start Time:</label>
                        <input type="time" name="start_time" id="startTime" required>
                    </div>
                    <div class="form-group">
                        <label for="endTime">End Time:</label>
                        <input type="time" name="end_time" id="endTime" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="teacher">Teacher (optional):</label>
                    <input type="text" name="teacher" id="teacher">
                </div>
                
                <div class="form-group">
                    <label for="room">Room (optional):</label>
                    <input type="text" name="room" id="room">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn" id="submitBtn">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p>Are you sure you want to delete this schedule entry?</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="schedule_id" id="deleteScheduleId">
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function changeClass() {
            const classId = document.getElementById('classSelect').value;
            window.location.href = 'schedule.php?class_id=' + classId;
        }

        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Schedule';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Add Schedule';
            document.getElementById('scheduleForm').reset();
            document.getElementById('classId').value = '<?php echo $selectedClassId; ?>';
            document.getElementById('scheduleModal').style.display = 'block';
        }

        // Store schedule data for editing
        const scheduleData = {};
        <?php foreach ($schedules as $schedule): ?>
        scheduleData[<?php echo $schedule['id']; ?>] = {
            id: <?php echo $schedule['id']; ?>,
            class_id: <?php echo $schedule['class_id']; ?>,
            day_of_week: '<?php echo $schedule['day_of_week']; ?>',
            start_time: '<?php echo substr($schedule['start_time'], 0, 5); ?>',
            end_time: '<?php echo substr($schedule['end_time'], 0, 5); ?>',
            subject: '<?php echo htmlspecialchars($schedule['subject'], ENT_QUOTES); ?>',
            teacher: '<?php echo htmlspecialchars($schedule['teacher'] ?: '', ENT_QUOTES); ?>',
            room: '<?php echo htmlspecialchars($schedule['room'] ?: '', ENT_QUOTES); ?>'
        };
        <?php endforeach; ?>

        function editSchedule(scheduleId) {
            const schedule = scheduleData[scheduleId];
            if (!schedule) {
                alert('Schedule not found');
                return;
            }
            
            // Populate modal with existing data
            document.getElementById('modalTitle').textContent = 'Edit Schedule';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('submitBtn').textContent = 'Update Schedule';
            document.getElementById('scheduleId').value = schedule.id;
            document.getElementById('classId').value = schedule.class_id;
            document.getElementById('dayOfWeek').value = schedule.day_of_week;
            document.getElementById('startTime').value = schedule.start_time;
            document.getElementById('endTime').value = schedule.end_time;
            document.getElementById('subject').value = schedule.subject;
            document.getElementById('teacher').value = schedule.teacher;
            document.getElementById('room').value = schedule.room;
            
            // Show modal
            document.getElementById('scheduleModal').style.display = 'block';
        }

        function deleteSchedule(scheduleId) {
            document.getElementById('deleteScheduleId').value = scheduleId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const scheduleModal = document.getElementById('scheduleModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === scheduleModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>