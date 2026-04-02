<?php
// export_pdf_with_evaluation.php - Fixed 4 Separate Grids Format
require('fpdf/fpdf.php');

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

if (isset($_GET['class_id']) && intval($_GET['class_id']) > 0) {
    $class_id = intval($_GET['class_id']);
    $school_year = $_GET['school_year'] ?? '2025/2026';

    // Get class name
    $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    if (!$stmt) {
        die("❌ Erreur de préparation de requête: " . htmlspecialchars($conn->error));
    }
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        $conn->close();
        die("❌ Classe introuvable.");
    }
    
    $row = $res->fetch_assoc();
    $className = $row['name'];
    $stmt->close();

    // Get students with evaluations
    $stmt = $conn->prepare("SELECT s.name, e.* 
        FROM students s 
        LEFT JOIN evaluations e ON s.id = e.student_id AND e.school_year = ?
        WHERE s.class_id = ? 
        ORDER BY s.name");
    
    if (!$stmt) {
        die("❌ Erreur de préparation de requête: " . htmlspecialchars($conn->error));
    }
    
    $stmt->bind_param("si", $school_year, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    class PDF extends FPDF {
        private $className = '';
        private $schoolYear = '';
        
        public function setClassName($n) { $this->className = $n; }
        public function setSchoolYear($y) { $this->schoolYear = $y; }
        
        function Header() {
            $this->SetFont('Arial','B',11);
            $this->Cell(0,5,utf8_to_latin("Republique algerienne democratique et populaire"),0,1,'C');
            $this->Cell(0,5,utf8_to_latin("Ministere de l'education nationale"),0,1,'C');
            $this->Ln(2);
        }
        
        function Footer() {
            $this->SetY(-10);
            $this->SetFont('Arial','I',7);
            $this->Cell(0,5,'Page '.$this->PageNo(),0,0,'C');
        }
        
        function GridPage($students, $gridTitle, $criteria, $fields) {
            $this->AddPage();
            
            // School info
            $this->SetFont('Arial','',8);
            $this->Cell(100,4,utf8_to_latin("Direction de l'education de la wilaya de: Setif"),0,0,'L');
            $this->Cell(90,4,utf8_to_latin("Enseignant: ________"),0,1,'L');
            $this->Cell(100,4,utf8_to_latin("Etablissement: Sebihi Belkacem"),0,0,'L');
            $this->Cell(90,4,utf8_to_latin("Annee scolaire: {$this->schoolYear}"),0,1,'L');
            $this->Ln(2);
            
            // Titles
            $this->SetFont('Arial','B',9);
            $this->Cell(0,4,utf8_to_latin("Grille analytique d'evaluation des acquis 1AM"),0,1,'C');
            $this->SetFont('Arial','BU',9);
            $this->Cell(0,4,utf8_to_latin($gridTitle),0,1,'C');
            $this->Ln(2);
            
            // === COLUMN WIDTH CALCULATIONS ===
            $numCriteria = count($criteria);
            $numWidth = 7;      
            $nameWidth = 45;    
            $pageWidth = 190;   
            
            $criteriaArea = $pageWidth - $numWidth - $nameWidth;
            $criteriaWidth = $criteriaArea / $numCriteria;
            $cellWidth = $criteriaWidth / 4;
            
            // === HEADER CONFIGURATION ===
            $headerY = $this->GetY();
            $criteriaRowHeight = 15;
            $abcdRowHeight = 5;
            $totalHeaderHeight = $criteriaRowHeight + $abcdRowHeight;
            
            // === DRAW ALL BORDERS FIRST ===
            $this->SetLineWidth(0.2);
            
            // N° cell
            $this->Rect($this->lMargin, $headerY, $numWidth, $totalHeaderHeight);
            
            // Name cell
            $this->Rect($this->lMargin + $numWidth, $headerY, $nameWidth, $totalHeaderHeight);
            
            // Criteria headers
            for ($i = 0; $i < $numCriteria; $i++) {
                $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth);
                $this->Rect($x, $headerY, $criteriaWidth, $criteriaRowHeight);
            }
            
            // A/B/C/D cells
            for ($i = 0; $i < $numCriteria; $i++) {
                for ($j = 0; $j < 4; $j++) {
                    $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth) + ($j * $cellWidth);
                    $this->Rect($x, $headerY + $criteriaRowHeight, $cellWidth, $abcdRowHeight);
                }
            }
            
            // === FILL TEXT CONTENT ===
            
            // N° text
            $this->SetFont('Arial','B',8);
            $this->SetXY($this->lMargin, $headerY + ($totalHeaderHeight / 2) - 2);
            $this->Cell($numWidth, 4, utf8_to_latin('N°'), 0, 0, 'C');
            
            // Name column header
            $this->SetFont('Arial','B',6);
            $line1 = utf8_to_latin("Criteres et niveau");
            $line2 = utf8_to_latin("de maitrise");
            $line3 = utf8_to_latin("Nom et prenom");
            
            $this->SetXY($this->lMargin + $numWidth, $headerY + 3);
            $this->Cell($nameWidth, 4, $line1, 0, 0, 'C');
            $this->SetXY($this->lMargin + $numWidth, $headerY + 8);
            $this->Cell($nameWidth, 4, $line2, 0, 0, 'C');
            $this->SetXY($this->lMargin + $numWidth, $headerY + 13);
            $this->Cell($nameWidth, 4, $line3, 0, 0, 'C');
            
            // Criteria texts
            $this->SetFont('Arial','B',6);
            
            for ($i = 0; $i < $numCriteria; $i++) {
                $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth);
                $text = utf8_to_latin($criteria[$i]);
                
                // Smart word wrapping
                $words = explode(' ', $text);
                $lines = [];
                $currentLine = '';
                
                foreach ($words as $word) {
                    $testLine = $currentLine . ($currentLine ? ' ' : '') . $word;
                    if ($this->GetStringWidth($testLine) > ($criteriaWidth - 3)) {
                        if ($currentLine) {
                            $lines[] = $currentLine;
                        }
                        $currentLine = $word;
                    } else {
                        $currentLine = $testLine;
                    }
                }
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                
                // Limit to 3 lines
                if (count($lines) > 3) {
                    $lines = array_slice($lines, 0, 3);
                    $lines[2] = substr($lines[2], 0, 30) . '...';
                }
                
                // Center text vertically
                $numLines = count($lines);
                $lineHeight = 4;
                $startY = $headerY + (($criteriaRowHeight - ($numLines * $lineHeight)) / 2);
                
                foreach ($lines as $idx => $line) {
                    $this->SetXY($x, $startY + ($idx * $lineHeight));
                    $this->Cell($criteriaWidth, $lineHeight, $line, 0, 0, 'C');
                }
            }
            
            // A/B/C/D letters
            $this->SetFont('Arial','B',7);
            $letters = ['A', 'B', 'C', 'D'];
            
            for ($i = 0; $i < $numCriteria; $i++) {
                for ($j = 0; $j < 4; $j++) {
                    $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth) + ($j * $cellWidth);
                    $this->SetXY($x, $headerY + $criteriaRowHeight);
                    $this->Cell($cellWidth, $abcdRowHeight, $letters[$j], 0, 0, 'C');
                }
            }
            
            // === DATA ROWS ===
            $this->SetXY($this->lMargin, $headerY + $totalHeaderHeight);
            $this->SetFont('Arial','',7);
            $rowHeight = 5;
            $counter = 1;
            
            foreach ($students as $student) {
                // Check for page break
                if ($this->GetY() > 260) {
                    $this->AddPage();
                    
                    $this->SetFont('Arial','B',9);
                    $this->Cell(0,4,utf8_to_latin("Grille analytique d'evaluation des acquis 1AM"),0,1,'C');
                    $this->SetFont('Arial','BU',9);
                    $this->Cell(0,4,utf8_to_latin($gridTitle),0,1,'C');
                    $this->Ln(2);
                    
                    $contHeaderY = $this->GetY();
                    $contHeaderHeight = 8;
                    $contAbcdHeight = 5;
                    
                    // Draw continuation header borders
                    $this->Rect($this->lMargin, $contHeaderY, $numWidth, $contHeaderHeight + $contAbcdHeight);
                    $this->Rect($this->lMargin + $numWidth, $contHeaderY, $nameWidth, $contHeaderHeight + $contAbcdHeight);
                    
                    for ($i = 0; $i < $numCriteria; $i++) {
                        $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth);
                        $this->Rect($x, $contHeaderY, $criteriaWidth, $contHeaderHeight);
                    }
                    
                    for ($i = 0; $i < $numCriteria; $i++) {
                        for ($j = 0; $j < 4; $j++) {
                            $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth) + ($j * $cellWidth);
                            $this->Rect($x, $contHeaderY + $contHeaderHeight, $cellWidth, $contAbcdHeight);
                        }
                    }
                    
                    // Fill header text
                    $this->SetFont('Arial','B',7);
                    $this->SetXY($this->lMargin, $contHeaderY + ($contHeaderHeight + $contAbcdHeight) / 2 - 2);
                    $this->Cell($numWidth, 4, utf8_to_latin('N°'), 0, 0, 'C');
                    
                    $this->SetXY($this->lMargin + $numWidth, $contHeaderY + ($contHeaderHeight + $contAbcdHeight) / 2 - 2);
                    $this->Cell($nameWidth, 4, utf8_to_latin('Nom'), 0, 0, 'C');
                    
                    $this->SetFont('Arial','B',5.5);
                    for ($i = 0; $i < $numCriteria; $i++) {
                        $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth);
                        $text = utf8_to_latin($criteria[$i]);
                        
                        if ($this->GetStringWidth($text) > ($criteriaWidth - 2)) {
                            while ($this->GetStringWidth($text . '...') > ($criteriaWidth - 2) && strlen($text) > 10) {
                                $text = substr($text, 0, -1);
                            }
                            $text .= '...';
                        }
                        
                        $this->SetXY($x, $contHeaderY + 2);
                        $this->Cell($criteriaWidth, 4, $text, 0, 0, 'C');
                    }
                    
                    $this->SetFont('Arial','B',7);
                    for ($i = 0; $i < $numCriteria; $i++) {
                        for ($j = 0; $j < 4; $j++) {
                            $x = $this->lMargin + $numWidth + $nameWidth + ($i * $criteriaWidth) + ($j * $cellWidth);
                            $this->SetXY($x, $contHeaderY + $contHeaderHeight);
                            $this->Cell($cellWidth, $contAbcdHeight, $letters[$j], 0, 0, 'C');
                        }
                    }
                    
                    $this->SetXY($this->lMargin, $contHeaderY + $contHeaderHeight + $contAbcdHeight);
                    $this->Ln();
                    $this->SetFont('Arial','',7);
                }
                
                $currentY = $this->GetY();
                
                // Number
                $this->Cell($numWidth, $rowHeight, str_pad($counter++, 2, '0', STR_PAD_LEFT), 1, 0, 'C');
                
                // Name
                $name = utf8_to_latin($student['name']);
                if ($this->GetStringWidth($name) > ($nameWidth - 2)) {
                    while ($this->GetStringWidth($name) > ($nameWidth - 2) && strlen($name) > 0) {
                        $name = substr($name, 0, -1);
                    }
                }
                $this->Cell($nameWidth, $rowHeight, $name, 1, 0, 'L');
                
                // Evaluation marks
                foreach ($fields as $field) {
                    $value = strtoupper(trim($student[$field] ?? ''));
                    
                    foreach ($letters as $letter) {
                        $mark = ($value === $letter) ? 'X' : '';
                        $this->Cell($cellWidth, $rowHeight, $mark, 1, 0, 'C');
                    }
                }
                
                $this->Ln();
            }
        }
    }

    $pdf = new PDF('P','mm','A4');
    $pdf->AliasNbPages();
    $pdf->setClassName($className);
    $pdf->setSchoolYear($school_year);

    if ($result->num_rows > 0) {
        $students = [];
        while ($stu = $result->fetch_assoc()) {
            $students[] = $stu;
        }
        
        // Grid 1: Oral
        $pdf->GridPage($students, 
            "Evaluation de la comprehension et communication orales",
            [
                "Identifier le theme de la situation de communication",
                "Identifier les unites de sens",
                "S'exprimer en fonction de la situation de communication"
            ],
            ['oral_1', 'oral_2', 'oral_3']
        );
        
        // Grid 2: Reading
        $pdf->GridPage($students,
            "Evaluation de la lecture",
            [
                "Activer la correspondance graphie/phonie",
                "Realiser une lecture fluide",
                "Respecter l'intonation"
            ],
            ['reading_1', 'reading_2', 'reading_3']
        );
        
        // Grid 3: Comprehension
        $pdf->GridPage($students,
            "Comprehension de l'ecrit: Evaluation des idees vehiculees par le texte",
            [
                "Identifier le theme general du texte",
                "Identifier le champ lexical relatif au theme",
                "Reperer des informations"
            ],
            ['comp_1', 'comp_2', 'comp_3']
        );
        
        // Grid 4: Production
        $pdf->GridPage($students,
            "Evaluation de la production ecrite",
            [
                "Pertinence du texte produit",
                "Coherence du texte produit",
                "Correction de la langue",
                "Lisibilite de l'ecrit"
            ],
            ['prod_1', 'prod_2', 'prod_3', 'prod_4']
        );
        
    } else {
        $pdf->AddPage();
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10,utf8_to_latin("Aucun eleve trouve pour cette classe"),1,1,'C');
    }

    $stmt->close();
    $conn->close();
    
    $filename = 'Grilles_Eval_1AM_' . preg_replace('/[^A-Za-z0-9_-]/','_', $className) . '_' . $school_year . '.pdf';
    $pdf->Output('I', $filename);
    exit;
}

// If no class_id, show selection form
$classes = [];
$res = $conn->query("SELECT id, name FROM classes ORDER BY name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
}
$conn->close();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Exporter les grilles d'évaluation 1AM (PDF)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: 600;
            color: #34495e;
        }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #27ae60;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            width: 100%;
        }
        button:hover {
            background: #229954;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 13px;
            color: #1565c0;
        }
        .info ul {
            margin: 10px 0 0 20px;
        }
        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="evaluation_dashboard.php" class="back">← Retour</a>
        <h2>Exporter les 4 grilles d'évaluation 1AM (PDF)</h2>
        <form method="get">
            <label for="class_id">Choisir une classe 1AM:</label>
            <select name="class_id" id="class_id" required>
                <option value="">-- Sélectionner une classe --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo htmlspecialchars($class['id']); ?>">
                        <?php echo htmlspecialchars($class['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="school_year">Année scolaire:</label>
            <input type="text" name="school_year" id="school_year" value="2025/2026" required>
            
            <button type="submit">Générer le PDF (4 grilles)</button>
        </form>
        
        <div class="info">
            <strong>Le PDF généré contiendra 4 pages séparées :</strong>
            <ul>
                <li>Page 1: Compréhension et Communication Orales (3 critères)</li>
                <li>Page 2: Lecture (3 critères)</li>
                <li>Page 3: Compréhension de l'Écrit (3 critères)</li>
                <li>Page 4: Production Écrite (4 critères)</li>
            </ul>
            <p style="margin-top:10px;"><strong>Format:</strong> Chaque page suit exactement le modèle officiel du Ministère de l'Éducation Nationale.</p>
        </div>
    </div>
</body>
</html>