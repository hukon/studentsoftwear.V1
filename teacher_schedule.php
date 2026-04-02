<?php
require_once __DIR__ . '/config.php';
requireLogin();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $pdo = getDB();
} catch(PDOException $e) {
    die("Connection failed. Please try again later.");
}

// Get all unique teachers
$teachersStmt = $pdo->query("SELECT DISTINCT teacher FROM schedules WHERE teacher IS NOT NULL AND teacher != '' ORDER BY teacher");
$teachers = $teachersStmt->fetchAll(PDO::FETCH_COLUMN);

// Get selected teacher
$selectedTeacher = isset($_GET['teacher']) ? $_GET['teacher'] : (count($teachers) > 0 ? $teachers[0] : null);

// Get schedules for selected teacher with class names
$schedules = [];
if ($selectedTeacher) {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as class_name 
        FROM schedules s 
        JOIN classes c ON s.class_id = c.id 
        WHERE s.teacher = ? 
        ORDER BY s.day_of_week, s.start_time
    ");
    $stmt->execute([$selectedTeacher]);
    $schedules = $stmt->fetchAll();
}

// Days of the week
$daysOfWeek = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

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
        'class_name' => $schedule['class_name'],
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
    <title>Emploi du temps - Enseignant</title>
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

        .teacher-selector {
            margin-bottom: 20px;
        }

        .teacher-selector select {
            padding: 10px 14px;
            border: 2px solid #d1d5db;
            font-size: 15px;
            background: #ffffff;
            width: 300px;
            cursor: pointer;
            color: #374151;
        }

        .teacher-selector select:focus {
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
            background: #10b981;
            color: #ffffff;
            padding: 6px 8px;
            font-size: 11px;
            line-height: 1.3;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            border: 1px solid #059669;
        }

        .schedule-item .subject {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .schedule-item .details {
            font-size: 10px;
            opacity: 0.9;
        }

        .no-schedule {
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            padding: 30px;
            background: #f9fafb;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .card {
                padding: 20px;
            }

            .teacher-selector select {
                width: 100%;
            }

            .timetable {
                min-width: 600px;
            }

            .schedule-grid {
                margin: 20px -20px;
                padding: 0 20px;
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

        @media (min-width: 769px) {
            .mobile-day-view {
                display: none;
            }

            .desktop-grid-view {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .mobile-day-view {
                display: block;
            }

            .desktop-grid-view {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Emploi du temps - Enseignant</h1>
            <p>Consulter l'emploi du temps des enseignants</p>
        </div>

        <div class="card">
            <div class="teacher-selector">
                <label for="teacherSelect">Sélectionner un enseignant:</label>
                <select id="teacherSelect" onchange="changeTeacher()">
                    <?php if (count($teachers) > 0): ?>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo htmlspecialchars($teacher); ?>" 
                                    <?php echo $selectedTeacher == $teacher ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">Aucun enseignant trouvé</option>
                    <?php endif; ?>
                </select>
                <a href="schedule.php" class="btn">Retour aux classes</a>
            </div>
        </div>

        <?php if ($selectedTeacher && $schedules): ?>
        <!-- Desktop Grid View -->
        <div class="card desktop-grid-view">
            <h2>Emploi du temps de <?php echo htmlspecialchars($selectedTeacher); ?></h2>
            <div class="schedule-grid">
                <table class="timetable">
                    <thead>
                        <tr>
                            <th class="time-slot">Temps</th>
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
                                                    <?php echo htmlspecialchars($item['class_name']); ?><br>
                                                    <?php if ($item['room']): ?>
                                                        <?php echo htmlspecialchars($item['room']); ?>
                                                    <?php endif; ?>
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
            <h2 style="padding: 0 0 20px 0;">Emploi du temps de <?php echo htmlspecialchars($selectedTeacher); ?></h2>
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
                                            <?php echo htmlspecialchars($schedule['class_name']); ?>
                                            <?php if ($schedule['room']): ?>
                                                - <?php echo htmlspecialchars($schedule['room']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-schedule">Pas de cours ce jour</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($selectedTeacher): ?>
        <div class="card">
            <div class="no-schedule">
                <h3>Aucun cours trouvé pour cet enseignant</h3>
            </div>
        </div>
        <?php elseif (count($teachers) == 0): ?>
        <div class="card">
            <div class="no-schedule">
                <h3>Aucun enseignant dans le système</h3>
                <p>Ajoutez des enseignants aux horaires dans la gestion des classes.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function changeTeacher() {
            const teacher = document.getElementById('teacherSelect').value;
            if (teacher) {
                window.location.href = 'teacher_schedule.php?teacher=' + encodeURIComponent(teacher);
            }
        }
    </script>
</body>
</html>