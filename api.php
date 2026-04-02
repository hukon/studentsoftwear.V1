<?php
// api.php - Enhanced API for Student Organizer with New Category System
// Requirements: PHP 8+, MySQL/MariaDB, PDO extension enabled
require_once __DIR__ . '/config.php';

// Disable error display in production (errors logged server-side instead)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

try {
  $pdo = getDB();
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB connection failed']);
  exit;
}

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function json_ok($data){ echo json_encode($data); exit; }
function json_err($msg, $code=400){ http_response_code($code); echo json_encode(['error'=>$msg]); exit; }

switch ($action) {
  case 'classes':
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name ASC");
    json_ok($stmt->fetchAll());
    break;

  case 'add_class':
    if (!isset($_POST['name']) || trim($_POST['name']) === '') {
        json_err("Class name is required");
    }
    $name = trim($_POST['name']);



    $stmt = $pdo->prepare("INSERT INTO classes (name) VALUES (?)");
    if (!$stmt->execute([$name])) {
        json_err("Insert failed: " . implode(" | ", $stmt->errorInfo()));
    }

    json_ok(["id" => $pdo->lastInsertId(), "name" => $name]);
    break;

  case 'students':
    if (!isset($_GET['class_id'])) {
        json_err("Missing class_id");
    }
    $class_id = intval($_GET['class_id']);



    // ✅ Fetch with new category columns
    $stmt = $pdo->prepare("
        SELECT id, name, dob, bio, pic_path, 
               comprehension_orale, ecriture, vocabulaire, 
               grammaire, conjugaison, production_ecrite,
               category1, difficulties
        FROM students 
        WHERE class_id = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();

    // ✅ Fix photo URLs - handle both relative and absolute paths
    foreach ($students as &$row) {
        if (!empty($row['pic_path'])) {
            // If already a full URL, keep it
            if (strpos($row['pic_path'], 'http') === 0) {
                // Already absolute URL, keep as is
                $row['pic_path'] = $row['pic_path'];
            } else {
                // Build absolute URL from relative path
                $filename = basename($row['pic_path']);
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $row['pic_path'] = $protocol . "://" . $host . "/uploads/" . $filename;
            }
        }
        // Convert boolean fields to integers for JavaScript
        $row['comprehension_orale'] = (int)$row['comprehension_orale'];
        $row['ecriture'] = (int)$row['ecriture'];
        $row['vocabulaire'] = (int)$row['vocabulaire'];
        $row['grammaire'] = (int)$row['grammaire'];
        $row['conjugaison'] = (int)$row['conjugaison'];
        $row['production_ecrite'] = (int)$row['production_ecrite'];
    }

    json_ok($students);
    break;

  case 'add_student':
    $required = ['name', 'class_id'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            json_err("Field '$field' is required");
        }
    }

    $name     = trim($_POST['name']);
    $dob      = $_POST['dob'] ?? null;
    $bio      = trim($_POST['bio'] ?? '');
    $class_id = intval($_POST['class_id']);
    
    // ✅ New category system (boolean fields)
    $comprehension_orale = intval($_POST['comprehension_orale'] ?? 0);
    $ecriture            = intval($_POST['ecriture'] ?? 0);
    $vocabulaire         = intval($_POST['vocabulaire'] ?? 0);
    $grammaire           = intval($_POST['grammaire'] ?? 0);
    $conjugaison         = intval($_POST['conjugaison'] ?? 0);
    $production_ecrite   = intval($_POST['production_ecrite'] ?? 0);

    // Legacy fields (kept for backwards compatibility)
    $category1    = $_POST['category1'] ?? null;
    $difficulties = $_POST['difficulties'] ?? null;

    // Handle photo upload
    $pic_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $target = "uploads/" . uniqid("student_", true) . "." . $ext;
        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $pic_path = $target;
        } else {
            json_err("Photo upload failed");
        }
    }



    try {
        $stmt = $pdo->prepare("
            INSERT INTO students 
            (class_id, name, dob, bio, pic_path, 
             comprehension_orale, ecriture, vocabulaire, 
             grammaire, conjugaison, production_ecrite,
             category1, difficulties) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        
        if (!$stmt->execute([
            $class_id, $name, $dob ?: null, $bio, $pic_path,
            $comprehension_orale, $ecriture, $vocabulaire,
            $grammaire, $conjugaison, $production_ecrite,
            $category1, $difficulties
        ])) {
            $err = $stmt->errorInfo();
            json_err("Insert failed: " . $err[2]);
        }

        json_ok([
            "id" => $pdo->lastInsertId(),
            "class_id" => $class_id,
            "name" => $name,
            "dob" => $dob,
            "bio" => $bio,
            "pic_path" => $pic_path,
            "comprehension_orale" => $comprehension_orale,
            "ecriture" => $ecriture,
            "vocabulaire" => $vocabulaire,
            "grammaire" => $grammaire,
            "conjugaison" => $conjugaison,
            "production_ecrite" => $production_ecrite
        ]);
    } catch (Exception $e) {
        json_err("Database error: " . $e->getMessage());
    }
    break;

  case 'update_student':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_err("Missing student id");

    $name = trim($_POST['name'] ?? '');
    $dob  = $_POST['dob'] ?? null;
    $bio  = trim($_POST['bio'] ?? '');
    
    // ✅ New category system
    $comprehension_orale = intval($_POST['comprehension_orale'] ?? 0);
    $ecriture            = intval($_POST['ecriture'] ?? 0);
    $vocabulaire         = intval($_POST['vocabulaire'] ?? 0);
    $grammaire           = intval($_POST['grammaire'] ?? 0);
    $conjugaison         = intval($_POST['conjugaison'] ?? 0);
    $production_ecrite   = intval($_POST['production_ecrite'] ?? 0);

    // Legacy fields
    $category1    = $_POST['category1'] ?? null;
    $difficulties = $_POST['difficulties'] ?? '[]';

    if ($name === '') json_err("Name required");

    // Handle photo upload
    $pic_path = null;
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Delete old photo first
        $oldStmt = $pdo->prepare("SELECT pic_path FROM students WHERE id=?");
        $oldStmt->execute([$id]);
        $oldPic = $oldStmt->fetchColumn();
        if ($oldPic && file_exists($oldPic)) {
            @unlink($oldPic);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $target = "uploads/" . uniqid("student_", true) . "." . $ext;
        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $pic_path = $target;
        } else {
            json_err("Photo upload failed");
        }
    }

    try {
        if ($pic_path) {
            $stmt = $pdo->prepare("
                UPDATE students 
                SET name=?, dob=?, bio=?, pic_path=?,
                    comprehension_orale=?, ecriture=?, vocabulaire=?,
                    grammaire=?, conjugaison=?, production_ecrite=?,
                    category1=?, difficulties=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $dob ?: null, $bio, $pic_path,
                $comprehension_orale, $ecriture, $vocabulaire,
                $grammaire, $conjugaison, $production_ecrite,
                $category1, $difficulties,
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE students 
                SET name=?, dob=?, bio=?,
                    comprehension_orale=?, ecriture=?, vocabulaire=?,
                    grammaire=?, conjugaison=?, production_ecrite=?,
                    category1=?, difficulties=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $dob ?: null, $bio,
                $comprehension_orale, $ecriture, $vocabulaire,
                $grammaire, $conjugaison, $production_ecrite,
                $category1, $difficulties,
                $id
            ]);
        }

        json_ok(["success" => true]);
    } catch (Exception $e) {
        json_err("Database error: " . $e->getMessage());
    }
    break;

  case 'delete_student':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) json_err("Missing student id");

    try {
        // Delete photo file first
        $stmt = $pdo->prepare("SELECT pic_path FROM students WHERE id=?");
        $stmt->execute([$id]);
        $pic = $stmt->fetchColumn();
        if ($pic && file_exists($pic)) {
            @unlink($pic);
        }

        // Delete student record
        $stmt = $pdo->prepare("DELETE FROM students WHERE id=?");
        $stmt->execute([$id]);
        
        json_ok(["success" => true]);
    } catch (Exception $e) {
        json_err("Database error: " . $e->getMessage());
    }
    break;

  // ========== SEATING SYSTEM ==========
  case 'get_seating':
    $class_id = intval($_GET['class_id'] ?? 0);
    if ($class_id <= 0) json_err("Missing class_id");
    try {
        $stmt = $pdo->prepare("
            SELECT seat.row_num, seat.col_num, seat.seat_num, seat.student_id,
                   s.name, s.pic_path
            FROM seating AS seat
            JOIN students AS s ON seat.student_id = s.id
            WHERE seat.class_id = ?
        ");
        $stmt->execute([$class_id]);
        $rows = $stmt->fetchAll();
        // normalize pic URL
        foreach ($rows as &$r) {
            if (!empty($r['pic_path'])) {
                $r['pic_url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/uploads/' . basename($r['pic_path']);
            } else {
                $r['pic_url'] = null;
            }
        }
        json_ok($rows);
    } catch (Exception $e) {
        json_err("DB error: " . $e->getMessage());
    }
    break;

  case 'set_seating':
    $class_id   = intval($_POST['class_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $row = intval($_POST['row_num'] ?? 0);
    $col = intval($_POST['col_num'] ?? 0);
    $seat = intval($_POST['seat_num'] ?? 0);

    if (!$class_id || !$student_id || !$row || !$col || !$seat) json_err("Missing parameters");

    try {
        $pdo->beginTransaction();

        // Remove previous seat for this student in the class (if any)
        $del = $pdo->prepare("DELETE FROM seating WHERE class_id=? AND student_id=?");
        $del->execute([$class_id, $student_id]);

        // If seat is already occupied, remove that occupant (we'll overwrite)
        $del2 = $pdo->prepare("DELETE FROM seating WHERE class_id=? AND row_num=? AND col_num=? AND seat_num=?");
        $del2->execute([$class_id, $row, $col, $seat]);

        // Insert new assignment
        $ins = $pdo->prepare("INSERT INTO seating (class_id, student_id, row_num, col_num, seat_num) VALUES (?,?,?,?,?)");
        $ins->execute([$class_id, $student_id, $row, $col, $seat]);

        $pdo->commit();
        json_ok(["success" => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_err("DB error: " . $e->getMessage());
    }
    break;

  case 'remove_seating':
    $class_id = intval($_POST['class_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    if (!$class_id || !$student_id) json_err("Missing parameters");
    try {
        $stmt = $pdo->prepare("DELETE FROM seating WHERE class_id=? AND student_id=?");
        $stmt->execute([$class_id, $student_id]);
        json_ok(["success" => true]);
    } catch (Exception $e) {
        json_err("DB error: " . $e->getMessage());
    }
    break;

  case 'clear_seating':
    $class_id = intval($_POST['class_id'] ?? 0);
    if (!$class_id) json_err("Missing class_id");
    try {
        $stmt = $pdo->prepare("DELETE FROM seating WHERE class_id=?");
        $stmt->execute([$class_id]);
        json_ok(["success" => true]);
    } catch (Exception $e) {
        json_err("DB error: " . $e->getMessage());
    }
    break;

  // ========== CALENDAR SYSTEM ==========
  case 'calendar_events':
    $year = intval($_GET['year'] ?? date('Y'));
    $month = intval($_GET['month'] ?? date('n'));
    if ($month < 1 || $month > 12) json_err('Invalid month');

    // start and end dates for the month
    $start = sprintf('%04d-%02d-01', $year, $month);
    $end = date('Y-m-d', strtotime("$start +1 month -1 day"));

    try {
        $events = [];

        // 1) Student birthdays: recurring yearly
        $stmt = $pdo->prepare("SELECT id, name, dob, pic_path FROM students WHERE dob IS NOT NULL AND dob <> ''");
        $stmt->execute();
        $students = $stmt->fetchAll();

        foreach ($students as $s) {
            $dob = $s['dob'];
            if (!$dob) continue;
            $m = intval(date('n', strtotime($dob)));
            $d = intval(date('j', strtotime($dob)));

            // build event date in this year
            $eventDate = sprintf('%04d-%02d-%02d', $year, $m, $d);

            // only include if inside requested month range
            if ($eventDate >= $start && $eventDate <= $end) {
                $events[] = [
                    'type' => 'birthday',
                    'title' => $s['name'] . " — Anniversaire",
                    'date' => $eventDate,
                    'student_id' => intval($s['id']),
                    'pic' => $s['pic_path'] ? (strpos($s['pic_path'],'http')===0 ? $s['pic_path'] : ($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/uploads/'.basename($s['pic_path']))) : null
                ];
            }
        }

        // 2) Holidays table (one-time and recurring)
        $stmt = $pdo->query("SELECT * FROM holidays");
        $hols = $stmt->fetchAll();

        foreach ($hols as $h) {
            if ($h['recurring']) {
                $md = date('m-d', strtotime($h['date']));
                $eventDate = $year . '-' . $md;
            } else {
                $eventDate = $h['date'];
            }
            if ($eventDate >= $start && $eventDate <= $end) {
                $events[] = [
                    'type' => 'holiday',
                    'title' => $h['title'],
                    'date' => $eventDate,
                    'holiday_id' => intval($h['id']),
                    'notes' => $h['notes'] ?? null
                ];
            }
        }

        json_ok($events);
    } catch (Exception $e) {
        json_err('DB error: '.$e->getMessage());
    }
    break;

  case 'add_holiday':
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $rec = intval($_POST['recurring'] ?? 0);
    $notes = $_POST['notes'] ?? null;
    if ($title === '' || $date === '') json_err('title and date required');
    $stmt = $pdo->prepare("INSERT INTO holidays (title, date, recurring, notes) VALUES (?,?,?,?)");
    $stmt->execute([$title, $date, $rec, $notes]);
    json_ok(['id' => $pdo->lastInsertId()]);
    break;

  case 'del_holiday':
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json_err('id required');
    $stmt = $pdo->prepare("DELETE FROM holidays WHERE id=?");
    $stmt->execute([$id]);
    json_ok(['success'=>true]);
    break;

  default:
    json_err('Action inconnue', 404);
}
?>