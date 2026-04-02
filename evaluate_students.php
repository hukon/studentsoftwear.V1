<?php
// evaluate_students.php - FIXED VERSION
require_once __DIR__ . '/config.php';
requireLogin();

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    die("Connection error.");
}
$mysqli->set_charset('utf8mb4');

// Create table if not exists
$table_check = $mysqli->query("SHOW TABLES LIKE 'evaluations'");
if ($table_check->num_rows == 0) {
    $create_table = "CREATE TABLE `evaluations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `school_year` varchar(20) DEFAULT '2025/2026',
        `oral_1` varchar(1) DEFAULT NULL, `oral_2` varchar(1) DEFAULT NULL, `oral_3` varchar(1) DEFAULT NULL,
        `reading_1` varchar(1) DEFAULT NULL, `reading_2` varchar(1) DEFAULT NULL, `reading_3` varchar(1) DEFAULT NULL,
        `comp_1` varchar(1) DEFAULT NULL, `comp_2` varchar(1) DEFAULT NULL, `comp_3` varchar(1) DEFAULT NULL,
        `prod_1` varchar(1) DEFAULT NULL, `prod_2` varchar(1) DEFAULT NULL, `prod_3` varchar(1) DEFAULT NULL, `prod_4` varchar(1) DEFAULT NULL,
        `evaluated_date` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_student_year` (`student_id`, `school_year`),
        KEY `student_id` (`student_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $mysqli->query($create_table);
}

// Handle save
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_evaluation'])) {
    $student_id = intval($_POST['student_id']);
    $category = $_POST['category'];
    $class_id = intval($_POST['class_id']);
    $school_year = '2025/2026';
    
    // Validate student_id
    if ($student_id <= 0) {
        $error = "Erreur: ID étudiant invalide";
    } else {
        // Validate that all required fields are filled
        $all_filled = true;
        if ($category === 'oral') {
            $all_filled = !empty($_POST['oral_1']) && !empty($_POST['oral_2']) && !empty($_POST['oral_3']);
        } elseif ($category === 'reading') {
            $all_filled = !empty($_POST['reading_1']) && !empty($_POST['reading_2']) && !empty($_POST['reading_3']);
        } elseif ($category === 'comp') {
            $all_filled = !empty($_POST['comp_1']) && !empty($_POST['comp_2']) && !empty($_POST['comp_3']);
        } elseif ($category === 'prod') {
            $all_filled = !empty($_POST['prod_1']) && !empty($_POST['prod_2']) && !empty($_POST['prod_3']) && !empty($_POST['prod_4']);
        }
        
        if (!$all_filled) {
            $error = "Veuillez remplir tous les critères";
        } else {
            // Get current evaluation data
            $check = $mysqli->prepare("SELECT * FROM evaluations WHERE student_id = ? AND school_year = ?");
            $check->bind_param("is", $student_id, $school_year);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();
            
            $success_save = false;
            
            if ($existing) {
                // Update only the relevant fields
                if ($category === 'oral') {
                    $stmt = $mysqli->prepare("UPDATE evaluations SET oral_1=?, oral_2=?, oral_3=?, evaluated_date=NOW() WHERE student_id=? AND school_year=?");
                    $stmt->bind_param("sssis", $_POST['oral_1'], $_POST['oral_2'], $_POST['oral_3'], $student_id, $school_year);
                } elseif ($category === 'reading') {
                    $stmt = $mysqli->prepare("UPDATE evaluations SET reading_1=?, reading_2=?, reading_3=?, evaluated_date=NOW() WHERE student_id=? AND school_year=?");
                    $stmt->bind_param("sssis", $_POST['reading_1'], $_POST['reading_2'], $_POST['reading_3'], $student_id, $school_year);
                } elseif ($category === 'comp') {
                    $stmt = $mysqli->prepare("UPDATE evaluations SET comp_1=?, comp_2=?, comp_3=?, evaluated_date=NOW() WHERE student_id=? AND school_year=?");
                    $stmt->bind_param("sssis", $_POST['comp_1'], $_POST['comp_2'], $_POST['comp_3'], $student_id, $school_year);
                } elseif ($category === 'prod') {
                    $stmt = $mysqli->prepare("UPDATE evaluations SET prod_1=?, prod_2=?, prod_3=?, prod_4=?, evaluated_date=NOW() WHERE student_id=? AND school_year=?");
                    $stmt->bind_param("ssssis", $_POST['prod_1'], $_POST['prod_2'], $_POST['prod_3'], $_POST['prod_4'], $student_id, $school_year);
                }
                
                if ($stmt->execute()) {
                    $success_save = true;
                } else {
                    $error = "Erreur lors de la mise à jour: " . $stmt->error;
                }
                $stmt->close();
                
            } else {
                // Insert new record with only relevant fields
                if ($category === 'oral') {
                    $stmt = $mysqli->prepare("INSERT INTO evaluations (student_id, school_year, oral_1, oral_2, oral_3) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issss", $student_id, $school_year, $_POST['oral_1'], $_POST['oral_2'], $_POST['oral_3']);
                } elseif ($category === 'reading') {
                    $stmt = $mysqli->prepare("INSERT INTO evaluations (student_id, school_year, reading_1, reading_2, reading_3) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issss", $student_id, $school_year, $_POST['reading_1'], $_POST['reading_2'], $_POST['reading_3']);
                } elseif ($category === 'comp') {
                    $stmt = $mysqli->prepare("INSERT INTO evaluations (student_id, school_year, comp_1, comp_2, comp_3) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issss", $student_id, $school_year, $_POST['comp_1'], $_POST['comp_2'], $_POST['comp_3']);
                } elseif ($category === 'prod') {
                    $stmt = $mysqli->prepare("INSERT INTO evaluations (student_id, school_year, prod_1, prod_2, prod_3, prod_4) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssss", $student_id, $school_year, $_POST['prod_1'], $_POST['prod_2'], $_POST['prod_3'], $_POST['prod_4']);
                }
                
                if ($stmt->execute()) {
                    $success_save = true;
                } else {
                    $error = "Erreur lors de l'insertion: " . $stmt->error;
                }
                $stmt->close();
            }
            
            if ($success_save) {
                // Redirect back to student list with success message
                header("Location: ?step=students&class_id=$class_id&category=$category&success=1");
                exit;
            }
        }
    }
}

// Get classes
$classes = [];
$res = $mysqli->query("SELECT id, name FROM classes ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $classes[] = $row;
}

// Evaluation categories
$categories = [
    'oral' => [
        'title' => 'Compréhension et Communication Orales',
        'icon' => '🗣️',
        'color' => '#3b82f6',
        'fields' => [
            'oral_1' => 'Identifier le thème de la situation de communication',
            'oral_2' => 'Identifier les unités de sens',
            'oral_3' => "S'exprimer en fonction de la situation de communication"
        ]
    ],
    'reading' => [
        'title' => 'Lecture',
        'icon' => '📖',
        'color' => '#10b981',
        'fields' => [
            'reading_1' => 'Activer la correspondance graphie/phonie',
            'reading_2' => 'Réaliser une lecture fluide',
            'reading_3' => "Respecter l'intonation (règle de la prosodie)"
        ]
    ],
    'comp' => [
        'title' => "Compréhension de l'Écrit",
        'icon' => '📝',
        'color' => '#f59e0b',
        'fields' => [
            'comp_1' => 'Identifier le thème général du texte',
            'comp_2' => 'Identifier le champ lexical relatif au thème',
            'comp_3' => 'Repérer des informations'
        ]
    ],
    'prod' => [
        'title' => 'Production Écrite',
        'icon' => '✏️',
        'color' => '#8b5cf6',
        'fields' => [
            'prod_1' => 'Pertinence du texte produit',
            'prod_2' => 'Cohérence du texte produit',
            'prod_3' => 'Correction de la langue',
            'prod_4' => "Lisibilité de l'écrit"
        ]
    ]
];

$step = $_GET['step'] ?? 'class';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation - 1AM</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #6b7280;
        }
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .category-card {
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .category-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .category-card .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .category-card .title {
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
        }
        
        .student-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .student-item:hover {
            border-color: #667eea;
            background: #f9fafb;
        }
        .student-item.evaluated {
            border-color: #6ee7b7;
            background: #ecfdf5;
        }
        
        .student-photo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }
        
        .student-info {
            flex: 1;
        }
        .student-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }
        .student-status {
            font-size: 12px;
            color: #6b7280;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-complete {
            background: #d1fae5;
            color: #065f46;
        }
        .status-incomplete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .eval-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .criterion {
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .criterion-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .radio-group {
            display: flex;
            gap: 10px;
        }
        
        .radio-option {
            flex: 1;
        }
        .radio-option input[type="radio"] {
            display: none;
        }
        .radio-option label {
            display: block;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.2s;
            background: white;
        }
        .radio-option input:checked + label {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .level-a label { color: #10b981; border-color: #10b981; }
        .level-a input:checked + label { background: #d1fae5; border-width: 3px; }
        
        .level-b label { color: #3b82f6; border-color: #3b82f6; }
        .level-b input:checked + label { background: #dbeafe; border-width: 3px; }
        
        .level-c label { color: #f59e0b; border-color: #f59e0b; }
        .level-c input:checked + label { background: #fef3c7; border-width: 3px; }
        
        .level-d label { color: #ef4444; border-color: #ef4444; }
        .level-d input:checked + label { background: #fee2e2; border-width: 3px; }
        
        .legend {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-badge {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }
        
        .debug-info {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            font-family: monospace;
        }
        
        @media (max-width: 600px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 15px;
            }
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($step === 'class'): ?>
            <!-- STEP 1: Select Class -->
            <div class="card">
                <div class="header">
                    <h1>📋 Évaluation des Élèves</h1>
                    <p>Système d'évaluation par étapes</p>
                </div>
                
                <div class="breadcrumb">
                    <span style="font-weight: 600;">Étape 1 sur 3</span> · Sélection de la classe
                </div>
                
                <form method="GET">
                    <input type="hidden" name="step" value="category">
                    <label for="class_id">Choisir une classe</label>
                    <select name="class_id" id="class_id" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Suivant →</button>
                </form>
                
                <a href="evaluation_dashboard.php" class="btn btn-secondary">← Retour au tableau de bord</a>
            </div>

        <?php elseif ($step === 'category'): ?>
            <!-- STEP 2: Select Category -->
            <?php
            $class_id = intval($_GET['class_id']);
            $class_name = '';
            $stmt = $mysqli->prepare("SELECT name FROM classes WHERE id = ?");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $class_name = $row['name'];
            }
            $stmt->close();
            ?>
            
            <div class="card">
                <div class="header">
                    <h1>📚 <?php echo htmlspecialchars($class_name); ?></h1>
                    <p>Choisir une compétence à évaluer</p>
                </div>
                
                <div class="breadcrumb">
                    <a href="?step=class">Classe</a> › <span style="font-weight: 600;">Étape 2 sur 3</span> · Compétence
                </div>
                
                <div class="category-grid">
                    <?php foreach ($categories as $key => $category): ?>
                        <a href="?step=students&class_id=<?php echo $class_id; ?>&category=<?php echo $key; ?>" class="category-card">
                            <div class="icon"><?php echo $category['icon']; ?></div>
                            <div class="title"><?php echo $category['title']; ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <a href="?step=class" class="btn btn-secondary">← Retour</a>
            </div>

        <?php elseif ($step === 'students'): ?>
            <!-- STEP 3: Select Student -->
            <?php
            $class_id = intval($_GET['class_id']);
            $category = $_GET['category'] ?? 'oral';
            
            // Fetch students with their evaluation data
            $stmt = $mysqli->prepare("SELECT s.id as student_id, s.name, s.pic_path, 
    e.oral_1, e.oral_2, e.oral_3,
    e.reading_1, e.reading_2, e.reading_3,
    e.comp_1, e.comp_2, e.comp_3,
    e.prod_1, e.prod_2, e.prod_3, e.prod_4
    FROM students s 
    LEFT JOIN evaluations e ON s.id = e.student_id AND e.school_year = '2025/2026'
    WHERE s.class_id = ? ORDER BY s.name");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            ?>
            
            <div class="card">
                <div class="header">
                    <h1><?php echo $categories[$category]['icon']; ?> <?php echo $categories[$category]['title']; ?></h1>
                    <p>Sélectionner un élève à évaluer</p>
                </div>
                
                <div class="breadcrumb">
                    <a href="?step=class">Classe</a> › 
                    <a href="?step=category&class_id=<?php echo $class_id; ?>">Compétence</a> › 
                    <span style="font-weight: 600;">Étape 3 sur 3</span> · Élève
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">✓ Évaluation enregistrée avec succès</div>
                <?php endif; ?>
                
                <div class="student-list">
                    <?php foreach ($students as $student):
                        // Check if this category is evaluated
                        $fields = array_keys($categories[$category]['fields']);
                        $is_complete = true;
                        foreach ($fields as $field) {
                            if (!isset($student[$field]) || $student[$field] === null || $student[$field] === '') {
                                $is_complete = false;
                                break;
                            }
                        }
                    ?>
<a href="?step=evaluate&class_id=<?php echo $class_id; ?>&category=<?php echo $category; ?>&student_id=<?php echo $student['student_id']; ?>"                           class="student-item <?php echo $is_complete ? 'evaluated' : ''; ?>">
                            <?php if ($student['pic_path']): ?>
                                <img src="<?php echo htmlspecialchars($student['pic_path']); ?>" class="student-photo" alt="">
                            <?php else: ?>
                                <div class="student-photo" style="background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 20px;">👤</div>
                            <?php endif; ?>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                <div class="student-status">
                                    <?php if ($is_complete): ?>
                                        <span class="status-badge status-complete">✓ Évalué</span>
                                    <?php else: ?>
                                        <span class="status-badge status-incomplete">À évaluer</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span style="color: #9ca3af;">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <a href="?step=category&class_id=<?php echo $class_id; ?>" class="btn btn-secondary">← Retour</a>
            </div>

        <?php elseif ($step === 'evaluate'): ?>
            <!-- STEP 4: Evaluate Student -->
            <?php
            // CRITICAL FIX: Get values from POST (after form submission) or GET (initial page load)
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : intval($_GET['class_id'] ?? 0);
            $category = isset($_POST['category']) ? $_POST['category'] : ($_GET['category'] ?? 'oral');
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : intval($_GET['student_id'] ?? 0);
            
            // Validate student_id
            if ($student_id <= 0) {
                echo '<div class="card"><div class="alert alert-error">Erreur: ID étudiant invalide. <a href="?step=class">Retour</a></div></div>';
                exit;
            }
            
            $stmt = $mysqli->prepare("SELECT s.*, e.* FROM students s 
                LEFT JOIN evaluations e ON s.id = e.student_id AND e.school_year = '2025/2026'
                WHERE s.id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$student) {
                echo '<div class="card"><div class="alert alert-error">Étudiant non trouvé. <a href="?step=class">Retour</a></div></div>';
                exit;
            }
            ?>
            
            <div class="card">
                <div class="header">
                    <h1><?php echo $categories[$category]['icon']; ?> <?php echo htmlspecialchars($student['name']); ?></h1>
                    <p><?php echo $categories[$category]['title']; ?></p>
                </div>
                
                <div class="breadcrumb">
                    <a href="?step=class">Classe</a> › 
                    <a href="?step=category&class_id=<?php echo $class_id; ?>">Compétence</a> › 
                    <a href="?step=students&class_id=<?php echo $class_id; ?>&category=<?php echo $category; ?>">Élève</a> › 
                    <span style="font-weight: 600;">Évaluation</span>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #d1fae5; color: #10b981; border: 2px solid #10b981;">A</div>
                        <span>Très satisfaisant</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #dbeafe; color: #3b82f6; border: 2px solid #3b82f6;">B</div>
                        <span>Satisfaisant</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #fef3c7; color: #f59e0b; border: 2px solid #f59e0b;">C</div>
                        <span>Peu satisfaisant</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #fee2e2; color: #ef4444; border: 2px solid #ef4444;">D</div>
                        <span>Non satisfaisant</span>
                    </div>
                </div>
                
                <form method="POST" class="eval-form">
                    <input type="hidden" name="save_evaluation" value="1">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                    
                    <?php foreach ($categories[$category]['fields'] as $field => $label): ?>
                        <div class="criterion">
                            <div class="criterion-label"><?php echo $label; ?></div>
                            <div class="radio-group">
                                <?php foreach (['A', 'B', 'C', 'D'] as $level): ?>
                                    <div class="radio-option level-<?php echo strtolower($level); ?>">
                                        <input type="radio" 
                                               name="<?php echo $field; ?>" 
                                               value="<?php echo $level; ?>"
                                               id="<?php echo $field; ?>_<?php echo $level; ?>"
                                               <?php echo (isset($student[$field]) && $student[$field] == $level) ? 'checked' : ''; ?>
                                               required>
                                        <label for="<?php echo $field; ?>_<?php echo $level; ?>"><?php echo $level; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn btn-primary">💾 Enregistrer l'évaluation</button>
                    <a href="?step=students&class_id=<?php echo $class_id; ?>&category=<?php echo $category; ?>" class="btn btn-secondary">← Retour à la liste</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php $mysqli->close(); ?>
</body>
</html>