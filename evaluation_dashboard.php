<?php
// evaluation_dashboard.php
require_once __DIR__ . '/config.php';
requireLogin();

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    die("Erreur de connexion à la base de données.");
}
$mysqli->set_charset('utf8mb4');

// Check if evaluations table exists, if not create it
$table_check = $mysqli->query("SHOW TABLES LIKE 'evaluations'");
if ($table_check->num_rows == 0) {
    $create_sql = "CREATE TABLE `evaluations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `school_year` varchar(20) DEFAULT '2024/2025',
        `oral_1` varchar(1) DEFAULT NULL,
        `oral_2` varchar(1) DEFAULT NULL,
        `oral_3` varchar(1) DEFAULT NULL,
        `reading_1` varchar(1) DEFAULT NULL,
        `reading_2` varchar(1) DEFAULT NULL,
        `reading_3` varchar(1) DEFAULT NULL,
        `comp_1` varchar(1) DEFAULT NULL,
        `comp_2` varchar(1) DEFAULT NULL,
        `comp_3` varchar(1) DEFAULT NULL,
        `prod_1` varchar(1) DEFAULT NULL,
        `prod_2` varchar(1) DEFAULT NULL,
        `prod_3` varchar(1) DEFAULT NULL,
        `prod_4` varchar(1) DEFAULT NULL,
        `global_mastery` varchar(50) DEFAULT NULL,
        `evaluated_date` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_student_year` (`student_id`, `school_year`),
        KEY `student_id` (`student_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$mysqli->query($create_sql)) {
        die("Erreur lors de la création de la table evaluations: " . $mysqli->error);
    }
}

// Get statistics
$stats = [];
$selected_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Get classes
$classes = [];
$res = $mysqli->query("SELECT id, name FROM classes ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $classes[] = $row;
}

// Calculate statistics if class selected
if ($selected_class > 0) {
    $where = "WHERE s.class_id = $selected_class";
    
    // Total students
    $result = $mysqli->query("SELECT COUNT(*) as total FROM students $where");
    $stats['total_students'] = $result->fetch_assoc()['total'];
    
    // Evaluated students
    $result = $mysqli->query("SELECT COUNT(DISTINCT s.id) as evaluated 
        FROM students s 
        INNER JOIN evaluations e ON s.id = e.student_id 
        $where");
    $stats['evaluated'] = $result->fetch_assoc()['evaluated'];
    
    // Distribution by global mastery
    $result = $mysqli->query("SELECT e.global_mastery, COUNT(*) as count 
        FROM students s 
        INNER JOIN evaluations e ON s.id = e.student_id 
        $where AND e.global_mastery IS NOT NULL
        GROUP BY e.global_mastery");
    
    $stats['mastery_distribution'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['mastery_distribution'][$row['global_mastery']] = $row['count'];
    }
    
    // Get detailed student data with NEW column names (oral_1, oral_2, etc.)
    $students = [];
    $query = "SELECT s.id, s.name, s.dob,
        e.oral_1, e.oral_2, e.oral_3,
        e.reading_1, e.reading_2, e.reading_3,
        e.comp_1, e.comp_2, e.comp_3,
        e.prod_1, e.prod_2, e.prod_3, e.prod_4,
        e.global_mastery, e.evaluated_date
        FROM students s 
        LEFT JOIN evaluations e ON s.id = e.student_id
        $where
        ORDER BY s.name";
    
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord des Évaluations</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-section select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #6b7280;
            font-size: 14px;
        }
        .mastery-chart {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .mastery-chart h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .mastery-bar {
            margin-bottom: 15px;
        }
        .mastery-bar .label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
            color: #374151;
        }
        .mastery-bar .bar {
            height: 30px;
            background: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
        }
        .mastery-bar .fill {
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 15px;
            color: white;
            font-weight: 600;
            font-size: 12px;
            transition: width 0.5s ease;
        }
        .mastery-a { background: #10b981; }
        .mastery-b { background: #3b82f6; }
        .mastery-c { background: #f59e0b; }
        .mastery-d { background: #ef4444; }
        
        .students-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .students-table h3 {
            padding: 20px;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            position: sticky;
            top: 0;
        }
        td {
            color: #6b7280;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            min-width: 30px;
        }
        .badge-a { background: #d1fae5; color: #065f46; }
        .badge-b { background: #dbeafe; color: #1e40af; }
        .badge-c { background: #fed7aa; color: #92400e; }
        .badge-d { background: #fee2e2; color: #991b1b; }
        .badge-none { background: #f3f4f6; color: #6b7280; }
        
        .actions {
            padding: 20px;
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Tableau de Bord des Évaluations</h1>
        <p>Visualisez et analysez les performances de vos élèves en langue française</p>
    </div>

    <div class="filter-section">
        <form method="get">
            <select name="class_id" onchange="this.form.submit()">
                <option value="">-- Sélectionner une classe --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selected_class > 0): ?>
        
        <div class="actions">
            <a href="evaluate_students.php?class_id=<?php echo $selected_class; ?>" class="btn btn-primary">
                ✏️ Évaluer les élèves
            </a>
            <a href="export_pdf_with_evaluation.php?class_id=<?php echo $selected_class; ?>" class="btn btn-success" target="_blank">
                📄 Exporter en PDF
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $stats['total_students']; ?></div>
                <div class="label">Élèves au total</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['evaluated']; ?></div>
                <div class="label">Élèves évalués</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php 
                    $percent = $stats['total_students'] > 0 
                        ? round(($stats['evaluated'] / $stats['total_students']) * 100) 
                        : 0;
                    echo $percent . '%';
                    ?>
                </div>
                <div class="label">Taux d'évaluation</div>
            </div>
        </div>

        <?php if (!empty($stats['mastery_distribution'])): ?>
            <div class="mastery-chart">
                <h3>Distribution par niveau de maîtrise</h3>
                
                <?php
                $mastery_levels = [
                    'Maîtrise très satisfaisante' => ['class' => 'mastery-a', 'short' => 'A'],
                    'Maîtrise satisfaisante' => ['class' => 'mastery-b', 'short' => 'B'],
                    'Maîtrise peu satisfaisante' => ['class' => 'mastery-c', 'short' => 'C'],
                    'Maîtrise non satisfaisante' => ['class' => 'mastery-d', 'short' => 'D']
                ];
                
                $total_evaluated = array_sum($stats['mastery_distribution']);
                
                foreach ($mastery_levels as $level => $config):
                    $count = $stats['mastery_distribution'][$level] ?? 0;
                    $percentage = $total_evaluated > 0 ? ($count / $total_evaluated) * 100 : 0;
                ?>
                    <div class="mastery-bar">
                        <div class="label">
                            <span><?php echo $level; ?> (<?php echo $config['short']; ?>)</span>
                            <span><?php echo $count; ?> élève(s) - <?php echo round($percentage, 1); ?>%</span>
                        </div>
                        <div class="bar">
                            <div class="fill <?php echo $config['class']; ?>" style="width: <?php echo $percentage; ?>%">
                                <?php if ($percentage > 15): ?>
                                    <?php echo $count; ?> élève(s)
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($students)): ?>
            <div class="students-table">
                <h3>Détails des évaluations par élève</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nom de l'élève</th>
                            <th>Date de naissance</th>
                            <th>Oral</th>
                            <th>Lecture</th>
                            <th>Compréhension</th>
                            <th>Production</th>
                            <th>Maîtrise globale</th>
                            <th>Date d'évaluation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['dob'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $oral_scores = array_filter([
                                        $student['oral_1'] ?? null, 
                                        $student['oral_2'] ?? null, 
                                        $student['oral_3'] ?? null
                                    ]);
                                    if (count($oral_scores) > 0) {
                                        $oral_display = implode(', ', $oral_scores);
                                        $first_score = strtolower($oral_scores[0]);
                                        echo "<span class='badge badge-{$first_score}'>{$oral_display}</span>";
                                    } else {
                                        echo "<span class='badge badge-none'>-</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $reading_scores = array_filter([
                                        $student['reading_1'] ?? null, 
                                        $student['reading_2'] ?? null, 
                                        $student['reading_3'] ?? null
                                    ]);
                                    if (count($reading_scores) > 0) {
                                        $reading_display = implode(', ', $reading_scores);
                                        $first_score = strtolower($reading_scores[0]);
                                        echo "<span class='badge badge-{$first_score}'>{$reading_display}</span>";
                                    } else {
                                        echo "<span class='badge badge-none'>-</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $comp_scores = array_filter([
                                        $student['comp_1'] ?? null, 
                                        $student['comp_2'] ?? null, 
                                        $student['comp_3'] ?? null
                                    ]);
                                    if (count($comp_scores) > 0) {
                                        $comp_display = implode(', ', $comp_scores);
                                        $first_score = strtolower($comp_scores[0]);
                                        echo "<span class='badge badge-{$first_score}'>{$comp_display}</span>";
                                    } else {
                                        echo "<span class='badge badge-none'>-</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $prod_scores = array_filter([
                                        $student['prod_1'] ?? null, 
                                        $student['prod_2'] ?? null, 
                                        $student['prod_3'] ?? null, 
                                        $student['prod_4'] ?? null
                                    ]);
                                    if (count($prod_scores) > 0) {
                                        $prod_display = implode(', ', $prod_scores);
                                        $first_score = strtolower($prod_scores[0]);
                                        echo "<span class='badge badge-{$first_score}'>{$prod_display}</span>";
                                    } else {
                                        echo "<span class='badge badge-none'>-</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($student['global_mastery']) {
                                        $mastery_class = 'none';
                                        if (strpos($student['global_mastery'], 'très') !== false) $mastery_class = 'a';
                                        elseif (strpos($student['global_mastery'], 'satisfaisante') !== false && strpos($student['global_mastery'], 'peu') === false) $mastery_class = 'b';
                                        elseif (strpos($student['global_mastery'], 'peu') !== false) $mastery_class = 'c';
                                        elseif (strpos($student['global_mastery'], 'non') !== false) $mastery_class = 'd';
                                        
                                        echo "<span class='badge badge-$mastery_class'>" . htmlspecialchars($student['global_mastery']) . "</span>";
                                    } else {
                                        echo "<span class='badge badge-none'>Non évalué</span>";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $student['evaluated_date'] ? date('d/m/Y', strtotime($student['evaluated_date'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3>Sélectionnez une classe</h3>
            <p>Choisissez une classe dans le menu déroulant ci-dessus pour voir les évaluations</p>
        </div>
    <?php endif; ?>

    <?php $mysqli->close(); ?>
</body>
</html>