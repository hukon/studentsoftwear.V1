<?php
// export_pdf.php
require('fpdf/fpdf.php');

// Convert UTF-8 strings to ISO-8859-1 for FPDF
function utf8_to_latin($text) {
    if ($text === null) return '';
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    if ($out !== false) return $out;
    return @utf8_decode($text);
}

require_once __DIR__ . '/config.php';
requireLogin();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données.");
}
$conn->set_charset("utf8mb4");

// Generate PDF when class_id is provided
if (isset($_GET['class_id']) && intval($_GET['class_id']) > 0) {
    $class_id = intval($_GET['class_id']);

    // get class name
    $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        die("Classe introuvable.");
    }
    $row = $res->fetch_assoc();
    $className = $row['name'];
    $stmt->close();

    // get students for that class, with optional difficulty filter
    if (!empty($_GET['difficulty_filter'])) {
        $difficultyFilter = '%' . $_GET['difficulty_filter'] . '%';
        $stmt = $conn->prepare("SELECT name, dob, category1, difficulties FROM students WHERE class_id = ? AND difficulties LIKE ? ORDER BY name");
        $stmt->bind_param("is", $class_id, $difficultyFilter);
    } else {
        $stmt = $conn->prepare("SELECT name, dob, category1, difficulties FROM students WHERE class_id = ? ORDER BY name");
        $stmt->bind_param("i", $class_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    class PDF extends FPDF {
        private $className = '';
        public function setClassName($n) { $this->className = $n; }
        function Header() {
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,utf8_to_latin("Liste des élèves - Classe: {$this->className}"),0,1,'C');
            $this->Ln(5);
            $this->SetFont('Arial','B',10);
            $this->Cell(10,8,utf8_to_latin('N°'),1);
            $this->Cell(60,8,utf8_to_latin('Nom'),1);
            $this->Cell(25,8,utf8_to_latin('Date de Nais'),1);
            $this->Cell(60,8,utf8_to_latin('Catégorie'),1);
            $this->Cell(35,8,utf8_to_latin('Difficultés'),1);
            $this->Ln();
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,utf8_to_latin('Page ').$this->PageNo().'/{nb}',0,0,'C');
        }
    }

    $pdf = new PDF('P','mm','A4');
    $pdf->AliasNbPages();
    $pdf->setClassName($className);
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);

    if ($result->num_rows > 0) {
        $counter = 1;
        while ($stu = $result->fetch_assoc()) {
            $name = utf8_to_latin($stu['name'] ?? '');
            $dob  = $stu['dob'] ?? '';
            $category = utf8_to_latin($stu['category1'] ?? '');
            $difficulties = '';
if (!empty($stu['difficulties'])) {
    $decoded = json_decode($stu['difficulties'], true);
    $words = [];

    if (is_array($decoded)) {
        foreach ($decoded as $diff) {
            $parts = explode(' ', trim($diff));
            $words[] = end($parts); // take last word
        }
        $difficulties = implode(', ', $words);
    } else {
        $parts = explode(' ', trim($stu['difficulties']));
        $difficulties = end($parts);
    }
}


            // cells: adjust widths/heights to your liking
            $pdf->Cell(10,8,$counter,1);
            $pdf->Cell(60,8,$name,1);
            $pdf->Cell(25,8,$dob,1);
            $pdf->Cell(60,8,$category,1);
            $pdf->Cell(35,8, utf8_decode($difficulties), 1);

            $pdf->Ln();
            $counter++;
        }
    } else {
        $pdf->Cell(0,10,utf8_to_latin("Aucun étudiant trouvé pour cette classe"),1,1,'C');
    }

    $stmt->close();
    $conn->close();
    $pdf->Output('I', 'Liste_eleves_' . preg_replace('/[^A-Za-z0-9_-]/','_', $className) . '.pdf');
    exit;
}

// If no class_id: show selection form (HTML)
$res = $conn->query("SELECT id, name FROM classes ORDER BY name");
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Exporter les élèves par classe (PDF)</title>
</head>
<body>
  <h3>Exporter les élèves par classe (PDF)</h3>
  <form method="get" action="">
    <label for="class_id">Choisir une classe:</label>
    <select name="class_id" id="class_id" required>
      <?php while ($r = $res->fetch_assoc()): ?>
        <option value="<?php echo htmlspecialchars($r['id']); ?>">
          <?php echo htmlspecialchars($r['name']); ?>
        </option>
      <?php endwhile; ?>
    </select>
    <br>
    <label for="difficulty_filter">Filtrer par difficulté (ex: Reading):</label>
    <input type="text" name="difficulty_filter" id="difficulty_filter" value="<?php echo isset($_GET['difficulty_filter']) ? htmlspecialchars($_GET['difficulty_filter']) : ''; ?>">
    <button type="submit">Exporter en PDF</button>
  </form>
  <p>Astuce: vous pouvez aussi appeler directement <code>export_pdf.php?class_id=1</code></p>
</body>
</html>
<?php
$conn->close();
?>
