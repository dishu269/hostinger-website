<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Connection Test</title>";
echo "<style>body{font-family:sans-serif;margin:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h1>Database Connection Test</h1>";

echo "<h2>Configuration Values:</h2>";
echo "<pre>";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . (empty(DB_PASS) ? '<span class="error">EMPTY - This is likely the problem!</span>' : '***hidden***') . "\n";
echo "DB_CHARSET: " . DB_CHARSET . "\n";
echo "</pre>";

echo "<h2>Environment Variables Check:</h2>";
echo "<pre>";
echo "getenv('DB_HOST'): " . (getenv('DB_HOST') ?: 'Not set') . "\n";
echo "getenv('DB_NAME'): " . (getenv('DB_NAME') ?: 'Not set') . "\n";
echo "getenv('DB_USER'): " . (getenv('DB_USER') ?: 'Not set') . "\n";
echo "getenv('DB_PASS'): " . (getenv('DB_PASS') ? '***hidden***' : '<span class="error">Not set - You need to set this!</span>') . "\n";
echo "</pre>";

echo "<h2>Connection Test:</h2>";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "<p>Attempting to connect...</p>";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo '<p class="success">✅ Database connection successful!</p>';
    
    // Test query
    $result = $pdo->query("SELECT VERSION() as version")->fetch();
    echo "<p>MySQL Version: " . $result['version'] . "</p>";
    
    // Check tables
    echo "<h3>Database Tables:</h3>";
    echo "<pre>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo '<span class="error">No tables found in database!</span>';
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo '<p class="error">❌ Database connection failed!</p>';
    echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    
    echo "<h3>Common Issues:</h3>";
    echo "<ul>";
    echo "<li>Database password not set in environment variables</li>";
    echo "<li>Wrong database credentials</li>";
    echo "<li>Database server not running</li>";
    echo "<li>Database does not exist</li>";
    echo "</ul>";
}

echo "<h2>Instructions to Fix:</h2>";
echo "<ol>";
echo "<li><strong>Set Environment Variables in Hosting Panel:</strong><br>";
echo "   - Login to your hosting control panel (e.g., Hostinger hPanel)<br>";
echo "   - Find Environment Variables or PHP Configuration section<br>";
echo "   - Add these variables:<br>";
echo "   <pre>DB_HOST=localhost
DB_NAME=u782093275_app
DB_USER=u782093275_app
DB_PASS=your_actual_password_here</pre></li>";
echo "<li><strong>Alternative: Create .env file</strong> (less secure):<br>";
echo "   Create a file named .env in the root directory with the above variables</li>";
echo "<li><strong>Update config.php</strong> if needed to read from .env file</li>";
echo "</ol>";

echo "<p><strong>Security Note:</strong> Delete this test file after fixing the issue!</p>";
echo "</body></html>";
?>