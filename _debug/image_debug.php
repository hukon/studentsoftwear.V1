<?php
// test_images.php - Diagnostic tool for image loading issues
// Upload this file to your website root and access it via browser

header('Content-Type: text/html; charset=utf-8');

$DB_HOST = 'sql308.infinityfree.com';
$DB_NAME = 'if0_39971413_student';
$DB_USER = 'if0_39971413';
$DB_PASS = '2thxXBOi75vZOFT';

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
} catch (Exception $e) {
  die("DB Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Diagnostics</title>
    <style>
        body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        img { max-width: 100px; height: 100px; object-fit: cover; border: 2px solid #ddd; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .path { font-family: monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Image Loading Diagnostics</h1>
    
    <div class="test">
        <h2>1. Server Information</h2>
        <p><strong>Server Protocol:</strong> <?php echo $_SERVER['REQUEST_SCHEME'] ?? 'http'; ?></p>
        <p><strong>Host:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
        <p><strong>Current URL:</strong> <?php echo ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
    </div>

    <div class="test">
        <h2>2. Uploads Folder Check</h2>
        <?php
        $uploadsDir = __DIR__ . '/uploads';
        if (is_dir($uploadsDir)) {
            echo '<p class="success">✅ Uploads folder exists: ' . $uploadsDir . '</p>';
            
            $perms = substr(sprintf('%o', fileperms($uploadsDir)), -4);
            echo '<p><strong>Permissions:</strong> ' . $perms;
            if ($perms == '0755' || $perms == '0775' || $perms == '0777') {
                echo ' <span class="success">✅ Good</span>';
            } else {
                echo ' <span class="warning">⚠️ May cause issues (recommended: 0755)</span>';
            }
            echo '</p>';
            
            if (is_writable($uploadsDir)) {
                echo '<p class="success">✅ Folder is writable</p>';
            } else {
                echo '<p class="error">❌ Folder is NOT writable - uploads will fail!</p>';
            }
            
            $files = glob($uploadsDir . '/*');
            echo '<p><strong>Files in uploads:</strong> ' . count($files) . '</p>';
            
        } else {
            echo '<p class="error">❌ Uploads folder does NOT exist!</p>';
            echo '<p>Attempting to create...</p>';
            if (mkdir($uploadsDir, 0755, true)) {
                echo '<p class="success">✅ Folder created successfully</p>';
            } else {
                echo '<p class="error">❌ Failed to create folder. Please create it manually.</p>';
            }
        }
        ?>
    </div>

    <div class="test">
        <h2>3. Database Image Paths</h2>
        <?php
        $stmt = $pdo->query("SELECT id, name, pic_path FROM students WHERE pic_path IS NOT NULL AND pic_path != '' LIMIT 10");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($students) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>Path in DB</th><th>File Exists?</th><th>Preview</th></tr>';
            
            foreach ($students as $s) {
                echo '<tr>';
                echo '<td>' . $s['id'] . '</td>';
                echo '<td>' . htmlspecialchars($s['name']) . '</td>';
                echo '<td class="path">' . htmlspecialchars($s['pic_path']) . '</td>';
                
                $filePath = __DIR__ . '/' . $s['pic_path'];
                if (file_exists($filePath)) {
                    echo '<td class="success">✅ Yes</td>';
                    
                    // Build URL
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . $s['pic_path'];
                    
                    echo '<td>';
                    echo '<img src="' . htmlspecialchars($url) . '" onerror="this.alt=\'Failed to load\'; this.style.border=\'2px solid red\';" alt="Preview" />';
                    echo '<br><small>' . htmlspecialchars($url) . '</small>';
                    echo '</td>';
                } else {
                    echo '<td class="error">❌ No</td>';
                    echo '<td>-</td>';
                }
                
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="warning">⚠️ No students with photos found in database</p>';
        }
        ?>
    </div>

    <div class="test">
        <h2>4. Direct File Access Test</h2>
        <?php
        $testFiles = glob(__DIR__ . '/uploads/*');
        if (count($testFiles) > 0) {
            echo '<p>Testing direct access to files:</p>';
            echo '<table>';
            echo '<tr><th>Filename</th><th>Size</th><th>Direct URL</th><th>Preview</th></tr>';
            
            foreach (array_slice($testFiles, 0, 5) as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $filename;
                
                echo '<tr>';
                echo '<td class="path">' . htmlspecialchars($filename) . '</td>';
                echo '<td>' . number_format($size) . ' bytes</td>';
                echo '<td><a href="' . $url . '" target="_blank">Open in new tab</a></td>';
                echo '<td><img src="' . $url . '" alt="' . $filename . '"/></td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="warning">⚠️ No files found in uploads folder</p>';
        }
        ?>
    </div>

    <div class="test">
        <h2>5. Browser Console Check</h2>
        <p>Open your browser console (F12) and check for any errors when loading this page.</p>
        <p>Common issues:</p>
        <ul>
            <li><strong>CORS errors:</strong> Add the .htaccess file I provided</li>
            <li><strong>404 errors:</strong> File path is wrong in database</li>
            <li><strong>403 errors:</strong> Permission denied - check folder permissions</li>
            <li><strong>Mixed content:</strong> HTTPS site loading HTTP images - all URLs should use HTTPS</li>
        </ul>
    </div>

    <div class="test">
        <h2>6. Recommended Actions</h2>
        <ol>
            <li>✅ Upload the <code>.htaccess</code> file I created to your root directory</li>
            <li>✅ Make sure uploads folder has 755 permissions</li>
            <li>✅ Test images in Chrome DevTools Network tab to see exact error</li>
            <li>✅ Clear browser cache (Ctrl+Shift+Delete)</li>
            <li>✅ Try opening image URLs directly in browser</li>
        </ol>
    </div>

    <script>
        console.log('=== Image Diagnostics ===');
        console.log('If you see CORS errors, upload the .htaccess file');
        console.log('If you see 404 errors, check file paths in database');
        
        // Test image loading in different ways
        const testImg = new Image();
        testImg.onload = () => console.log('✅ Test image loaded successfully');
        testImg.onerror = (e) => console.error('❌ Test image failed to load:', e);
        
        <?php if (count($testFiles) > 0): ?>
        testImg.src = '<?php echo $protocol . '://' . $_SERVER['HTTP_HOST'] . '/uploads/' . basename($testFiles[0]); ?>';
        <?php endif; ?>
    </script>
</body>
</html>
