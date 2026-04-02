<?php
// test_save.php - Test if saving actually works
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

$log = [];
$success = false;
$error = '';

// Get first student for testing
$result = $mysqli->query("SELECT id, name FROM students ORDER BY id LIMIT 1");
$test_student = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_save'])) {
    $student_id = intval($_POST['student_id']);
    $school_year = '2025/2026';
    
    $log[] = "🔹 POST data received";
    $log[] = "Student ID: " . $student_id;
    $log[] = "oral_1: " . ($_POST['oral_1'] ?? 'NOT SET');
    $log[] = "oral_2: " . ($_POST['oral_2'] ?? 'NOT SET');
    $log[] = "oral_3: " . ($_POST['oral_3'] ?? 'NOT SET');
    
    // Check if evaluation exists
    $check = $mysqli->prepare("SELECT id FROM evaluations WHERE student_id = ? AND school_year = ?");
    $check->bind_param("is", $student_id, $school_year);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($existing) {
        $log[] = "🔹 Found existing evaluation (ID: " . $existing['id'] . ")";
        $log[] = "🔹 Attempting UPDATE...";
        
        $stmt = $mysqli->prepare("UPDATE evaluations SET oral_1=?, oral_2=?, oral_3=?, evaluated_date=NOW() WHERE student_id=? AND school_year=?");
        $stmt->bind_param("sssis", $_POST['oral_1'], $_POST['oral_2'], $_POST['oral_3'], $student_id, $school_year);
        
        if ($stmt->execute()) {
            $log[] = "✅ UPDATE executed successfully";
            $log[] = "Affected rows: " . $stmt->affected_rows;
            $success = true;
        } else {
            $log[] = "❌ UPDATE failed: " . $stmt->error;
            $error = $stmt->error;
        }
        $stmt->close();
        
    } else {
        $log[] = "🔹 No existing evaluation found";
        $log[] = "🔹 Attempting INSERT...";
        
        $stmt = $mysqli->prepare("INSERT INTO evaluations (student_id, school_year, oral_1, oral_2, oral_3) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $student_id, $school_year, $_POST['oral_1'], $_POST['oral_2'], $_POST['oral_3']);
        
        if ($stmt->execute()) {
            $log[] = "✅ INSERT executed successfully";
            $log[] = "New evaluation ID: " . $mysqli->insert_id;
            $success = true;
        } else {
            $log[] = "❌ INSERT failed: " . $stmt->error;
            $error = $stmt->error;
        }
        $stmt->close();
    }
    
    // Verify the data was saved
    $log[] = "";
    $log[] = "🔍 VERIFICATION - Reading back from database:";
    $verify = $mysqli->prepare("SELECT oral_1, oral_2, oral_3, evaluated_date FROM evaluations WHERE student_id = ? AND school_year = ?");
    $verify->bind_param("is", $student_id, $school_year);
    $verify->execute();
    $result = $verify->get_result()->fetch_assoc();
    $verify->close();
    
    if ($result) {
        $log[] = "oral_1: " . ($result['oral_1'] ?? 'NULL');
        $log[] = "oral_2: " . ($result['oral_2'] ?? 'NULL');
        $log[] = "oral_3: " . ($result['oral_3'] ?? 'NULL');
        $log[] = "evaluated_date: " . ($result['evaluated_date'] ?? 'NULL');
    } else {
        $log[] = "❌ No data found in database!";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Save Function</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: #252526;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #3e3e42;
        }
        h1, h2 {
            color: #4ec9b0;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            color: #9cdcfe;
            margin-bottom: 5px;
        }
        input[type="radio"] {
            margin-right: 10px;
        }
        .radio-group {
            display: flex;
            gap: 15px;
            padding: 10px;
            background: #1e1e1e;
            border-radius: 4px;
        }
        .btn {
            background: #007acc;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
        .btn:hover {
            background: #005a9e;
        }
        .log {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.8;
        }
        .log-line {
            margin: 5px 0;
        }
        .success {
            color: #73c991;
        }
        .error {
            color: #f48771;
        }
        .info {
            color: #4fc3f7;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #2d572c;
            color: #73c991;
            border: 1px solid #73c991;
        }
        .alert-error {
            background: #5a1d1d;
            color: #f48771;
            border: 1px solid #f48771;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🧪 Test Save Function</h1>
            <p style="color: #858585; margin-bottom: 20px;">
                Test if evaluations are actually being saved to the database
            </p>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ SAVE SUCCESSFUL! Check the log below for details.
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error">
                    ❌ SAVE FAILED: <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($test_student): ?>
                <form method="POST">
                    <input type="hidden" name="test_save" value="1">
                    <input type="hidden" name="student_id" value="5">
                    
                    <div style="background: #2d2d30; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                        <strong style="color: #4ec9b0;">Test Student:</strong>
                        <span style="color: #ce9178;"><?php echo htmlspecialchars($test_student['name']); ?></span>
                        <span style="color: #858585; margin-left: 10px;">(ID: <?php echo $test_student['id']; ?>)</span>
                    </div>

                    <div class="form-group">
                        <label>Oral Criterion 1:</label>
                        <div class="radio-group">
                            <label><input type="radio" name="oral_1" value="A" required> A</label>
                            <label><input type="radio" name="oral_1" value="B"> B</label>
                            <label><input type="radio" name="oral_1" value="C"> C</label>
                            <label><input type="radio" name="oral_1" value="D"> D</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Oral Criterion 2:</label>
                        <div class="radio-group">
                            <label><input type="radio" name="oral_2" value="A" required> A</label>
                            <label><input type="radio" name="oral_2" value="B"> B</label>
                            <label><input type="radio" name="oral_2" value="C"> C</label>
                            <label><input type="radio" name="oral_2" value="D"> D</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Oral Criterion 3:</label>
                        <div class="radio-group">
                            <label><input type="radio" name="oral_3" value="A" required> A</label>
                            <label><input type="radio" name="oral_3" value="B"> B</label>
                            <label><input type="radio" name="oral_3" value="C"> C</label>
                            <label><input type="radio" name="oral_3" value="D"> D</label>
                        </div>
                    </div>

                    <button type="submit" class="btn">🧪 Test Save</button>
                </form>
            <?php else: ?>
                <div class="alert alert-error">
                    No students found in database!
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($log)): ?>
        <div class="card">
            <h2>📋 Execution Log</h2>
            <div class="log">
                <?php foreach ($log as $line): ?>
                    <div class="log-line <?php 
                        if (strpos($line, '✅') !== false) echo 'success';
                        elseif (strpos($line, '❌') !== false) echo 'error';
                        elseif (strpos($line, '🔹') !== false) echo 'info';
                    ?>">
                        <?php echo htmlspecialchars($line); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>🔗 Next Steps</h2>
            <p style="margin-bottom: 15px; color: #858585;">
                After testing, check these pages:
            </p>
            <a href="debug_evaluations.php" style="color: #4fc3f7; text-decoration: none; display: block; margin: 10px 0;">
                → Debug Evaluations (see if data appears)
            </a>
            <a href="evaluate_students_v4.php" style="color: #4fc3f7; text-decoration: none; display: block; margin: 10px 0;">
                → Try Real Evaluation Page
            </a>
        </div>
    </div>
</body>
</html>
<?php $mysqli->close(); ?>
