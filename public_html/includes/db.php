<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO instance.
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        
        // Show user-friendly error page
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Service Temporarily Unavailable</title>
            <style>
                body {
                    font-family: -apple-system, system-ui, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #f9fafb;
                    color: #1f2937;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    text-align: center;
                }
                .error-container {
                    max-width: 500px;
                    background: white;
                    border-radius: 16px;
                    padding: 40px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                }
                h1 { color: #dc2626; margin: 0 0 16px; }
                p { margin: 16px 0; line-height: 1.6; }
                .btn {
                    display: inline-block;
                    background: #0052cc;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    margin-top: 16px;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>Service Temporarily Unavailable</h1>
                <p>We're experiencing technical difficulties and are working to resolve them as quickly as possible.</p>
                <p>Please try again in a few minutes.</p>
                <a href="/" class="btn">Try Again</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

?>
