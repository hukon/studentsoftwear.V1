<?php
// export.php

// Database connection
$host = "sql212.infinityfree.com";
$db   = "if0_39896944_student";
$user = "if0_39896944"; // change this
$pass = "3I0LWKaoHhKbjEw"; // change this

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Choose which report to export
$type = $_GET['type'] ?? '';

// CSV export function
function exportCSV($filename, $result) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen("php://output", "w");

    // column headers
    $fields = $result->fetch_fields();
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    fputcsv($output, $headers);

    // data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Export logic
switch ($type) {
    case 'students':
        $result = $conn->query("SELECT * FROM students");
        exportCSV("students.csv", $result);
        break;

    case 'classes':
        $result = $conn->query("SELECT * FROM classes");
        exportCSV("classes.csv", $result);
        break;

    case 'holidays':
        $result = $conn->query("SELECT * FROM holidays");
        exportCSV("holidays.csv", $result);
        break;

    case 'seating':
        $result = $conn->query("SELECT * FROM seating");
        exportCSV("seating.csv", $result);
        break;

    default:
        echo "<h3>Rapports & Export</h3>";
        echo "<ul>";
        echo "<li><a href='export.php?type=students'>Exporter les étudiants</a></li>";
        echo "<li><a href='export.php?type=classes'>Exporter les classes</a></li>";
        echo "<li><a href='export.php?type=holidays'>Exporter les congés</a></li>";
        echo "<li><a href='export.php?type=seating'>Exporter les places</a></li>";
        echo "</ul>";
        break;
}

$conn->close();
?>
