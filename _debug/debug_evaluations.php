<?php
// debug_evaluations.php - See exactly what's in your database
session_start();

define('DB_HOST', 'sql308.infinityfree.com');
define('DB_NAME', 'if0_39971413_student');
define('DB_USER', 'if0_39971413');
define('DB_PASS', '2thxXBOi75vZOFT');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    die("Connection error: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// Get a specific class or first class
$selected_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Get all classes
$classes = [];
$result = $mysqli->query("SELECT id, name FROM classes ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
    if ($selected_class == 0) {
        $selected_class = $row['id']; // Auto-select first class
    }
}

// Get students with evaluations
$students = [];
if ($selected_class > 0) {
    $stmt = $mysqli->prepare("
        SELECT 
            s.id, s.name, s.pic_path,
            e.oral_1, e.oral_2, e.oral_3,
            e.reading_1, e.reading_2, e.reading_3,
            e.comp_1, e.comp_2, e.comp_3,
            e.prod_1, e.prod_2, e.prod_3, e.prod_4,
            e.evaluated_date
        FROM students s 
        LEFT JOIN evaluations e ON s.id = e.student_id AND e.school_year = '2025/2026'
        WHERE s.class_id = ? 
        ORDER BY s.name
    ");
    $stmt->bind_param("i", $selected_class);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

// Helper function to check if category is complete
function isCategoryComplete($student, $category) {
    if ($category == 'oral') {
        $fields = ['oral_1', 'oral_2', 'oral_3'];
    } elseif ($category == 'reading') {
        $fields = ['reading_1', 'reading_2', 'reading_3'];
    } elseif ($category == 'comp') {
        $fields = ['comp_1', 'comp_2', 'comp_3'];
    } elseif ($category == 'prod') {
        $fields = ['prod_1', 'prod_2', 'prod_3', 'prod_4'];
    }
    
    foreach ($fields as $field) {
        if (!isset($student[$field]) || $student[$field] === null || $student[$field] === '') {
            return false;
        }
    }
    return true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🐛 Debug Évaluations</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: #252526;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007acc;
        }
        .header h1 {
            color: #4ec9b0;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .card {
            background: #252526;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #3e3e42;
        }
        .card h2 {
            color: #4ec9b0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        select {
            background: #3c3c3c;
            color: #d4d4d4;
            border: 1px solid #007acc;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #3e3e42;
        }
        th {
            background: #2d2d30;
            color: #4ec9b0;
            font-weight: bold;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-green {
            background: #2d572c;
            color: #73c991;
        }
        .badge-red {
            background: #5a1d1d;
            color: #f48771;
        }
        .badge-gray {
            background: #3e3e42;
            color: #858585;
        }
        .value-cell {
            font-weight: bold;
        }
        .value-null {
            color: #858585;
            font-style: italic;
        }
        .value-a { color: #73c991; }
        .value-b { color: #4ec9b0; }
        .value-c { color: #ce9178; }
        .value-d { color: #f48771; }
        .debug-info {
            background: #1e1e1e;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 11px;
            border: 1px solid #007acc;
        }
        .debug-line {
            margin: 3px 0;
        }
        .key {
            color: #9cdcfe;
        }
        .string {
            color: #ce9178;
        }
        .number {
            color: #b5cea8;
        }
        .boolean {
            color: #569cd6;
        }
        .count {
            display: inline-block;
            background: #007acc;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 10px;
        }
        a {
            color: #4fc3f7;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐛 DEBUG - Évaluations Database</h1>
            <p>Voir exactement ce qui est dans la base de données</p>
        </div>

        <div class="card">
            <h2>📚 Sélectionner une classe</h2>
            <select onchange="window.location.href='?class_id='+this.value">
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="count"><?php echo count($students); ?> élève(s)</span>
        </div>

        <?php if (empty($students)): ?>
            <div class="card">
                <h2 style="color: #f48771;">❌ AUCUN ÉLÈVE TROUVÉ</h2>
                <p>Il n'y a aucun élève dans cette classe.</p>
                <p style="margin-top: 10px;">
                    <a href="students.php">→ Aller ajouter des élèves</a>
                </p>
            </div>
        <?php else: ?>

            <div class="card">
                <h2>🔍 Données Brutes (Raw Data)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th colspan="3">Oral</th>
                            <th colspan="3">Reading</th>
                            <th colspan="3">Comp</th>
                            <th colspan="4">Prod</th>
                            <th>Date Eval</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th></th>
                            <th>1</th>
                            <th>2</th>
                            <th>3</th>
                            <th>1</th>
                            <th>2</th>
                            <th>3</th>
                            <th>1</th>
                            <th>2</th>
                            <th>3</th>
                            <th>1</th>
                            <th>2</th>
                            <th>3</th>
                            <th>4</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                
                                <?php
                                $fields = ['oral_1', 'oral_2', 'oral_3', 'reading_1', 'reading_2', 'reading_3',
                                           'comp_1', 'comp_2', 'comp_3', 'prod_1', 'prod_2', 'prod_3', 'prod_4'];
                                foreach ($fields as $field) {
                                    $value = $student[$field];
                                    if ($value === null || $value === '') {
                                        echo '<td class="value-null">NULL</td>';
                                    } else {
                                        $class = 'value-' . strtolower($value);
                                        echo "<td class='value-cell $class'>" . htmlspecialchars($value) . "</td>";
                                    }
                                }
                                ?>
                                
                                <td><?php echo $student['evaluated_date'] ? date('d/m/Y H:i', strtotime($student['evaluated_date'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>✅ Status par Catégorie</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>🗣️ Oral</th>
                            <th>📖 Reading</th>
                            <th>📝 Comp</th>
                            <th>✍️ Prod</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                
                                <?php
                                $categories = ['oral', 'reading', 'comp', 'prod'];
                                foreach ($categories as $cat) {
                                    $complete = isCategoryComplete($student, $cat);
                                    if ($complete) {
                                        echo '<td><span class="status-badge badge-green">✓ COMPLET</span></td>';
                                    } else {
                                        echo '<td><span class="status-badge badge-red">✗ INCOMPLET</span></td>';
                                    }
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>🔬 Debug Info - Premier Élève</h2>
                <?php 
                $first = $students[0];
                ?>
                <div class="debug-info">
                    <div class="debug-line">
                        <span class="key">student_id:</span> 
                        <span class="number"><?php echo $first['id']; ?></span>
                    </div>
                    <div class="debug-line">
                        <span class="key">name:</span> 
                        <span class="string">"<?php echo htmlspecialchars($first['name']); ?>"</span>
                    </div>
                    
                    <div class="debug-line" style="margin-top: 10px;">
                        <span class="key">oral_1:</span> 
                        <?php if ($first['oral_1'] === null): ?>
                            <span class="value-null">null</span>
                        <?php else: ?>
                            <span class="string">"<?php echo $first['oral_1']; ?>"</span>
                        <?php endif; ?>
                        
                        <span style="margin-left: 20px;">
                            isset: <span class="boolean"><?php echo isset($first['oral_1']) ? 'true' : 'false'; ?></span>
                        </span>
                        
                        <span style="margin-left: 20px;">
                            empty: <span class="boolean"><?php echo empty($first['oral_1']) ? 'true' : 'false'; ?></span>
                        </span>
                    </div>
                    
                    <div class="debug-line">
                        <span class="key">oral_complete:</span> 
                        <span class="boolean">
                            <?php echo isCategoryComplete($first, 'oral') ? 'true' : 'false'; ?>
                        </span>
                    </div>
                    
                    <div class="debug-line" style="margin-top: 10px;">
                        <span class="key">evaluated_date:</span> 
                        <?php if ($first['evaluated_date']): ?>
                            <span class="string">"<?php echo $first['evaluated_date']; ?>"</span>
                        <?php else: ?>
                            <span class="value-null">null</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>📊 SQL Query Used</h2>
                <div class="debug-info">
                    <pre style="color: #d4d4d4; overflow-x: auto;">
SELECT 
    s.id, s.name, s.pic_path,
    e.oral_1, e.oral_2, e.oral_3,
    e.reading_1, e.reading_2, e.reading_3,
    e.comp_1, e.comp_2, e.comp_3,
    e.prod_1, e.prod_2, e.prod_3, e.prod_4,
    e.evaluated_date
FROM students s 
LEFT JOIN evaluations e 
    ON s.id = e.student_id 
    AND e.school_year = '2025/2026'
WHERE s.class_id = <?php echo $selected_class; ?>

ORDER BY s.name</pre>
                </div>
            </div>

            <div class="card">
                <h2>🔗 Quick Links</h2>
                <p>
                    <a href="evaluate_students_v4.php?step=category&class_id=<?php echo $selected_class; ?>">
                        → Évaluer ces élèves
                    </a>
                </p>
                <p style="margin-top: 10px;">
                    <a href="students.php">
                        → Gérer les élèves
                    </a>
                </p>
                <p style="margin-top: 10px;">
                    <a href="evaluation_dashboard_v2.php?class_id=<?php echo $selected_class; ?>">
                        → Voir le dashboard
                    </a>
                </p>
            </div>

        <?php endif; ?>
    </div>

    <?php $mysqli->close(); ?>
</body>
</html>
